<?php
Library::import('recess.lang.Inflector');
Library::import('recess.lang.Object');
Library::import('recess.lang.reflection.RecessReflectionClass');
Library::import('recess.lang.Annotation');

Library::import('recess.database.Databases');
Library::import('recess.database.sql.ISqlConditions');
Library::import('recess.database.orm.ModelClassInfo');
Library::import('recess.database.sql.SqlBuilder');

Library::import('recess.database.orm.annotations.HasManyAnnotation', true);
Library::import('recess.database.orm.annotations.BelongsToAnnotation', true);
Library::import('recess.database.orm.annotations.DatabaseAnnotation', true);
Library::import('recess.database.orm.annotations.TableAnnotation', true);
Library::import('recess.database.orm.annotations.ColumnAnnotation', true);

Library::import('recess.database.orm.relationships.Relationship');
Library::import('recess.database.orm.relationships.HasManyRelationship');
Library::import('recess.database.orm.relationships.BelongsToRelationship');

/**
 * Model is the basic unit of organization in Recess' simple ORM.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
abstract class Model extends Object implements ISqlConditions {
	
	const CLASSNAME = 'Model';
	const INSERT = 'insert';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const SAVE = 'save';
	
	/**
	 * Constructor can take either a keyed array or a string/int
	 * to set the primary key with;
	 *
	 * @param mixed $data
	 */
	final public function __construct($data = null) {
		if(is_numeric($data) || is_string($data)) {
			$primaryKey = Model::primaryKeyName($this);
			$this->$primaryKey = $data;
		} else if (is_array($data)) {
			$this->copy($data, false);
		}
	}
	
	/**
	 * Return the model's descriptor.
	 */
	static function getDescriptor($class) {
		return self::getClassDescriptor($class);
	}
	
	/**
	 * Get the datasource for a class.
	 *
	 * @param mixed $class
	 * @return ModelDataSource
	 */
	static function sourceFor($class) {
		return self::getClassDescriptor($class)->getSource();
	}
	
	/**
	 * Get the name of the datasource for a class
	 *
	 * @param mixed $class
	 * @return string Key name of the ModelDataSource in Databases
	 */
	static function sourceNameFor($class) {
		return self::getClassDescriptor($class)->getSourceName();
	}
	
	/**
	 * The table which $modelClass is persisted on.
	 *
	 * @param mixed $class
	 * @return string Table Name
	 */
	static function tableFor($class) {
		return self::getClassDescriptor($class)->getTable();
	}
	
	/**
	 * Return the primary key column name for a class. This is prefixed
	 * with the class' table name.
	 *
	 * @param midex $class
	 * @return string Primary Key Column Name ie "table.id"
	 */
	static function primaryKeyFor($class) {
		$descriptor = self::getClassDescriptor($class);
		return $descriptor->getTable() . '.' . $descriptor->primaryKey;
	}
	
	/**
	 * Return the property name for the primary key.
	 *
	 * @param mixed $class
	 * @return string Primary key name ie: 'id'
	 */
	static function primaryKeyName($class) {
		return self::getClassDescriptor($class)->primaryKey;
	}
	
	/**
	 * Get a relationship on a class or instance by the relationship's name.
	 *
	 * @param mixed $classOrInstance
	 * @param string $name of the relationship
	 * @return Relationship
	 */
	static function getRelationship($classOrInstance, $name) {
		if(isset(self::getClassDescriptor($classOrInstance)->relationships[$name])) {
			return self::getClassDescriptor($classOrInstance)->relationships[$name];
		} else {
			return false;
		}
	}
	
	/**
	 * Return all relationships for a class or instance
	 *
	 * @param mixed $classOrInstance
	 * @return array of Relationship
	 */
	static function getRelationships($classOrInstance) {
		return self::getClassDescriptor($classOrInstance)->relationships;
	}
	
	/**
	 * Retrieve an array of column names in the table corresponding to
	 * a model class.
	 *
	 * @param mixed $classOrInstance
	 * @return array of strings of column names
	 */
	static function getColumns($classOrInstance) {
		return self::getClassDescriptor($classOrInstance)->columns;
	}
	
	/**
	 * Retrieve an array of the properties.
	 *
	 * @param mixed $classOrInstance
	 * @return array of type ModelProperty
	 */
	static function getProperties($classOrInstance) {
		return self::getClassDescriptor($classOrInstance)->properties;
	}

	protected static function initClassDescriptor($class) {	
		return new ModelDescriptor($class, false);
	}
	
	protected static function shapeDescriptorWithProperty($class, $property, $descriptor, $annotations) {
		if(!$property->isStatic() && $property->isPublic()) {
			$modelProperty = new ModelProperty();
			$modelProperty->name = $property->name;
			$descriptor->properties[$modelProperty->name] = $modelProperty;
		}
		return $descriptor;
	}
	
	protected static function finalClassDescriptor($class, $descriptor) {
		$modelSource = Databases::getSource($descriptor->getSourceName());
		$modelSource->cascadeTableDescriptor($descriptor->getTable(), $modelSource->modelToTableDescriptor($descriptor));	
		return $descriptor;
	}
	
	/**
	 * Attempt to generate a table from this model's descriptor.
	 *
	 * @param mixed $class
	 */
	static function createTableFor($class) {
		$descriptor = self::getClassDescriptor($class);
		$modelSource = Databases::getSource($descriptor->getSourceName());
		$modelSource->exec($modelSource->createTableSql($descriptor));
	}

	/**
	 * Build a ModelSet from this instance by assigning this Model instance's
	 * properties and values.
	 *
	 * @return ModelSet
	 */
	protected function getModelSet() {
		$thisClassDescriptor = self::getClassDescriptor($this);
		$result = $thisClassDescriptor->getSource()->selectModelSet($thisClassDescriptor->getTable());
		$pkName = self::primaryKeyName($this);
		
		if(isset($this->$pkName)) {
			$result = $result->equal($pkName,$this->$pkName);
		} else {
			foreach($this as $column => $value) {
				if(isset($this->$column) && in_array($column,$thisClassDescriptor->columns)) {
					$result = $result->assign($column, $value);
				}
			}
		}
		
		$result->rowClass = get_class($this);
		return $result;
	}
	
	/**
	 * Return a results ModelSet based on the values of this instance's properties.
	 *
	 * @return ModelSet
	 */
	function select() { 
		return $this->getModelSet()->useAssignmentsAsConditions(true);
	}

	/**
	 * Alias for select.
	 *
	 * @return ModelSet
	 */
	function find() { return $this->select(); }
	
	/**
	 * Select all. This is different from find() in that find will use
	 * assigned values to the model as equality statements.
	 *
	 * @return ModelSet
	 */
	function all() { 
		return $this->getModelSet();
	}
	
	
	/**
	 * Return a SqlBuilder object which has set the table and optionally
	 * assigned values to columns based on this instances' properties. This is used in
	 * insert(), update(), and delete()
	 *
	 * @param ModelDescriptor $descriptor
	 * @param boolean $useAssignment
	 * @param boolean $excludePrimaryKey
	 * @return SqlBuilder
	 */
	protected function assignmentSqlForThisObject(ModelDescriptor $descriptor, $useAssignment = true, $excludePrimaryKey = false) {
		$sqlBuilder = new SqlBuilder();
		$sqlBuilder->from($descriptor->getTable());

		if(empty($descriptor->columns)) {
			throw new RecessException('The "' . $descriptor->getTable() . '" table does not appear to exist in your database.', get_defined_vars());
		}
		
		foreach($this as $column => $value) {
			if($excludePrimaryKey && $descriptor->primaryKey == $column) continue;
			if(in_array($column, $descriptor->columns) && isset($value)) {
				if($useAssignment) {
					$sqlBuilder->assign($column,$value);
				} else {
					$sqlBuilder->equal($column,$value);
				}
			}
		}
		return $sqlBuilder;
	}
	
	/**
	 * Delete row(s) from the data source which match this instance.
	 *
	 * @param boolean $cascade - Also delete models related to this model?
	 * @return boolean
	 * 
	 * !Wrappable delete
	 */
	function wrappedDelete($cascade = true) {
		$thisClassDescriptor = self::getClassDescriptor($this);
		
		if($cascade) {
			foreach($thisClassDescriptor->relationships as $relationship) {
				$relationship->delete($this);
			}
		}
			
		$sqlBuilder = $this->assignmentSqlForThisObject($thisClassDescriptor, false);
		
		return $thisClassDescriptor->getSource()->executeSqlBuilder($sqlBuilder, 'delete');	
	}

	/**
	 * Insert row into the data source based on the values of this instance.
	 * @return boolean
	 * 
	 * !Wrappable insert
	 */
	function wrappedInsert() {
		$thisClassDescriptor = self::getClassDescriptor($this);
		
		$sqlBuilder = $this->assignmentSqlForThisObject($thisClassDescriptor);
		
		$result = $thisClassDescriptor->getSource()->executeSqlBuilder($sqlBuilder, 'insert');
		
	 	$primaryKey = $thisClassDescriptor->primaryKey;
	 	
	 	$this->$primaryKey = $thisClassDescriptor->getSource()->lastInsertId();
	 	
	 	return $result;
	}

	/**
	 * Update a row in the data source based on the values of this instance.
	 * @return boolean
	 * 
	 * !Wrappable update
	 */
	function wrappedUpdate() {
		$thisClassDescriptor = self::getClassDescriptor($this);
		
		$sqlBuilder = $this->assignmentSqlForThisObject($thisClassDescriptor, true, true);
		$primaryKey = $thisClassDescriptor->primaryKey;
		$sqlBuilder->equal($thisClassDescriptor->primaryKey, $this->$primaryKey);
		
		return $thisClassDescriptor->getSource()->executeSqlBuilder($sqlBuilder, 'update');
	}
	
	/**
	 * Insert or update depending on whether or not this instance's primary key is set.
	 *
	 * @return boolean
	 * 
	 * !Wrappable save
	 */
	function wrappedSave()   {
		if($this->primaryKeyIsSet()) {
			return $this->update();
		} else {
			return $this->insert();
		}
	}
	
	/**
	 * @return boolean
	 */
	function primaryKeyIsSet() {
		$thisClassDescriptor = self::getClassDescriptor($this);
		
		$primaryKey = $thisClassDescriptor->primaryKey;
				
		if(isset($this->$primaryKey)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Shortcut method which will determine whether a row
	 * with the current instances properties exists. If so, it will
	 * preload those values (side effects).
	 * 
	 * Usage:
	 * $model->id = 1;
	 * if($model->exists()) {
	 *  die('a lonesome death');
	 * }
	 *
	 * @return boolean
	 */
	function exists() {
		$descriptor = self::getClassDescriptor($this);
		$blank_object = true;
		foreach($this as $column => $value) {
			if(in_array($column, $descriptor->columns) && isset($value)) {
				$blank_object = false;
			}
		}
		
		if(!$blank_object) {
			$result = $this->select()->first();
			if($result !== false) {
				$this->copy($result, false);
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Copy values from a key/value array or another model/object
	 * to this instance.
	 *
	 * @param iterable $keyValuePair
	 * @return Model
	 */
	function copy($keyValuePair, $excludePrimaryKey = true) {
		if($excludePrimaryKey) {
			$pk = Model::primaryKeyName($this);
		}
		foreach($keyValuePair as $key => $value) {
			if($excludePrimaryKey && $key == $pk) {
				continue;
			}
			$this->$key = $value;
		}
		return $this;
	}
	
	/**
	 * Add equality criteria between a column and value
	 *
	 * @param string $lhs Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function equal($column, $rhs){ return $this->select()->equal($column, $rhs); }
	
	/**
	 * Add inequality criteria between a column and value
	 *
	 * @param string $lhs Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function notEqual($column, $rhs) { return $this->select()->notEqual($column,$rhs); }
	
	/**
	 * Add criteria to state a column's value falls between $small and $big
	 *
	 * @param string $column Column
	 * @param mixed $small Floor Value
	 * @param mixed $big Ceiling Value
	 * @return PdoDataSet
	 */
	function between ($column, $small, $big) { return $this->select()->between($column, $small, $big); }
	
	/**
	 * SQL criteria specifying a column's value is greater than $rhs
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function greaterThan($column, $rhs) { return $this->select()->greaterThan($column,$rhs); }
	
	/**
	 * SQL criteria specifying a column's value is no less than $rhs
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function greaterThanOrEqualTo($column, $rhs) { return $this->select()->greaterThanOrEqualTo($column,$rhs); }
	
	/**
	 * SQL criteria specifying a column's value is less than $rhs
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function lessThan($column, $rhs) { return $this->select()->lessThan($column,$rhs); }
	
	/**
	 * SQL criteria specifying a column's value is no greater than $rhs
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function lessThanOrEqualTo($column, $rhs) { return $this->select()->lessThanOrEqualTo($column,$rhs); }
	
	/**
	 * SQL LIKE criteria, note this does not automatically include wildcards
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function like($column, $rhs) { return $this->select()->like($column,$rhs); }
	
	/**
	 * SQL NOT LIKE criteria, note this does not automatically include wildcards
	 *
	 * @param string $column Column
	 * @param mixed $rhs Value
	 * @return PdoDataSet
	 */
	function notLike($column, $rhs) { return $this->select()->notLike($column,$rhs); }
	
	/**
	 * SQL IS NULL criteria
	 *
	 * @param string $column Column
	 * @return PdoDataSet
	 */
	function isNull($column) { return $this->select()->isNull($column); }
	
	/**
	 * SQL IS NOT NULL criteria
	 *
	 * @param string $column Column
	 * @return PdoDataSet
	 */
	function isNotNull($column) { return $this->select()->isNotNull($column); }
	
	/**
	 * SQL IN criteria
	 * @param string $column Column name
	 * @param array $values An array of values to test
	 * @return PdoDataSet
	 */
	function in($column, $values) { return $this->select()->in($column,$values); }
}

/**
 * Class descriptor + metadata for a model.
 */
class ModelDescriptor extends ClassDescriptor {
	
	public $primaryKey = 'id';
	private $table;
	
	public $plural;
	public $modelClass;
	public $relationships;
	
	public $columns;
	public $properties;
	
	public $source;
	
	function __construct($class, $loadColumns = true) {
		$this->table = strtolower($class);
		$this->relationships = array();
		$this->properties = array();
		$this->source = false;
		if($loadColumns) {
			$this->columns = $this->getSource()->getColumns($this->table);
		} else {
			$this->columns = array();
		}
		$this->primaryKeyColumn = 'id';
		$this->modelClass = $class;
	}
	
	function __set_state($array) {
		$descriptor = new ModelDescriptor($array['modelClass']);
		$descriptor->primaryKey = $array['primaryKey'];
		$descriptor->table = $array['table'];
		$descriptor->relationships = $array['relationships'];
		$descriptor->columns = $array['columns'];
		$descriptor->properties = $array['properties'];
		$descriptor->source = $array['source'];
		$descriptor->attachedMethods = $array['attachedMethods'];
		return $descriptor;
	}
	
	function setTable($table, $loadColumns = true) {
		$this->table = $table;
		if($loadColumns) {
			$source = $this->getSource();
			if(isset($source)) {
				$this->columns = $this->getSource()->getColumns($this->table);
			} else {
				throw new RecessException('Data Source "' . $this->getSourceName() . '" is not set.', array());
			}
		} else {
			$this->columns = array();
		}
	}
	
	function getTable() {
		return $this->table;
	}
	
	function setSource($source) {
		$this->source = $source;		
	}
	
	function getSource() {
		if(!$this->source) {
			return Databases::getDefaultSource();
		} else {
			return Databases::getSource($this->source);
		}
	}
	
	function getSourceName() {
		if(!$this->source) {
			return 'Default';
		} else {
			return $this->source;
		}
	}
}

/**
 * The data structure for a propery on a model
 */
class ModelProperty {
	public $name;
	public $type;
	public $pkCallback;
	public $isAutoIncrement = false;
	public $isPrimaryKey = false;
	public $isForeignKey = false;
	public $required = false;
	
	function __set_state($array) {
		$prop = new ModelProperty();
		$prop->name = $array['name'];
		$prop->type = $array['type'];
		$prop->pkCallback = $array['pkCallback'];
		$prop->isAutoIncrement = $array['autoincrement'];
		$prop->isPrimaryKey = $array['isPrimaryKey'];
		$prop->isForeignKey = $array['isForeignKey'];
		return $prop;
	}
}
?>
