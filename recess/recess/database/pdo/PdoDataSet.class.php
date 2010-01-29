<?php
Library::import('recess.database.sql.SqlBuilder');
Library::import('recess.database.sql.ISqlSelectOptions');
Library::import('recess.database.sql.ISqlConditions');

/**
 * PdoDataSet is used as a proxy to query results that is realized once the results are
 * iterated over or accessed using array notation. Queries can thus be built incrementally
 * and an SQL request will only be issued once needed.
 *  
 * Example usage:
 * 
 * $results = new PdoDataSet(Databases::getDefault());
 * $results->from('tableName')->equal('someColumn', 'Hi')->limit(10)->offset(50);
 * foreach($results as $result) { // This is when the query is run!
 * 		print_r($result);
 * }
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class PdoDataSet implements Iterator, Countable, ArrayAccess, ISqlSelectOptions, ISqlConditions {
	
	/**
	 * The SqlBuilder instance we use to build up the query string.
	 *
	 * @var SqlBuilder
	 */
	protected $sqlBuilder;
	
	/**
	 * Whether this instance has fetched results or not.
	 *
	 * @var boolean
	 */
	protected $hasResults = false;
	
	/**
	 * Array of results that is filled once a query is realized.
	 *
	 * @var array of type $this->rowClass
	 */
	protected $results = array();
	
	/**
	 * The PdoDataSource which this PdoDataSet is extracted from.
	 *
	 * @var PdoDataSource
	 */
	protected $source;
	
	/**
	 * The Class which PDO will fetch rows into.
	 *
	 * @var string Classname
	 */
	public $rowClass = 'stdClass';
	
	/**
	 * Index counter for our location in the result set.
	 *
	 * @var integer
	 */
	protected $index = 0;
	
	/**
	 * @param PdoDataSource $source
	 */
	public function __construct(PdoDataSource $source) {
		$this->sqlBuilder = new SqlBuilder();
		$this->source = $source;
	}
	
	public function __clone() {
		$this->sqlBuilder = clone $this->sqlBuilder;
		$this->hasResults = false;
		$this->results = array();
		$this->index = 0;
	}
	
	protected function reset() {
		$this->hasResults = false;
		$this->index = 0;
	}
	
	/**
	 * Once results are needed this method executes the accumulated query
	 * on the data source.
	 */
	protected function realize() {  
		if(!$this->hasResults) {
			unset($this->results);
			$this->results = $this->source->queryForClass($this->sqlBuilder, $this->rowClass);
			$this->hasResults = true;
		}
	}
	
	/**
	 * Return the SQL representation of this PdoDataSet
	 *
	 * @return string
	 */
	public function toSql() {
		return $this->sqlBuilder->select();
	}
	
	/**
	 * Return the SqlBuilder
	 * @return SqlBuilder
	 */
	public function sqlBuilder() {
		return $this->sqlBuilder;
	}
	
	/**
	 * Return the results as an array.
	 *
	 * @return array of type $this->rowClass
	 */
	public function toArray() {
		$this->realize();
		return $this->results;
	}
	
	public function count() {
		return iterator_count($this); 
	}
	
	public function exists() {
		return (bool)iterator_count($this);
	}
	
	/*
	 * The following methods are in accordance with the Iterator interface
	 */
	public function rewind() {
		if(!$this->hasResults) {$this->realize();}
		$this->index = 0;
	}
	
	public function current() {
		if(!$this->hasResults) {$this->realize();}
		return $this->results[$this->index];
	}

	public function key() {
		if(!$this->hasResults) {$this->realize();}
		return $this->index;
	}
	
	public function next() { 
		if(!$this->hasResults) {$this->realize();}
		$this->index++;
	} 
	
	public function valid() {
		if(!$this->hasResults) {$this->realize();}
		return isset($this->results[$this->index]);
	}
	
	/*
	 * The following methods are in accordance with the ArrayAccess interface
	 */
	function offsetExists($index) {
		if(!$this->hasResults) {$this->realize();}
		return isset($this->results[$index]);
	}

	function offsetGet($index) {
		if(!$this->hasResults) {$this->realize();}
		if(isset($this->results[$index])) {
			return $this->results[$index];
		} else {
			throw new OutOfBoundsException();
		}
	}

	function offsetSet($index, $value) {
		if(!$this->hasResults) {$this->realize();}
		$this->results[$index] = $value;
	}

	function offsetUnset($index) {
		if(!$this->hasResults) {$this->realize();}
		if(isset($this->results[$index])) {
			unset($this->results[$index]);
		}
	}
	
	function isEmpty() {
		return !(isset($this[0]) && $this[0] != null);
		// return !isset($this[0]);
	}
	
	/**
	 * Return the first item in the PdoDataSet or Null if none exist
	 *
	 * @return object or false
	 */
	function first() {
		if(!$this->hasResults) {
			$results = $this->range(0,1);
			if(!$results->isEmpty()) {
				return $results[0];
			}
		} else {
			if(!$this->isEmpty()) {
				return $this[0];
			}
		}
		
		return false;
	}
	
	/**
	 * @see SqlBuilder::assign
	 * @return PdoDataSet
	 */
	function assign($column, $value) { $copy = clone $this; $copy->sqlBuilder->assign($column, $value); return $copy; }
	
	/**
	 * @see SqlBuilder::useAssignmentsAsConditions
	 * @return PdoDataSet
	 */
	function useAssignmentsAsConditions($bool) { $copy = clone $this; $copy->sqlBuilder->useAssignmentsAsConditions($bool); return $copy; }
	
	/**
	 * @see SqlBuilder::from
	 * @return PdoDataSet
	 */
	function from($table) { $copy = clone $this; $copy->sqlBuilder->from($table); return $copy; }
	
	/**
	 * @see SqlBuilder::leftOuterJoin
	 * @return PdoDataSet
	 */
	function leftOuterJoin($table, $tablePrimaryKey, $fromTableForeignKey) { $copy = clone $this; $copy->sqlBuilder->leftOuterJoin($table, $tablePrimaryKey, $fromTableForeignKey); return $copy; }
	
	/**
	 * @see SqlBuilder::innerJoin
	 * @return PdoDataSet
	 */
	function innerJoin($table, $tablePrimaryKey, $fromTableForeignKey) { $copy = clone $this; $copy->sqlBuilder->innerJoin($table, $tablePrimaryKey, $fromTableForeignKey); return $copy; }
	
	/**
	 * @see SqlBuilder::selectAs
	 * @return PdoDataSet
	 */	
	function selectAs($select, $as) { $copy = clone $this; $copy->sqlBuilder->selectAs($select, $as); return $copy; }

	/**
	 * @see SqlBuilder::distinct
	 * @return PdoDataSet
	 */	
	function distinct() { $copy = clone $this; $copy->sqlBuilder->distinct(); return $copy; }

	/**
	 * @see SqlBuilder::equal
	 * @return PdoDataSet
	 */
	function equal($lhs, $rhs){ $copy = clone $this; $copy->sqlBuilder->equal($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::notEqual
	 * @return PdoDataSet
	 */
	function notEqual($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->notEqual($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::between
	 * @return PdoDataSet
	 */	
	function between ($column, $lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->between($column, $lhs, $rhs); return $copy; }

	/**
	 * @see SqlBuilder::greaterThan
	 * @return PdoDataSet
	 */	
	function greaterThan($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->greaterThan($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::greaterThanOrEqualTo
	 * @return PdoDataSet
	 */	
	function greaterThanOrEqualTo($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->greaterThanOrEqualTo($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::lessThan
	 * @return PdoDataSet
	 */	
	function lessThan($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->lessThan($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::lessThanOrEqualTo
	 * @return PdoDataSet
	 */	
	function lessThanOrEqualTo($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->lessThanOrEqualTo($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::like
	 * @return PdoDataSet
	 */	
	function like($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->like($lhs,$rhs); return $copy; }

	/**
	 * @see SqlBuilder::like
	 * @return PdoDataSet
	 */
	function notLike($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->notLike($lhs,$rhs); return $copy; }
	
	/**
	 * @see SqlBuilder::isNull
	 * @return PdoDataSet
	 */	
	function isNull($lhs) { $copy = clone $this; $copy->sqlBuilder->isNull($lhs); return $copy; }

	/**
	 * @see SqlBuilder::like
	 * @return PdoDataSet
	 */
	function isNotNull($lhs) { $copy = clone $this; $copy->sqlBuilder->isNotNull($lhs); return $copy; }
	
	/**
	 * @see SqlBuilder::where
	 * @return PdoDataSet
	 */	
	function where($lhs, $rhs, $operator) { $copy = clone $this; $copy->sqlBuilder->where($lhs,$rhs,$operator); return $copy; }

	/**
	 * @see SqlBuilder::limit
	 * @return PdoDataSet
	 */	
	function limit($size) { $copy = clone $this; $copy->sqlBuilder->limit($size); return $copy; }

	/**
	 * @see SqlBuilder::offset
	 * @return PdoDataSet
	 */	
	function offset($offset) { $copy = clone $this; $copy->sqlBuilder->offset($offset); return $copy; }

	/**
	 * @see SqlBuilder::range
	 * @return PdoDataSet
	 */	
	function range($start, $finish) { $copy = clone $this; $copy->sqlBuilder->range($start,$finish); return $copy; }

	/**
	 * @see SqlBuilder::orderBy
	 * @return PdoDataSet
	 */	
	function orderBy($clause) { $copy = clone $this; $copy->sqlBuilder->orderBy($clause); return $copy; }

	/**
	 * @see SqlBuilder::groupBy
	 * @return PdoDataSet
	 */	
	function groupBy($clause) { $copy = clone $this; $copy->sqlBuilder->groupBy($clause); return $copy; }
	
	/**
	 * @see SqlBuilder::in
	 * @return PdoDataSet
	 */		
	function in($lhs, $rhs) { $copy = clone $this; $copy->sqlBuilder->in($lhs,$rhs); return $copy; }
	
	/**
	 * @see SqlBuilder::getTable
	 * @return string
	 */		
	function getTable() { return $this->sqlBuilder->getTable();  }
	
	/**
	 * @see SqlBuilder::getTableAlias
	 * @return string
	 */		
	function getTableAlias() { return $this->sqlBuilder->getTableAlias();  }
	
	/**
	 * @see SqlBuilder::getRawTable
	 * @return string
	 */		
	function getRawTable() { return $this->sqlBuilder->getRawTable();  }
}
?>
