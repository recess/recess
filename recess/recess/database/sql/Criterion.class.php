<?php

class Criterion {
	public $column;
	public $pdoLabel;
	public $value;
	public $operator;
	
	const GREATER_THAN = ' > ';
	const GREATER_THAN_EQUAL_TO = ' >= ';
	
	const LESS_THAN = ' < ';
	const LESS_THAN_EQUAL_TO = ' <= ';
	
	const EQUAL_TO = ' = ';
	const NOT_EQUAL_TO = ' != ';
	
	const LIKE = ' LIKE ';
	const NOT_LIKE = ' NOT LIKE ';
	
	const IS_NULL = ' IS NULL';
	const IS_NOT_NULL = ' IS NOT NULL';
	
	const COLON = ':';
	
	const ASSIGNMENT = '=';
	const ASSIGNMENT_PREFIX = 'assgn_';
	
	const UNDERSCORE = '_';
	
	const IN = ' IN ';
	
	//const DISTANCE = ' <-> ';
	
	const CONTAINS = ' @> ';
	
	public function __construct($column, $value, $operator, $pdoLabel = null){
		$this->column = $column;
		$this->value = $value;
		$this->operator = $operator;

		if(!isset($pdoLabel)) {
			$this->pdoLabel = preg_replace('/[ \-.,\(\)`"]/', '_', $column);
		} else {
			$this->pdoLabel = preg_replace('/[ \-.,\(\)`"]/', '_', $pdoLabel);
		}
	}
	
	public function getQueryParameter() {
		// Begin workaround for PDO's poor numeric binding
		if(is_array($this->value)) {
	      $value = '('.implode(',', $this->value).')';
	      return $value;
		}
		
		if(is_numeric($this->value)) {
			return $this->value;
		}
		// End workaround
		
		if($this->operator == self::ASSIGNMENT) { 
			return self::COLON . self::ASSIGNMENT_PREFIX . $this->pdoLabel;
		} elseif($this->operator == self::IS_NULL || $this->operator == self::IS_NOT_NULL) {
			return '';
		} else {
			return self::COLON . $this->pdoLabel;
		}
	}
}

class Join {
	const NATURAL = 'NATURAL';
	
	const LEFT = 'LEFT';
	const RIGHT = 'RIGHT';
	const FULL = 'FULL';
	
	const INNER = 'INNER';
	const OUTER = 'OUTER';
	const CROSS = 'CROSS';
	
	public $natural;
	public $leftRightOrFull;
	public $innerOuterOrCross = 'OUTER';
	
	public $table;
	public $tablePrimaryKey;
	public $fromTableForeignKey;
	
	public function __construct($leftRightOrFull, $innerOuterOrCross, $table, $tablePrimaryKey, $fromTableForeignKey, $natural = ''){
		$this->natural = $natural;
		$this->leftRightOrFull = $leftRightOrFull;
		$this->innerOuterOrCross = $innerOuterOrCross;
		$this->table = $table;
		$this->tablePrimaryKey = $tablePrimaryKey;
		$this->fromTableForeignKey = $fromTableForeignKey;
	}
}

?>
