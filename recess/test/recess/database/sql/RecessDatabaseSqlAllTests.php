<?php
require_once 'PHPUnit/Framework.php';
require_once 'recess/database/sql/MySqlBuilderTest.php';
require_once 'recess/database/sql/PgSqlBuilderTest.php';
require_once 'recess/database/sql/SelectMySqlBuilderTest.php';
require_once 'recess/database/sql/SelectPgSqlBuilderTest.php';

class RecessDatabaseSqlAllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('recess.database.sql');

        $suite->addTestSuite('MySqlBuilderTest');
        $suite->addTestSuite('PgSqlBuilderTest');
	$suite->addTestSuite('SelectMySqlBuilderTest');
	$suite->addTestSuite('SelectPgSqlBuilderTest');
 		
        return $suite;
    }
}
?>
