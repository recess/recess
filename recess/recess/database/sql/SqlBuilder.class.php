<?php
Library::import('recess.database.sql.Criterion');

/**
 * SqlBuilder is used to incrementally compose named-parameter PDO Sql strings
 * using a simple, chainable method call API. This is a naive wrapper that does
 * not gaurantee valid SQL output (i.e. column names using reserved SQL words).
 *
 * 4 classes of SQL strings can be built: INSERT, UPDATE, DELETE, SELECT.
 * This class is intentionally arranged from the low complexity requirements
 * of INSERT to the more complex SELECT.
 *
 * INSERT:        table, column/value assignments
 * UPDATE/DELETE: where conditions
 * SELECT:        order, joins, offset, limit, distinct
 *
 * Example usage:
 *
 * $sqlBuilder->into('table_name')->assign('column', 'value')->insert() ..
 * 		returns "INSERT INTO table_name (column) VALUES (:column)"
 * $sqlBuilder->getPdoArguments() returns array( ':column' => 'value' )
 *
 *
 * XXX - This is implemented as an interface only because it was the
 * quickest way to get things going. There is probably a lot of code that
 * can be reused, so this may do better as an abstract class.
 *
 * @author Kris Jordan <krisjordan@gmail.com>
 * @contributor Luiz Alberto Zaiats
 * @contributor Ryan Day
 *
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */

interface SqlBuilder {
    public function insert();
   	/**
	 * Set the table of focus on a sql statement.
	 *
	 * @param string $table
	 * @return SqlBuilder 
	 */
	public function table($table);
	
	/**
	 * Alias for table (insert into)
	 *
	 * @param string $table
	 * @return SqlBuilder
	 */
	public function into($table);

	/**
	 * Assign a value to a column. Used with inserts and updates.
	 *
	 * @param string $column
	 * @param mixed $value
     * @param string $type
	 * @return SqlBuilder
	 */
	public function assign($column, $value, $type="");
    public function delete();
    public function update();
    public function getPdoArguments();
    public function from($table);
    public function useAssignmentsAsConditions($bool);
    public function select();
    public function getTable();
    public function getCriteria();
}
?>
