<?php
Library::import('recess.database.pdo.PdoDataSource');
require_once('PHPUnit/Extensions/Database/TestCase.php');

/**
 * Unit Tests for recess.database.pdo.PdoDataSet
 * @author Kris Jordan <krisjordan@gmail.com>
 * @see recess/sources/db/SelectedSet.class.php
 */
abstract class PdoDataSetTest extends PHPUnit_Extensions_Database_TestCase {
	protected $source = null;
	
	function getDataSet() {
		return $this->createXMLDataSet('recess/database/pdo/sample-data.xml');
	}
	
	function tearDown() {
		unset($this->source);
	}
	
	function testSelectAll() {
		$results = $this->source->select()->from('people');
		$this->assertEquals(4, $results->count());
	}
	
	function testSelectCriteria() {
		$results = $this->source->select()->from('people')->lessThan('age', 40);
		$this->assertEquals(3, $results->count());
	}
	
	function testSelectOne() {
		$results = $this->source->select()->from('people')->equal('first_name', 'Kris');
		$this->assertEquals('Jordan', $results[0]->last_name);
	}
	
	function testForEach() {
		$results = $this->source->select()->from('people');
		$firstNames = array('Kris', 'Joel', 'Clay', 'Barack');
		foreach($results as $key => $result) {
			$this->assertEquals($result->first_name, $firstNames[$key]);
		}
	}
	
	function testEmptySelect() {
		$results = $this->source->select();
		try {
			$this->assertTrue($results->isEmpty());
			$this->fail('Selection without specifying a table should fail.');
		} catch(RecessException $e) {
			$this->assertTrue(true, 'Passes');
		}
	}

	function testJoin() {
		$results = $this->source
						->select('people')
						->greaterThan('age', 40)
						->from('books')
						->leftOuterJoin('people','people.id','books.author_id');
		$this->assertEquals(1, $results->count());
		$this->assertEquals('The Audacity of Hope', $results[0]->title);
	}
	
	function testAmbiguousWhere() {
		$results = $this->source
						->select('people')
						->lessThan('age',40)
						->from('books')
						->leftOuterJoin('people','people.id','books.author_id')
						->greaterThan('id',2);
		$this->assertEquals(2, $results->count());		
	}
	
	function testAmbiguousOrderBy() {
		$results = $this->source
						->select('people')
						->lessThan('age',40)
						->from('books')
						->leftOuterJoin('people','people.id','books.author_id')
						->greaterThan('id',2)
						->orderBy('id');
		$this->assertEquals(2, $results->count());	
	}
	
	function testEmpty() {
		$results = $this->source->select('books');
		$this->assertFalse($results->isEmpty());
	}
	
	function testGreaterThanEqualTo() {
		$results = $this->source
						->select('people')
						->greaterThanOrEqualTo('age',23);
		$this->assertEquals(3, $results->count());
	}
	
	function testInnerJoin() {
		$results = $this->source
						->select('people')
						->greaterThan('age',23)
						->from('books')
						->innerJoin('people','people.id','books.author_id');
		$this->assertEquals(1, $results->count());
	}
	
	function testPrefetch() {
		$results = $this->source
						->select('people')
						->equal('age',23)
						->leftOuterJoin('books','books.author_id','people.id')
						->orderBy('last_name');
		$this->assertEquals(2, $results->count());
	}
	
	function testJoinTable() {
		$results = $this->source
						->select('books')
						->innerJoin('books_genera', 'books.id', 'books_genera.book_id')
						->equal('books_genera.genera_id',4);			
		$this->assertEquals(2, $results->count());
	}
	
	function testJoinTableCriteria() {
		$results = $this->source
						->select('genera')
						->equal('id',4)
						->from('books')
						->innerJoin('genera','genera.id','books_genera.genera_id')
						->innerJoin('books_genera','books.id','books_genera.book_id')
						->orderBy('title');
		$this->assertEquals(2, $results->count());	
	}
	
	function testJoinTablePrefetch() {
		$results = $this->source
						->select('genera')
						->equal('title','Comedy Healing')
						->from('books')
						->innerJoin('genera','genera.id','books_genera.genera_id')
						->innerJoin('books_genera','books.id','books_genera.book_id')
						->leftOuterJoin('people', 'people.id','books.author_id')
						->orderBy('title');
						
		$this->assertEquals(2, $results->count());		
	}
	
	function testMultipleJoins() {
		$results = $this->source
						->select('people')
						->equal('first_name','Barack')
						->from('books')
						->innerJoin('people','people.id','books.author_id')
						->from('genera')
						->innerJoin('books','books.id','books_genera.book_id')
						->innerJoin('books_genera','genera.id','books_genera.genera_id');

		$this->assertEquals(1, count($results));
	}
	
	function testClone() {
		$results = $this->source->select('people');
		$count = count($results);
		$results2 = clone $results;
		$results2 = $results2->equal('first_name','Barack');
		$this->assertNotEquals(count($results),count($results2));
	}
	
	
	function testSelectIn() {
		$results = $this->source->select()->from('people')->in('id', array(1, 2, 3, 10));
		$this->assertEquals(3, count($results));
	}
	
}

?>
