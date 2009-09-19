<?php
require_once 'PHPUnit/Framework.php';
require_once('recess/database/PdoDsnSettings.php');
require_once 'recess/database/sql/RecessDatabaseSqlAllTests.php';
require_once 'recess/database/pdo/RecessDatabasePdoAllTests.php';
require_once 'recess/database/orm/RecessDatabaseOrmAllTests.php';

class RecessDatabaseAllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('recess.database');

        $suite->addTestSuite(RecessDatabaseSqlAllTests::suite());
 	$suite->addTestSuite(RecessDatabasePdoAllTests::suite());
 	$suite->addTestSuite(RecessDatabaseOrmAllTests::suite());
 		
        return $suite;
    }
}
?>
