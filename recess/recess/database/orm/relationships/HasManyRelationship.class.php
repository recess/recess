<?php
Library::import('recess.database.orm.relationships.Relationship');

/**
 * A HasMany Recess Relationship is an abstraction of for the Many side of a 
 * foreign key relationship on the RDBMS.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class HasManyRelationship extends Relationship {
	
	function getType() {
		return 'HasMany';
	}
	
	function init($modelClassName, $relationshipName) {
		$this->localClass = $modelClassName;
		$this->name = $relationshipName;
		$this->foreignKey = Inflector::toCamelCaps($modelClassName) . 'Id';
		$this->foreignClass = Inflector::toSingular(Inflector::toProperCaps($relationshipName));
		$this->onDelete = Relationship::UNSPECIFIED;
	}
	
	function getDefaultOnDeleteMode() {
		if(!isset($this->through)) {
			return Relationship::CASCADE;
		} else {
			return Relationship::DELETE;
		}
	}
	
	function attachMethodsToModelDescriptor(ModelDescriptor $descriptor) {
		$alias = $this->name;
		$attachedMethod = new AttachedMethod($this,'selectModel', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
		
		$alias = 'addTo' . ucfirst($this->name);
		$attachedMethod = new AttachedMethod($this,'addTo', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
		
		$alias = 'removeFrom' . ucfirst($this->name);
		$attachedMethod = new AttachedMethod($this,'removeFrom', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
	}
	
	function addTo(Model $model, Model $relatedModel) {
		if(!$model->primaryKeyIsSet()) {
			$model->insert();
		}
			
		if(!isset($this->through)) {
			$foreignKey = $this->foreignKey;
			$localKey = Model::primaryKeyName($model);	
			$relatedModel->$foreignKey = $model->$localKey;
			$relatedModel->save();
		} else {
			if(!$relatedModel->primaryKeyIsSet()) {
				$relatedModel->insert();
			}
			// TODO: This is a shitshow.
			$through = new $this->through;
			$localPrimaryKey = Model::primaryKeyName($model);
			$localForeignKey = $this->foreignKey;
			$through->$localForeignKey = $model->$localPrimaryKey;
			
			$relatedPrimaryKey = Model::primaryKeyName($this->through);
			$relatedForeignKey = Model::getRelationship($this->through, Inflector::toSingular($this->name))->foreignKey;
			$through->$relatedForeignKey = $relatedModel->$relatedPrimaryKey;
			
			$through->insert();
		}
		
		return $model;
	}
	
	function removeFrom(Model $model, Model $relatedModel) {
		if(!isset($this->through)) {
			$foreignKey = $this->foreignKey;
			$relatedModel->$foreignKey = '';
			$relatedModel->save();
			return $model;
		} else {
			$through = new $this->through;
			
			$localPrimaryKey = Model::primaryKeyName($model);
			$localForeignKey = $this->foreignKey;
			$through->$localForeignKey = $model->$localPrimaryKey;
			
			$relatedPrimaryKey = Model::primaryKeyName($this->through);
			$relatedForeignKey = Model::getRelationship($this->through, Inflector::toSingular($this->name))->foreignKey;
			$through->$relatedForeignKey = $relatedModel->$relatedPrimaryKey;
			
			$through->find()->delete(false);
		}
	}
	
	function selectModel(Model $model) {
		return $this->augmentSelect($model->select());
	}
	
	function selectModelSet(ModelSet $modelSet) {
		return $this->augmentSelect($modelSet);
	}
	
	protected function augmentSelect(PdoDataSet $select) {
		if(!isset($this->through)) {
			$relatedClass = $this->foreignClass;
		} else {
			$relatedClass = $this->through;
		}
		
		$relatedTable = Model::tableFor($relatedClass);
		$localTable = Model::tableFor($this->localClass);
		$foreignKey = $relatedTable . '.' . $this->foreignKey;
		$primaryKey = Model::primaryKeyFor($this->localClass);
		
		$select = $select	
					->from($relatedTable)
					->innerJoin($localTable,
								$foreignKey, 
								$primaryKey 
								);
		
		$select->rowClass = $relatedClass;
		
		if(!isset($this->through)) {
			return $select;
		} else {
			$select = $select->distinct();
			$relationship = $this->name;
			return $select->$relationship();
		}
	}
	
	function onDeleteCascade(Model $model) {
		$related = $this->selectModel($model)->delete();
		
		if(isset($this->through)) {
			$modelPk = Model::primaryKeyName($model);
			$queryBuilder = new SqlBuilder();
			$queryBuilder
				->from(Model::tableFor($this->through))
				->equal($this->foreignKey, $model->$modelPk);
			
			$source = Model::sourceFor($model);
			
			$source->executeStatement($queryBuilder->delete(), $queryBuilder->getPdoArguments());		
		}
	}
	
	function onDeleteDelete(Model $model) {
		$modelPk = Model::primaryKeyName($model);
		
		if(!isset($this->through)) {
			$relatedClass = $this->foreignClass;
		} else {
			$relatedClass = $this->through;
		}
		
		$queryBuilder = new SqlBuilder();
		$queryBuilder
			->from(Model::tableFor($relatedClass))
			->equal($this->foreignKey, $model->$modelPk);
		
		$source = Model::sourceFor($model);
		
		$source->executeStatement($queryBuilder->delete(), $queryBuilder->getPdoArguments());
	}
	
	function onDeleteNullify(Model $model) {
		if(isset($this->through)) {
			return $this->onDeleteDelete($model);
		}
		
		$modelPk = Model::primaryKeyName($model);
		
		$queryBuilder = new SqlBuilder();
		$queryBuilder
			->from(Model::tableFor($this->foreignClass))
			->assign($this->foreignKey, null)
			->equal($this->foreignKey, $model->$modelPk);
			
		$source = Model::sourceFor($model);
		
		$source->executeStatement($queryBuilder->update(), $queryBuilder->getPdoArguments());
	}
	
	function __set_state($array) {
		$relationship = new HasManyRelationship();
		$relationship->name = $array['name'];
		$relationship->localClass = $array['localClass'];
		$relationship->foreignClass = $array['foreignClass'];
		$relationship->onDelete = $array['onDelete'];
		$relationship->through = $array['through'];
		return $relationship;
	}

}

?>