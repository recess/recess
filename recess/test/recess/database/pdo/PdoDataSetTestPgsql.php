<?php
Library::import('recess.database.pdo.PdoDataSource');
require_once('recess/database/pdo/PdoDataSetTest.php');
/**
 * Unit Tests for recess.database.pdo.PdoDataSet
 * @author Ryan Day <ryanday2@gmail.com>
 * @see recess/sources/db/SelectedSet.class.php
 */
class PdoDataSetTestPgsql extends PdoDataSetTest {
	
	function getConnection() {
		require('recess/database/PdoDsnSettings.php');
		$this->source = new PdoDataSource($_ENV['dsn.pgsql'][0], $_ENV['dsn.pgsql'][2], $_ENV['dsn.pgsql'][3]);
		$this->source->exec('DROP TABLE IF EXISTS people');
		$this->source->exec('DROP TABLE IF EXISTS books');
		$this->source->exec('DROP TABLE IF EXISTS genera');
		$this->source->exec('DROP TABLE IF EXISTS books_genera');
		$this->source->exec('DROP SEQUENCE IF EXISTS people1_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS books1_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS genera1_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS books1_genera_id_seq');
		$this->source->exec('CREATE SEQUENCE people1_id_seq');
		$this->source->exec('CREATE SEQUENCE books1_id_seq');
		$this->source->exec('CREATE SEQUENCE genera1_id_seq');
		$this->source->exec('CREATE SEQUENCE books1_genera_id_seq');
		$this->source->exec("CREATE TABLE people (id INTEGER PRIMARY KEY DEFAULT nextval('people1_id_seq'), first_name varchar(64), last_name varchar(64), age INTEGER)");
		$this->source->exec("CREATE TABLE books (id INTEGER PRIMARY KEY DEFAULT nextval('books1_id_seq'), author_id INTEGER, title varchar(64))");
		$this->source->exec("CREATE TABLE genera (id INTEGER PRIMARY KEY DEFAULT nextval('genera1_id_seq'), title varchar(64))");
		$this->source->exec("CREATE TABLE books_genera (id INTEGER PRIMARY KEY DEFAULT nextval('books1_genera_id_seq'), book_id INTEGER, genera_id INTEGER)");
		return $this->createDefaultDBConnection($this->source,$_ENV['dsn.pgsql'][1]);
	}
	
}

?>
