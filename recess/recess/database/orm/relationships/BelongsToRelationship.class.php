<?php
/**
 * A BelongsTo Recess Relationship is an abstraction of for the Many side of a 
 * foreign key relationship on the RDBMS.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class BelongsToRelationship extends Relationship {
	
	function getType() {
		return 'BelongsTo';
	}
	
	function init($modelClassName, $relationshipName) {
		$this->localClass = $modelClassName;
		$this->name = $relationshipName;
		$this->onDelete = Relationship::NULLIFY;
		$this->foreignKey = Inflector::toCamelCaps($relationshipName) . 'Id';
		$this->foreignClass = Inflector::toProperCaps($relationshipName);
	}
	
	function attachMethodsToModelDescriptor(ModelDescriptor $descriptor) {
		$alias = $this->name;
		$attachedMethod = new AttachedMethod($this, 'selectModel', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
		
		$alias = 'set' . ucfirst($this->name);
		$attachedMethod = new AttachedMethod($this,'set', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
		
		$alias = 'unset' . ucfirst($this->name);
		$attachedMethod = new AttachedMethod($this,'remove', $alias);
		$descriptor->addAttachedMethod($alias, $attachedMethod);
	}
	
	function set(Model $model, Model $relatedModel) {
		if(!$relatedModel->primaryKeyIsSet()) {
			$relatedModel->insert();
		}
		
		$foreignKey = $this->foreignKey;
		$relatedPrimaryKey = Model::primaryKeyName($relatedModel);
		$model->$foreignKey = $relatedModel->$relatedPrimaryKey;
		$model->save();
		
		return $model;
	}
	
	function remove(Model $model) {		
		$foreignKey = $this->foreignKey;
		$model->$foreignKey = '';
		$model->save();
		
		return $model;
	}
	
	protected function augmentSelect(PdoDataSet $select) {
		$select = $select	
					->from(Model::tableFor($this->foreignClass))
					->innerJoin(Model::tableFor($this->localClass), 
								Model::primaryKeyFor($this->foreignClass), 
								Model::tableFor($this->localClass) . '.' . $this->foreignKey);
						
		$select->rowClass = $this->foreignClass;
		return $select;
	}
	
	function selectModel(Model $model) {
		$foreignKey = $this->foreignKey;
		
		if(isset($model->$foreignKey)) {
			$select = $this->augmentSelect($model->all());
			$select = $select->equal(Model::tableFor($this->localClass) . '.' . $this->foreignKey, $model->$foreignKey);
		} else {
			$select = $this->augmentSelect($model->select());
		}
		
		if(isset($select[0])) {
			return $select[0];
		} else {
			return null;
		}
	}
	
	function selectModelSet(ModelSet $modelSet) {
		return $this->augmentSelect($modelSet);
	}
	
	function onDeleteCascade(Model $model) {
		$this->selectModel($model)->delete();
	}
	
	function onDeleteDelete(Model $model) {
		$relatedModel = $this->selectModel($model);
		if($relatedModel != null) {
			$relatedModel->delete(false);		
		}
	}
	
	function onDeleteNullify(Model $model) {
		// no-op
	}
	
	function __set_state($array) {
		$relationship = new BelongsToRelationship();
		$relationship->name = $array['name'];
		$relationship->localClass = $array['localClass'];
		$relationship->foreignClass = $array['foreignClass'];
		$relationship->onDelete = $array['onDelete'];
		$relationship->through = $array['through'];
		return $relationship;
	}
}
?>