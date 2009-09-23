<?php
require_once 'PHPUnit/Framework.php';
require_once 'recess/database/orm/ModelTestMysql.php';
require_once 'recess/database/orm/ModelTestPgsql.php';
require_once 'recess/database/orm/ModelTestSqlite.php';

class RecessDatabaseOrmAllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('recess.database.orm');
        
        $suite->addTestSuite('ModelTestMysql');
        $suite->addTestSuite('ModelTestPgsql');
        $suite->addTestSuite('ModelTestSqlite');

        return $suite;
    }
}
?>
