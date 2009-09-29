<?php
Library::import('recess.database.sql.ISqlConditions');
Library::import('recess.database.sql.ISqlSelectOptions');

/**
 * SqlBuilder is used to incrementally compose named-parameter PDO Sql strings 
 * using a simple, chainable method call API. This is a naive wrapper that does
 * not gaurantee valid SQL output (i.e. column names using reserved SQL words).
 * 
 * 4 classes of SQL strings can be built: INSERT, UPDATE, DELETE, SELECT.
 * This class is intentionally arranged from the low complexity requirements
 * of INSERT to the more complex SELECT.
 * 
 * INSERT:        table, column/value assignments
 * UPDATE/DELETE: where conditions
 * SELECT:        order, joins, offset, limit, distinct
 * 
 * Example usage: 
 * 
 * $sqlBuilder->into('table_name')->assign('column', 'value')->insert() .. 
 * 		returns "INSERT INTO table_name (column) VALUES (:column)"
 * $sqlBuilder->getPdoArguments() returns array( ':column' => 'value' )
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @contributor Luiz Alberto Zaiats 
 * 
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class SqlBuilder implements ISqlConditions, ISqlSelectOptions {
		
	/* INSERT */
	protected $table;
	protected $assignments = array();
	
	/**
	 * Build an INSERT SQL string from SqlBuilder's state.
	 * 
	 * @return string INSERT string.
	 */
	public function insert() {
		$this->insertSanityCheck();

		$sql = 'INSERT INTO ' . self::escapeWithTicks($this->table);
		
		$columns = '';
		$values = '';
		$first = true;
		$table_prefix = $this->tableAsPrefix() . '.';
		foreach($this->assignments as $assignment) {
			if($first) { $first = false; }
			else { $columns .= ', '; $values .= ', '; }
			$columns .= self::escapeWithTicks(str_replace($table_prefix, '', $assignment->column));
			$values .= $assignment->getQueryParameter();
		}
		$columns = ' (' . $columns . ')';
		$values = '(' . $values . ')';
		
		$sql .= $columns . ' VALUES ' . $values;
		
		return $sql;
	}
	
	/**
	 * Safety check used with insert to ensure only a table and assignments were applied.
	 */
	protected function insertSanityCheck() {
		if(	!empty($this->conditions) )
			throw new RecessException('Insert does not use conditionals.', get_defined_vars());
		if(	!empty($this->joins) )
			throw new RecessException('Insert does not use joins.', get_defined_vars());
		if(	!empty($this->orderBy) ) 
			throw new RecessException('Insert does not use order by.', get_defined_vars());
		if(	!empty($this->groupBy) ) 
			throw new RecessException('Insert does not use group by.', get_defined_vars());
		if(	isset($this->limit) )
			throw new RecessException('Insert does not use limit.', get_defined_vars());
		if(	isset($this->offset) )
			throw new RecessException('Insert does not use offset.', get_defined_vars());
		if(	isset($this->distinct) )
			throw new RecessException('Insert does not use distinct.', get_defined_vars());
	}
	
	/**
	 * Set the table of focus on a sql statement.
	 *
	 * @param string $table
	 * @return SqlBuilder 
	 */
	public function table($table) { $this->table = $table; return $this; }
	
	/**
	 * Alias for table (insert into)
	 *
	 * @param string $table
	 * @return SqlBuilder
	 */
	public function into($table) { return $this->table($table); }

	/**
	 * Assign a value to a column. Used with inserts and updates.
	 *
	 * @param string $column
	 * @param mixed $value
	 * @return SqlBuilder
	 */
	public function assign($column, $value) { 
		if(strpos($column, '.') === false) {
			if(isset($this->table)) {
				$this->assignments[] = new Criterion($this->tableAsPrefix() . '.' . $column, $value, Criterion::ASSIGNMENT); 
			} else {
				throw new RecessException('Cannot assign without specifying table.', get_defined_vars());
			}
		} else {
			$this->assignments[] = new Criterion($column, $value, Criterion::ASSIGNMENT); 
		}
		return $this;
	}
	
	/* UPDATE & DELETE */
	protected $conditions = array();
	protected $conditionsUsed = array();
	protected $useAssignmentsAsConditions = false;
	
	/**
	 * Build a DELETE SQL string from SqlBuilder's state.
	 *
	 * @return string DELETE string
	 */
	public function delete() {
		$this->deleteSanityCheck();
		return 'DELETE FROM ' . self::escapeWithTicks($this->table) . $this->whereHelper();
	}
	
	/**
	 * Safety check used with delete.
	 */
	protected function deleteSanityCheck() {
		if(	!empty($this->joins) )
			throw new RecessException('Delete does not use joins.', get_defined_vars());
		if(	!empty($this->orderBy) ) 
			throw new RecessException('Delete does not use order by.', get_defined_vars());
		if(	!empty($this->groupBy) ) 
			throw new RecessException('Delete does not use group by.', get_defined_vars());
		if(	isset($this->limit) )
			throw new RecessException('Delete does not use limit.', get_defined_vars());
		if(	isset($this->offset) )
			throw new RecessException('Delete does not use offset.', get_defined_vars());
		if(	isset($this->distinct) )
			throw new RecessException('Delete does not use distinct.', get_defined_vars());
		if( !empty($this->assignments) && !$this->useAssignmentsAsConditions)
			throw new RecessException('Delete does not use assignments. To use assignments as conditions add ->useAssignmentsAsConditions() to your method call chain.', get_defined_vars());
	}
	
	/**
	 * Build an UPDATE SQL string from SqlBuilder's state.
	 *
	 * @return string
	 */
	public function update() {
		$this->updateSanityCheck();
		$sql = 'UPDATE ' . self::escapeWithTicks($this->table) . ' SET ';
		
		$first = true;
		$table_prefix = $this->tableAsPrefix() . '.';
		foreach($this->assignments as $assignment) {
			if($first) { $first = false; }
			else { $sql .= ', '; }
			$sql .= self::escapeWithTicks(str_replace($table_prefix, '', $assignment->column)) . ' = ' . $assignment->getQueryParameter();;
		}
		
		$sql .= $this->whereHelper();
		
		return $sql;
	}
	
	/**
	 * Safety check used with update.
	 */
	protected function updateSanityCheck() {
		if(	!empty($this->joins) )
			throw new RecessException('Update does not use joins.', get_defined_vars());
		if(	!empty($this->orderBy) ) 
			throw new RecessException('Update (in Recess) does not use order by.', get_defined_vars());
		if(	!empty($this->groupBy) ) 
			throw new RecessException('Update (in Recess) does not use group by.', get_defined_vars());
		if(	isset($this->limit) )
			throw new RecessException('Update (in Recess) does not use limit.', get_defined_vars());
		if(	isset($this->offset) )
			throw new RecessException('Update (in Recess) does not use offset.', get_defined_vars());
		if(	isset($this->distinct) )
			throw new RecessException('Update does not use distinct.', get_defined_vars());
	}
	
	/**
	 * Return the collection of PDO named parameters and values to be
	 * applied to a parameterized PDO statement.
	 *
	 * @return array
	 */
	public function getPdoArguments() {
		if($this->useAssignmentsAsConditions)
			return array_merge($this->conditions, $this->cleansedAssignmentsAsConditions());
		else
			return array_merge($this->conditions, $this->assignments);
	}
	
	/**
	 * Method for when using assignments as conditions. This purges
	 * assignments which have null values.
	 *  
	 * @return array
	 */
	protected function cleansedAssignmentsAsConditions() {
		$assignments = array();
		
		$count = count($this->assignments);
		for($i = 0; $i < $count; $i++) {
			if(isset($this->assignments[$i]->value))
				$assignments[] = $this->assignments[$i];
		}
		
		return $assignments;
	}
	
	/**
	 * Alias to specify which table is being used.
	 *
	 * @param string $table
	 * @return SqlBuilder
	 */
	public function from($table) { return $this->table($table); }
	
	/**
	 * Handy shortcut which allows assignments to be used as conditions
	 * in a select statement.
	 *
	 * @param boolean $bool
	 * @return SqlBuilder
	 */
	public function useAssignmentsAsConditions($bool) { $this->useAssignmentsAsConditions = $bool; return $this; }
	
	/* ISqlConditions */
	
	/**
	 * Equality expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param mixed $value
	 * @return SqlBuilder
	 */
	public function equal($column, $value)       { return $this->addCondition($column, $value, Criterion::EQUAL_TO); }
	
	/**
	 * Inequality than expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param mixed $value
	 * @return SqlBuilder
	 */
	public function notEqual($column, $value)    { return $this->addCondition($column, $value, Criterion::NOT_EQUAL_TO); }
	
	/**
	 * Shortcut alias for SqlBuilder->lessThan($column,$big)->greaterThan($column,$small) 
	 *
	 * @param string $column
	 * @param numeric $small Greater than this number. 
	 * @param numeric $big Less than this number.
	 * @return SqlBuilder
	 */
	public function between ($column, $small, $big) { $this->greaterThan($column, $small); return $this->lessThan($column, $big); }
	
	/**
	 * Greater than expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param numeric $value
	 * @return SqlBuilder
	 */
	public function greaterThan($column, $value)          { return $this->addCondition($column, $value, Criterion::GREATER_THAN); }
	
	/**
	 * Greater than or equal to expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param numeric $value
	 * @return SqlBuilder
	 */
	public function greaterThanOrEqualTo($column, $value)         { return $this->addCondition($column, $value, Criterion::GREATER_THAN_EQUAL_TO); }
	
	/**
	 * Less than expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param numeric $value
	 * @return SqlBuilder
	 */
	public function lessThan($column, $value)          { return $this->addCondition($column, $value, Criterion::LESS_THAN); }

	/**
	 * Less than or equal to expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param numeric $value
	 * @return SqlBuilder
	 */
	public function lessThanOrEqualTo($column, $value)         { return $this->addCondition($column, $value, Criterion::LESS_THAN_EQUAL_TO); }

	/**
	 * LIKE expression for WHERE clause of update, delete, or select statements, does not include wildcards.
	 *
	 * @param string $column
	 * @param string $value
	 * @return SqlBuilder
	 */
	public function like($column, $value)        { return $this->addCondition($column, $value, Criterion::LIKE); }
	
	/**
	 * NOT LIKE expression for WHERE clause of update, delete, or select statements, does not include wildcards.
	 *
	 * @param string $column
	 * @param string $value
	 * @return SqlBuilder
	 */
	public function notLike($column, $value)        { return $this->addCondition($column, $value, Criterion::NOT_LIKE); }

	/**
	 * IS NULL expression for WHERE clause of update, delete, or select statements
	 *
	 * @param string $column
	 * @param string $value
	 * @return SqlBuilder
	 */
	public function isNull($column)        { return $this->addCondition($column, null, Criterion::IS_NULL); }
	
	/**
	 * IS NOT NULL expression for WHERE clause of update, delete, or select statements
	 *
	 * @param string $column
	 * @param string $value
	 * @return SqlBuilder
	 */
	public function isNotNull($column)        { return $this->addCondition($column, null, Criterion::IS_NOT_NULL); }
	
	/**
	 * IN to expression for WHERE clause of update, delete, or select statements.
	 *
	 * @param string $column
	 * @param array $value
	 * @return SqlBuilder
	 */
	public function in($column, $value)        { return $this->addCondition($column, $value, Criterion::IN); }
	
	
	/**
	 * Add a condition to the SqlBuilder statement. Additional logic here to prepend
	 * a table name and also keep track of which columns have already been assigned conditions
	 * to ensure we do not use two identical named parameters in PDO.
	 *
	 * @param string $column
	 * @param mixed $value
	 * @param string $operator
	 * @return SqlBuilder
	 */
	protected function addCondition($column, $value, $operator) {
		if(strpos($column, '.') === false && strpos($column, '(') === false && !in_array($column, array_keys($this->selectAs))) {
			if(isset($this->table)) {
				$column = $this->tableAsPrefix() . '.' . $column;
			} else {
				throw new RecessException('Cannot use "' . $operator . '" operator without specifying table for column "' . $column . '".', get_defined_vars());
			}
		}
				
		if(isset($this->conditionsUsed[$column])) {
			$this->conditionsUsed[$column]++;
			$pdoLabel = $column . '_' . $this->conditionsUsed[$column];
		} else {
			$this->conditionsUsed[$column] = 1;
			$pdoLabel = null;
		}
		
		$this->conditions[] = new Criterion($column, $value, $operator, $pdoLabel);
		
		return $this;
	}
	
	/* SELECT */
	protected $select = '*';
	protected $selectAs = array();
	protected $joins = array();
	protected $limit;
	protected $offset;
	protected $distinct;
	protected $orderBy = array();
	protected $groupBy = array();
	protected $usingAliases = false;
	
	/**
	 * Build a SELECT SQL string from SqlBuilder's state.
	 *
	 * @return string
	 */
	public function select() {
		$this->selectSanityCheck();

		$sql = 'SELECT ' . $this->distinct . self::escapeWithTicks($this->select);

		foreach($this->selectAs as $selectAs) {
			$sql .= ', ' . $selectAs;
		}
		
		$sql .= ' FROM ' . self::escapeWithTicks($this->table);
		
		$sql .= $this->joinHelper();
		
		$sql .= $this->whereHelper();
		
		$sql .= $this->orderByHelper();
		
		$sql .= $this->groupByHelper();
		
		$sql .= $this->rangeHelper();
		
		return $sql;
	}
	
	/**
	 * Safety check used when creating a SELECT statement.
	 */
	protected function selectSanityCheck() {
		if( (!empty($this->where) || !empty($this->orderBy)) && !isset($this->table))
			throw new RecessException('Must have from if using where.', get_defined_vars());
		
		if( isset($this->offset) && !isset($this->limit))
			throw new RecessException('Must define limit if using offset.', get_defined_vars());
		
		if($this->select == '*' && !isset($this->table))
			throw new RecessException('No table has been selected.', get_defined_vars());
	}
	
	/* ISqlSelectOptions */
	
	/**
	 * LIMIT results to some number of records.
	 *
	 * @param integer $size
	 * @return SqlBuilder
	 */
	public function limit($size)           { $this->limit = $size; return $this; }
	
	/**
	 * When used in conjunction with limit($size), offset specifies which row the results will begin at.
	 *
	 * @param integer $offset
	 * @return SqlBuilder
	 */
	public function offset($offset)        { $this->offset = $offset; return $this; }
	
	/**
	 * Shortcut alias to ->limit($finish - $start)->offset($start);
	 *
	 * @param integer $start
	 * @param integer $finish
	 * @return SqlBuilder
	 */
	public function range($start, $finish) { $this->offset = $start; $this->limit = $finish - $start; return $this; }
	
	/**
	 * Add an ORDER BY expression to sql string. Example: ->orderBy('name ASC')
	 *
	 * @param string $clause
	 * @return SqlBuilder
	 */
	public function orderBy($clause) {
		if(($spacePos = strpos($clause,' ')) !== false) {
			$name = substr($clause,0,$spacePos);
		} else {
			$name = $clause;
		}
		
		if(isset($this->table) && strpos($clause,'.') === false && strpos($name,'(') === false && !array_key_exists($name, $this->selectAs)) {
			$this->orderBy[] = $this->tableAsPrefix() . '.' . $clause; 
		} else {
			$this->orderBy[] = $clause;
		}
		return $this; 
	}
	
	/**
	 * Add an GROUP BY expression to sql string. Example: ->groupBy('name')
	 *
	 * @param string $clause
	 * @return SqlBuilder
	 */
	public function groupBy($clause) {
		if(($spacePos = strpos($clause,' ')) !== false) {
			$name = substr($clause,0,$spacePos);
		} else {
			$name = $clause;
		}
		
		if(isset($this->table) && strpos($clause,'.') === false && strpos($name,'(') === false && !array_key_exists($name, $this->selectAs)) {
			$this->groupBy[] = $this->tableAsPrefix() . '.' . $clause; 
		} else {
			$this->groupBy[] = $clause;
		}
		return $this; 
	}
	
	/**
	 * Helper method which returns the current table even when it 
	 * is aliased due to joins between the same table.
	 *
	 * @return string The current table which can be used as a prefix.
	 */
	protected function tableAsPrefix() {
		if($this->usingAliases) {
			$spacePos = strrpos($this->table, ' ');
			if($spacePos !== false) {
				return substr($this->table, $spacePos + 1);
			}
		}
		return $this->table;
	}
	
	/**
	 * Left outer join expression for SELECT SQL statement.
	 *
	 * @param string $table
	 * @param string $tablePrimaryKey
	 * @param string $fromTableForeignKey
	 * @return SqlBuilder
	 */
	public function leftOuterJoin($table, $tablePrimaryKey, $fromTableForeignKey) {
		return $this->join(Join::LEFT, Join::OUTER, $table, $tablePrimaryKey, $fromTableForeignKey);
	}
	
	/**
	 * Inner join expression for SELECT SQL statement.
	 *
	 * @param string $table
	 * @param string $tablePrimaryKey
	 * @param string $fromTableForeignKey
	 * @return SqlBuilder
	 */
	public function innerJoin($table, $tablePrimaryKey, $fromTableForeignKey) {
		return $this->join('', Join::INNER, $table, $tablePrimaryKey, $fromTableForeignKey);
	}
	
	/**
	 * Generic join expression to be added to a SELECT SQL statement.
	 *
	 * @param string $leftOrRight
	 * @param string $innerOrOuter
	 * @param string $table
	 * @param string $tablePrimaryKey
	 * @param string $fromTableForeignKey
	 * @return SqlBuilder
	 */
	protected function join($leftOrRight, $innerOrOuter, $table, $tablePrimaryKey, $fromTableForeignKey) {
		if($this->table == $table) {
			$oldTable = $this->table;
			$parts = explode('__', $this->table);
			$partsCount = count($parts);
			if($partsCount > 0 && is_int($parts[$partsCount-1])) {
				$number = $parts[$partsCount - 1] + 1;
			} else {
				$number = 2;
			}
			$tableAlias = $this->table . '__' . $number;
			$this->table = self::escapeWithTicks($this->table) . ' AS ' . self::escapeWithTicks($tableAlias);
			$this->usingAliases = true;
			
			$tablePrimaryKey = str_replace($oldTable,$tableAlias,$tablePrimaryKey);			
		}
		
		$this->select = $this->tableAsPrefix() . '.*';
		$this->joins[] = new Join($leftOrRight, $innerOrOuter, $table, $tablePrimaryKey, $fromTableForeignKey);	
		return $this;
	}
	
	/**
	 * Add additional field to select statement which is aliased using the AS parameter.
	 * ->selectAs("ABS(location - 5)", 'distance') translates to => SELECT ABS(location-5) AS distance
	 *
	 * @param string $select
	 * @param string $as
	 * @return SqlBuilder
	 */
	public function selectAs($select, $as) {
		$this->selectAs[$as] = $select . ' as ' . $as;
		return $this;
	}
	
	/**
	 * Add a DISTINCT clause to SELECT SQL.
	 *
	 * @return SqlBuilder
	 */
	public function distinct() { $this->distinct = ' DISTINCT '; return $this; }

	/* HELPER METHODS */
	protected function whereHelper() {
		$sql = '';
		
		$first = true;
		if(!empty($this->conditions)) {
			foreach($this->conditions as $clause) {
				if(!$first) { $sql .= ' AND '; } else { $first = false; } // TODO: Figure out how we'll do ORing
				$sql .= self::escapeWithTicks($clause->column) . $clause->operator . $clause->getQueryParameter();
			}
		}
		
		if($this->useAssignmentsAsConditions && !empty($this->assignments)) {
			$assignments = $this->cleansedAssignmentsAsConditions();
			foreach($assignments as $clause) {
				if(!$first) { $sql .= ' AND '; } else { $first = false; } // TODO: Figure out how we'll do ORing
				$sql .= self::escapeWithTicks($clause->column) . ' = ' . $clause->getQueryParameter();
			}
		}
		
		if($sql != '') {
			$sql = ' WHERE ' . $sql;
		}
		
		return $sql;
	}
	
	protected function joinHelper() {
		$sql = '';
		if(!empty($this->joins)) {
			$joins = array_reverse($this->joins, true);
			foreach($joins as $join) {
				$joinStatement = ' ';
				
				if(isset($join->natural) && $join->natural != '') {
					$joinStatement .= $join->natural . ' ';
				}
				if(isset($join->leftRightOrFull) && $join->leftRightOrFull != '') {
					$joinStatement .= $join->leftRightOrFull . ' ';
				}
				if(isset($join->innerOuterOrCross) && $join->innerOuterOrCross != '') {
					$joinStatement .= $join->innerOuterOrCross . ' ';
				}
				
				$onStatement = ' ON ' . self::escapeWithTicks($join->tablePrimaryKey) . ' = ' . self::escapeWithTicks($join->fromTableForeignKey);
				$joinStatement .= 'JOIN ' . self::escapeWithTicks($join->table) . $onStatement;
				
				$sql .= $joinStatement;
			}
		}
		return $sql;
	}
	
	protected static function escapeWithTicks($string) {
		if($string == '*' || strpos($string, '`') !== false) {
			return $string;
		}
		if(strpos($string,Library::dotSeparator) !== false) { // Todo: Replace with Regexp
			$parts = explode(Library::dotSeparator, $string);
			if(isset($parts[1]) && $parts[1] == '*') {
				return '`' . $parts[0] . '`.*';
			} else {
				return '`' . implode('`.`', $parts) . '`';
			}
		} else {
			return '`' . $string . '`';
		}
	}
	
	protected function orderByHelper() {
		$sql = '';
		if(!empty($this->orderBy)){
			$sql = ' ORDER BY ';
			$first = true;
			foreach($this->orderBy as $order){
				if(!$first) { $sql .= ', '; } else { $first = false; }
				$sql .= $order;
			}
		}
		return $sql;
	}
	
	protected function groupByHelper() {
		$sql = '';
		if(!empty($this->groupBy)){
			$sql = ' GROUP BY ';
			$first = true;
			foreach($this->groupBy as $order){
				if(!$first) { $sql .= ', '; } else { $first = false; }
				$sql .= $order;
			}
		}
		return $sql;
	}
	
	protected function rangeHelper() {
		$sql = '';
		if(isset($this->limit)){ $sql .= ' LIMIT ' . $this->limit; }
		if(isset($this->offset)){ $sql .= ' OFFSET ' . $this->offset; }
		return $sql;
	}
	

	public function getCriteria() {
		return array_merge($this->conditions, $this->assignments);
	}
	public function getTable() {
		return $this->table;
	}
}

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
	
	public function __construct($column, $value, $operator, $pdoLabel = null){
		$this->column = $column;
		$this->value = $value;
		$this->operator = $operator;
		if(!isset($pdoLabel)) {
			$this->pdoLabel = preg_replace('/[ \-.,\(\)`]/', '_', $column);
		} else {
			$this->pdoLabel = preg_replace('/[ \-.,\(\)`]/', '_', $pdoLabel);
		}
	}
	
	public function getQueryParameter() {
		// Begin workaround for PDO's poor numeric binding
		if(is_array($this->value)) {
	      $value = '('.implode(',', $this->value).')';
	      return $value;
		}
		
		if(is_numeric($this->value) && !is_string($this->value)) {
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