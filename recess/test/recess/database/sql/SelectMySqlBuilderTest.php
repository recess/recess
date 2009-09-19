<?php
Library::import('recess.database.sql.MySqlBuilder');
Library::import('recess.database.sql.Criterion');
/**
 * Unit Tests for recess.database.sql.MySqlBuilder
 * @author Kris Jordan <krisjordan@gmail.com>
 * @see lib/recess/sources/db/sql/SqlBuilder.class.php
 */
class SelectMySqlBuilderTest extends PHPUnit_Framework_TestCase  {
	
	protected $builder = null;
	
	function setUp() {
		$this->builder = new MySqlBuilder();
	}
	
	function testEmpty() {
		try {
			$this->builder->select();
			$this->fail('Should throw.');
		} catch(RecessException $e) { 
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testFrom() {
		$this->builder->from('table');
		$this->assertEquals('SELECT * FROM `table`', $this->builder->select());
	}
	
	function testFromChain() {
		$this->builder->from('table_a')->from('table_b');
		$this->assertEquals('SELECT * FROM `table_b`', $this->builder->select());
	}
	
	function testWhereWithoutFrom() {
		try {
			$this->builder->like('name','value');
			$this->fail('Should throw cannot have where without from exception.');
		} catch(Exception $e) {
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testMultipleWheres() {
		$this->builder->from('table')->greaterThan('height',5.2)->lessThan('age',100);
		$expected = 'SELECT * FROM `table` WHERE `table`.`height` > 5.2 AND `table`.`age` < 100';
		$this->assertEquals($this->builder->select(), $expected);
	}
	
	function testWhereIn() {
		$this->builder->from('table')->in('id', array(1, 2, 3, 4));
		$expected = 'SELECT * FROM `table` WHERE `table`.`id` IN (1,2,3,4)';
		$this->assertEquals($this->builder->select(), $expected);
	}
	
	function testOrderByFail() {
		$this->builder->orderBy('name ASC');
		try {
			$this->builder->select();
			$this->fail('Should throw, cannot have orderby without from.');
		} catch (Exception $e) {
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testOffsetWithoutLimit() {
		$this->builder->from('table')->offset(25);
		try {
			$this->builder->select();
			$this->fail('Should throw, cannot have offset without limit.');
		} catch (Exception $e) {
			$this->assertTrue(true,'Exception Thrown');
		}
	}
	
	function testLimitOffset() {
		$this->builder->from('table')->limit(10)->offset(1);
		$expected = 'SELECT * FROM `table` LIMIT 10 OFFSET 1';
		$this->assertEquals($expected, $this->builder->select());
	}
	
	function testLeftOuterJoin() {
		$this->builder->from('authors')->equal('first_name', 'John')->from('books')->leftOuterJoin('authors','authors.id','books.author_id');
		$expected = 'SELECT `books`.* FROM `books` LEFT OUTER JOIN `authors` ON `authors`.`id` = `books`.`author_id` WHERE `authors`.`first_name` = :authors_first_name';
		$this->assertEquals($expected, $this->builder->select());
	}
	
	function testInsert() {
		$this->builder->into('authors')->assign('first_name','Kris');
		$expected = 'INSERT INTO `authors` (`first_name`) VALUES (:assgn_authors_first_name)';
		$this->assertEquals($expected, $this->builder->insert());
	}
	
	function testUpdate() {
		$this->builder->table('authors')->equal('id',1)->assign('first_name','John');
		$expected = 'UPDATE `authors` SET `first_name` = :assgn_authors_first_name WHERE `authors`.`id` = 1';
		$this->assertEquals($expected, $this->builder->update());
	}
	
	function testDelete() {
		$this->builder->from('authors');
		$expected = 'DELETE FROM `authors`';
		$this->assertEquals($this->builder->delete(), $expected);
	}

	function testDeleteWhere() {
		$this->builder->from('authors')->equal('id',1);
		$expected = 'DELETE FROM `authors` WHERE `authors`.`id` = 1';
		$this->assertEquals($this->builder->delete(), $expected);
	}
	
	function testClone() {
		$this->builder->from('authors')->equal('first_name', 'John')->from('books')->leftOuterJoin('authors','authors.id','books.author_id');
		$anotherBuilder = clone $this->builder;
		$anotherBuilder->leftOuterJoin('authors','authors.id','books.author_id');
		$this->assertNotEquals($this->builder->select(), $anotherBuilder->select());
	}
	
	function testSameTableJoin() {
		$this->builder->from('recess_tools_packages')->equal('id', 8)->innerJoin('recess_tools_packages', 'recess_tools_packages.parentId', 'recess_tools_packages.id');
		$this->assertEquals('SELECT `recess_tools_packages__2`.* FROM `recess_tools_packages` AS `recess_tools_packages__2` INNER JOIN `recess_tools_packages` ON `recess_tools_packages__2`.`parentId` = `recess_tools_packages`.`id` WHERE `recess_tools_packages`.`id` = 8', $this->builder->select());
	}
	
	function tearDown() {
		unset($this->builder);
	}
}
?>
