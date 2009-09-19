<?php

Library::import('recess.database.orm.ModelDataSource');
require_once('recess/database/orm/ModelTest.php');
require_once('recess/database/PdoDsnSettings.php');

class ModelTestPgsql extends ModelTest {
	function getConnection() {
		require('recess/database/PdoDsnSettings.php');
		$this->source = new ModelDataSource($_ENV['dsn.pgsql'][0], $_ENV['dsn.pgsql'][2], $_ENV['dsn.pgsql'][3]);
		$this->source->beginTransaction();
	 	$this->source->exec('DROP TABLE IF EXISTS persons');
	 	$this->source->exec('DROP TABLE IF EXISTS groups');
	 	$this->source->exec('DROP TABLE IF EXISTS groupships');
		$this->source->exec('DROP TABLE IF EXISTS books');
	 	$this->source->exec('DROP TABLE IF EXISTS chapters');
	 	$this->source->exec('DROP TABLE IF EXISTS cars');
		$this->source->exec('DROP TABLE IF EXISTS movies');
		$this->source->exec('DROP TABLE IF EXISTS generas');
		$this->source->exec('DROP TABLE IF EXISTS movies_generas_joins');
	 	$this->source->exec('DROP TABLE IF EXISTS political_partys');
		$this->source->exec('DROP TABLE IF EXISTS books_generas_joins');
		$this->source->exec('DROP TABLE IF EXISTS pages');
		$this->source->exec('DROP SEQUENCE IF EXISTS persons_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS groups_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS groupships_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS books_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS chapters_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS cars_pk_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS movies_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS generas_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS movies_generas_joins_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS political_partys_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS books_generas_joins_id_seq');
		$this->source->exec('DROP SEQUENCE IF EXISTS pages_id_seq');
                $this->source->exec('CREATE SEQUENCE persons_id_seq START 7');
                $this->source->exec('CREATE SEQUENCE groups_id_seq');
                $this->source->exec('CREATE SEQUENCE groupships_id_seq');
                $this->source->exec('CREATE SEQUENCE books_id_seq START 8');
                $this->source->exec('CREATE SEQUENCE chapters_id_seq');
                $this->source->exec('CREATE SEQUENCE cars_pk_seq START 3');
                $this->source->exec('CREATE SEQUENCE movies_id_seq START 4');
                $this->source->exec('CREATE SEQUENCE generas_id_seq START 5');
                $this->source->exec('CREATE SEQUENCE movies_generas_joins_id_seq');
                $this->source->exec('CREATE SEQUENCE political_partys_id_seq');
                $this->source->exec('CREATE SEQUENCE books_generas_joins_id_seq START 9');
                $this->source->exec('CREATE SEQUENCE pages_id_seq');
		$this->source->exec('CREATE TABLE persons (id INTEGER PRIMARY KEY DEFAULT nextval(\'persons_id_seq\'), "firstName" varchar(64), "lastName" varchar(64), age integer, "politicalPartyId" INTEGER, phone varchar(64))');
		$this->source->exec('CREATE TABLE groups (id INTEGER PRIMARY KEY DEFAULT nextval(\'groups_id_seq\'), name varchar(64), description varchar(64))');
		$this->source->exec('CREATE TABLE groupships (id INTEGER PRIMARY KEY DEFAULT nextval(\'groupships_id_seq\'), "groupId" INTEGER, "personId" INTEGER)');
		$this->source->exec('CREATE TABLE books (id INTEGER PRIMARY KEY DEFAULT nextval(\'books_id_seq\'), "authorId" INTEGER, title varchar(64))');
		$this->source->exec('CREATE TABLE chapters (id INTEGER PRIMARY KEY DEFAULT nextval(\'chapters_id_seq\'), "bookId" INTEGER, title varchar(64))');
		$this->source->exec('CREATE TABLE cars (pk INTEGER PRIMARY KEY DEFAULT nextval(\'cars_pk_seq\'), "personId" INTEGER, make varchar(64), "isDriveable" BOOLEAN)');
		$this->source->exec('CREATE TABLE movies (id INTEGER PRIMARY KEY DEFAULT nextval(\'movies_id_seq\'), "authorId" INTEGER, title varchar(64))');
		$this->source->exec('CREATE TABLE generas (id INTEGER PRIMARY KEY DEFAULT nextval(\'generas_id_seq\'), title varchar(64))');
		$this->source->exec('CREATE TABLE books_generas_joins (id INTEGER PRIMARY KEY DEFAULT nextval(\'books_generas_joins_id_seq\'), "bookId" INTEGER, "generaId" INTEGER)');
		$this->source->exec('CREATE TABLE political_partys (id INTEGER PRIMARY KEY DEFAULT nextval(\'political_partys_id_seq\'), party varchar(64))');
		$this->source->exec('CREATE TABLE movies_generas_joins (id INTEGER PRIMARY KEY DEFAULT nextval(\'movies_generas_joins_id_seq\'), "movieId" INTEGER, "generaId" INTEGER)');
		$this->source->exec('CREATE TABLE pages (id INTEGER PRIMARY KEY DEFAULT nextval(\'pages_id_seq\'), "parentId" INTEGER, "Title" varchar(64))');
		$this->source->commit();
		return $this->createDefaultDBConnection($this->source,$_ENV['dsn.pgsql'][1]);
	}
}

?>
