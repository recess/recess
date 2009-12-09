<?php
/**
 * Interface used which maps to conditional SQL statements
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
interface ISqlConditions {
	
	function equal($column, $value);
	function notEqual($column, $value);
	function between ($column, $big, $small);
	function greaterThan($column, $value);
	function greaterThanOrEqualTo($column, $value);
	function lessThan($column, $value);
	function lessThanOrEqualTo($column, $value);
	function like($column, $value);
	function notLike($column, $value);
	function in($column, $value);
	
}
?>