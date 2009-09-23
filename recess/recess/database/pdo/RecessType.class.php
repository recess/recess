<?php
/**
 * Recess has a fixed set of native 'recess' types that are mapped to vendor specific
 * column types by individual DataSourceProviders.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
abstract class RecessType {
	const STRING = 'String';
	const TEXT = 'Text';
	const INTEGER = 'Integer';
	const BOOLEAN = 'Boolean';
	const FLOAT = 'Float';
	const TIME = 'Time';
	const TIMESTAMP = 'Timestamp';
	const DATE = 'Date';
	const DATETIME = 'DateTime';
	const BLOB = 'Blob';
	const POINT = 'Point';
	
	private static $all;
	
	static function all() {
		if(!isset(self::$all)) {
			self::$all = array(
							self::STRING, 
							self::TEXT, 
							self::INTEGER, 
							self::BOOLEAN, 
							self::FLOAT, 
							self::TIME, 
							self::TIMESTAMP, 
							self::DATETIME,
							self::DATE,
							self::BLOB,
							self::POINT);
		}
		return self::$all;
	}
}
?>