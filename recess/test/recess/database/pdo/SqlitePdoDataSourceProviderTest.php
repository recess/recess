<?php

Library::import('recess.database.pdo.PdoDataSource');
Library::import('recess.database.pdo.exceptions.DataSourceCouldNotConnectException');
Library::import('recess.database.pdo.exceptions.ProviderDoesNotExistException');
Library::import('recess.database.pdo.PdoDataSet');

/**
 * Unit Tests for recess/sources/db/pdo/PdoDataSource using SQLite provider
 * @author Kris Jordan <krisjordan@gmail.com>
 * @see recess/sources/db/pdo/PdoDataSource.class.php
 */
class SqlitePdoDataSourceProviderTest extends PHPUnit_Framework_TestCase  {
	
	const DSN = 'sqlite::memory:';
	const FAIL_DSN = 'slqite:test.db';
	
	protected $source = null;
	
	function setUp() {		
		$pdo = new PdoDataSource(self::DSN);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->beginTransaction();
		$pdo->exec('CREATE TABLE table_b (id INTEGER PRIMARY KEY AUTOINCREMENT, column_b STRING)');
		$pdo->exec('CREATE TABLE table_a (id INTEGER PRIMARY KEY AUTOINCREMENT, column_a STRING)');
		$pdo->commit();
		$this->source = $pdo;
	}

	function testConstructor() {
		$this->assertEquals($this->source->errorCode(), 0);
	}
	
	function testConstructorException() {
		try {
			$this->source = new PdoDataSource(self::FAIL_DSN);
		} catch(DataSourceCouldNotConnectException $e) {
			$this->assertTrue(true);
			return;
		}
		$this->fail('Constructor should have thrown failed DSN.');
	}
	
	function testGetTables() {
		$tables = $this->source->getTables();
		
		$expected = array('table_a', 'table_b');
		
		$this->assertEquals($tables, $expected);
	}
	
	function testGetColumns() {
		$table_a_columns = $this->source->getColumns('table_a');
		$table_b_columns = $this->source->getColumns('table_b');
		$table_a_columns_expected = array('column_a', 'id');
		$table_b_columns_expected = array('column_b', 'id');
		
		$this->assertEquals($table_a_columns, $table_a_columns_expected);
		$this->assertEquals($table_b_columns, $table_b_columns_expected);
	}
	
	function testSelect() {
		$selectedSet = $this->source->select();
		$this->assertTrue($selectedSet instanceof PdoDataSet);
	}
	
	function tearDown() {
		$this->source->beginTransaction();
		$this->source->exec('DROP TABLE table_b');
		$this->source->exec('DROP TABLE table_a');
		$this->source->commit();
		unset($this->source);
	}	
}
?>
