<?php
Library::import('recess.database.sql.PgSqlBuilder');
//Library::import('recess.database.sql.Criterion');
/**
 * Unit Tests for recess.database.sql.PgSqlBuilder
 * @author Kris Jordan <krisjordan@gmail.com>
 * @see lib/recess/sources/db/sql/SqlBuilder.class.php
 */
class PgSqlBuilderTest extends PHPUnit_Framework_TestCase  {
	
	protected $builder = null;
	
	function setUp() {
		$this->builder = new PgSqlBuilder();
	}
	
	function testFrom() {
		$this->builder->from('table');
		$this->assertEquals('SELECT * FROM "table"', $this->builder->select());
	}
	
	function testFromChain() {
		$this->builder->from('table_a')->from('table_b');
		$this->assertEquals('SELECT * FROM "table_b"', $this->builder->select());
	}
	
	function testWhereWithoutFrom() {
		try {
			$this->builder->like('name','value');
			$this->fail('Should throw cannot have where without from exception.');
		} catch(Exception $e) {
			// Pass
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testMultipleWheres() {
		$this->builder->from('aTable')->greaterThan('height',5.2)->lessThan('age',100);
		$expected = 'SELECT * FROM "aTable" WHERE "aTable"."height" > 5.2 AND "aTable"."age" < 100';
		$this->assertEquals($expected, $this->builder->select());
	}
	
	function testOrderByFail() {
		try {
			$this->builder->orderBy('name ASC');
			$this->builder->select();
			$this->fail('Should throw, cannot have orderby without from.');
		} catch (Exception $e) {
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testOffsetWithoutLimit() {
		try {
			$this->builder->from('table')->offset(25);
			$this->builder->select();
			$this->fail('Should throw, cannot have offset without limit.');
		} catch (Exception $e) {
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testLimitOffset() {
		$this->builder->from('table')->limit(10)->offset(1);
		$expected = 'SELECT * FROM "table" LIMIT 10 OFFSET 1';
		$this->assertEquals($expected, $this->builder->select());
	}
	
	function testLeftOuterJoin() {
		$this->builder->from('authors')->equal('first_name', 'John')->from('books')->leftOuterJoin('authors','authors.id','books.author_id');
		$expected = 'SELECT "books".* FROM "books" LEFT OUTER JOIN "authors" ON "authors"."id" = "books"."author_id" WHERE "authors"."first_name" = :_authors___first_name_';
		$this->assertEquals($expected, $this->builder->select());
	}
	
	function tearDown() {
		unset($this->builder);
	}
}
?>
