<?php
require_once 'PHPUnit/Framework.php';
require_once 'recess/database/pdo/PdoDataSetTestMysql.php';
//require_once 'recess/database/pdo/PdoDataSetTestSqlite.php';
require_once 'recess/database/pdo/PdoDataSetTestPgsql.php';
//require_once 'recess/database/pdo/SqlitePdoDataSourceProviderTest.php';

class RecessDatabasePdoAllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('recess.database.pdo');
        
 //       $suite->addTestSuite('SqlitePdoDataSourceProviderTest');
        $suite->addTestSuite('PdoDataSetTestPgsql');
//       	$suite->addTestSuite('PdoDataSetTestSqlite');
 	$suite->addTestSuite('PdoDataSetTestMysql');

        return $suite;
    }
}
?>
