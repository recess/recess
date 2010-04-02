<?php
Library::import('recess.database.pdo.IPdoDataSourceProvider');
Library::import('recess.database.pdo.RecessType');

/**
 * MySql Data Source Provider
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class MysqlDataSourceProvider implements IPdoDataSourceProvider {
	protected static $mysqlToRecessMappings;
	protected static $recessToMysqlMappings;
	protected $pdo = null;
	
	/**
	 * Initialize with a reference back to the PDO object.
	 *
	 * @param PDO $pdo
	 */
	function init(PDO $pdo) {
		$this->pdo = $pdo;
	}
	
	/**
	 * List the tables in a data source.
	 * @return array(string) The tables in the data source ordered alphabetically.
	 */
	function getTables() {
		$results = $this->pdo->query('SHOW TABLES');
		
		$tables = array();
		
		foreach($results as $result) {
			$tables[] = $result[0];
		}
		
		sort($tables);
		
		return $tables;
	}
	
	/**
	 * List the column names of a table alphabetically.
	 * @param string $table Table whose columns to list.
	 * @return array(string) Column names sorted alphabetically.
	 */
	function getColumns($table) {
		try {
			$results = $this->pdo->query('SHOW COLUMNS FROM ' . $table . ';');
		} catch(Exception $e) {
			return array();
		}
		
		$columns = array();
		
		foreach($results as $result) {
			$columns[] = $result['Field'];
		}
		
		sort($columns);
		
		return $columns;
	}
	
	/**
	 * Retrieve the a table's RecessTableDescriptor.
	 *
	 * @param string $table Name of table.
	 * @return RecessTableDescriptor
	 */
	function getTableDescriptor($table) {
		Library::import('recess.database.pdo.RecessTableDescriptor');
		$tableDescriptor = new RecessTableDescriptor();
		$tableDescriptor->name = $table;
		
		try {
			$results = $this->pdo->query('SHOW COLUMNS FROM ' . $table . ';');
			$tableDescriptor->tableExists = true;
		} catch (PDOException $e) {
			$tableDescriptor->tableExists = false;
			return $tableDescriptor;
		}
		
		foreach($results as $result) {
			$tableDescriptor->addColumn(
				$result['Field'],
				$this->getRecessType($result['Type']),
				$result['Null'] == 'NO' ? false : true,
				$result['Key'] == 'PRI' ? true : false,
				$result['Default'] == null ? '' : $result['Default'],
				$result['Extra'] == 'auto_increment' ? array('autoincrement' => true) : array());
		}
		
		return $tableDescriptor;
	}
	
	function getRecessType($mysqlType) {
		if(strtolower($mysqlType) == 'tinyint(1)')
			return RecessType::BOOLEAN;
		
		if( ($parenPos = strpos($mysqlType,'(')) !== false ) {
			$mysqlType = substr($mysqlType,0,$parenPos);
		}
		if( ($spacePos = strpos($mysqlType,' '))) {
			$mysqlType = substr($mysqlType(0,$spacePos));
		}
		$mysqlType = strtolower(rtrim($mysqlType));
		
		$mysqlToRecessMappings = MysqlDataSourceProvider::getMysqlToRecessMappings();
		if(isset($mysqlToRecessMappings[$mysqlType])) {
			return $mysqlToRecessMappings[$mysqlType];
		} else {
			return RecessType::STRING;
		}
	}
	
	static function getMysqlToRecessMappings() {
		if(!isset(self::$mysqlToRecessMappings)) {
			self::$mysqlToRecessMappings = array(
				'enum' => RecessType::STRING,
				'binary' => RecessType::STRING,
				'varbinary' => RecessType::STRING,
				'varchar' => RecessType::STRING,
				'char' => RecessType::STRING,
				'national' => RecessType::STRING,
			
				'text' => RecessType::TEXT,
				'tinytext' => RecessType::TEXT,
				'mediumtext' => RecessType::TEXT,
				'longtext' => RecessType::TEXT,
				'set' => RecessType::TEXT,
			
				'blob' => RecessType::BLOB,
				'tinyblob' => RecessType::BLOB,
				'mediumblob' => RecessType::BLOB,
				'longblob' => RecessType::BLOB,
			
				'int' => RecessType::INTEGER,
				'integer' => RecessType::INTEGER,
				'tinyint' => RecessType::INTEGER,
				'smallint' => RecessType::INTEGER,
				'mediumint' => RecessType::INTEGER,
				'bigint' => RecessType::INTEGER,
				'bit' => RecessType::INTEGER,
			
				'bool' => RecessType::BOOLEAN,
				'boolean' => RecessType::BOOLEAN,
			
				'float' => RecessType::FLOAT,
				'double' => RecessType::FLOAT,
				'decimal' => RecessType::STRING,
				'dec' => RecessType::STRING,
			
				'year' => RecessType::INTEGER,
				'date' => RecessType::DATE,
				'datetime' => RecessType::DATETIME,
				'timestamp' => RecessType::TIMESTAMP,
				'time' => RecessType::TIME,
			); 
		}
		return self::$mysqlToRecessMappings;
	}
	
	static function getRecessToMysqlMappings() {
		if(!isset(self::$recessToMysqlMappings)) {
			self::$recessToMysqlMappings = array(
				RecessType::BLOB => 'BLOB',
				RecessType::BOOLEAN => 'TINYINT(1)',
				RecessType::DATE => 'DATE',
				RecessType::DATETIME => 'DATETIME',
				RecessType::FLOAT => 'FLOAT',
				RecessType::INTEGER => 'INTEGER',
				RecessType::STRING => 'VARCHAR(255)',
				RecessType::TEXT => 'TEXT',
				RecessType::TIME => 'TIME',
				RecessType::TIMESTAMP => 'TIMESTAMP',
			);
		}
		return self::$recessToMysqlMappings;
	}
	
	/**
	 * Drop a table from MySql database.
	 *
	 * @param string $table Name of table.
	 */
	function dropTable($table) {
		return $this->pdo->exec('DROP TABLE ' . $table);
	}
	
	/**
	 * Empty a table from MySql database.
	 *
	 * @param string $table Name of table.
	 */
	function emptyTable($table) {
		return $this->pdo->exec('DELETE FROM ' . $table);
	}
	
	/**
	 * Given a Table Definition, return the CREATE TABLE SQL statement
	 * in the MySQL's syntax.
	 *
	 * @param RecessTableDescriptor $tableDescriptor
	 */
	function createTableSql(RecessTableDescriptor $definition) {
		$sql = 'CREATE TABLE ' . $definition->name;
		
		$mappings = MysqlDataSourceProvider::getRecessToMysqlMappings();
		
		$columnSql = null;
		foreach($definition->getColumns() as $column) {
			if(isset($columnSql)) { $columnSql .= ', '; }
			$columnSql .= "\n\t" . $column->name . ' ' . $mappings[$column->type];
			if($column->isPrimaryKey) {
				$columnSql .= ' NOT NULL';
			
				if(isset($column->options['autoincrement'])) {
					$columnSql .= ' AUTO_INCREMENT';
				}
				
				$columnSql .= ' PRIMARY KEY';
			}
		}
		$columnSql .= "\n";
		
		return $sql . ' (' . $columnSql . ')  ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	}
	
	/**
	 * Sanity check and semantic sugar from higher level
	 * representation of table pushed down to the RDBMS
	 * representation of the table.
	 *
	 * @param string $table
	 * @param RecessTableDescriptor $descriptor
	 */
	function cascadeTableDescriptor($table, RecessTableDescriptor $descriptor) {
		$sourceDescriptor = $this->getTableDescriptor($table);
		
		if(!$sourceDescriptor->tableExists) {
			$descriptor->tableExists = false;
			return $descriptor;
		}
		
		$sourceColumns = $sourceDescriptor->getColumns();
		
		$errors = array();
		
		foreach($descriptor->getColumns() as $column) {
			if(isset($sourceColumns[$column->name])) {
				if($column->isPrimaryKey && !$sourceColumns[$column->name]->isPrimaryKey) {
					$errors[] = 'Column "' . $column->name . '" is not the primary key in table ' . $table . '.';
				}
				if($sourceColumns[$column->name]->type != $column->type) {
					$errors[] = 'Column "' . $column->name . '" type "' . $column->type . '" does not match database column type "' . $sourceColumns[$column->name]->type . '".';
				}
			} else {
				$errors[] = 'Column "' . $column->name . '" does not exist in table ' . $table . '.';
			}
		}
		
		if(!empty($errors)) {
			throw new RecessException(implode(' ', $errors), get_defined_vars());
		} else {
			return $sourceDescriptor;
		}
	}
	
	/**
	 * Fetch all returns columns typed as Recess expects:
	 *  i.e. Dates become Unix Time Based and TinyInts are converted to Boolean
	 *
	 * TODO: Refactor this into the query code so that MySql does the type conversion
	 * instead of doing it slow and manually in PHP.
	 * 
	 * @param PDOStatement $statement
	 * @return array fetchAll() of statement
	 */
	function fetchAll(PDOStatement $statement) {
		try {
			$columnCount = $statement->columnCount();
			$manualFetch = false;
			$booleanColumns = array();
			$dateColumns = array();
			$timeColumns = array();
			for($i = 0 ; $i < $columnCount; $i++) {
				$meta = $statement->getColumnMeta($i);
				if(isset($meta['native_type'])) {
					switch($meta['native_type']) {
						case 'TIMESTAMP': case 'DATETIME': case 'DATE':
							$dateColumns[] = $meta['name'];
							break;
						case 'TIME':
							$timeColumns[] = $meta['name'];
							break;
					}
				} else {
					if($meta['len'] == 1) {
						$booleanColumns[] = $meta['name'];
					}
				}
			}
			
			if(	!empty($booleanColumns) || 
				!empty($datetimeColumns) || 
				!empty($dateColumns) || 
				!empty($timeColumns)) {
				$manualFetch = true;
			}
		} catch(PDOException $e) {
			return $statement->fetchAll();
		}
		
		if(!$manualFetch) {
			return $statement->fetchAll();
		} else {
			$results = array();
			while($result = $statement->fetch()) {
				foreach($booleanColumns as $column) {
					$result->$column = $result->$column == 1;
				}
				foreach($dateColumns as $column) {
					$result->$column = strtotime($result->$column);
				}
				foreach($timeColumns as $column) {
					$result->$column = strtotime('1970-01-01 ' . $result->$column);
				}
				$results[] = $result;
			}
			return $results;
		}
	}
	
	function getStatementForBuilder(SqlBuilder $builder, $action, PdoDataSource $source) {
		$criteria = $builder->getCriteria();
		$builderTable = $builder->getTable();
		$tableDescriptors = array();
		
		foreach($criteria as $criterion) {
			$table = $builderTable;
			$column = $criterion->column;
			if(strpos($column,'.') !== false) {
				$parts = explode('.', $column);
				$table = $parts[0];
				$column = $parts[1];
			}
			
			if(!isset($tableDescriptors[$table])) {
				$tableDescriptors[$table] = $source->getTableDescriptor($table)->getColumns();
			}
			
			if(isset($tableDescriptors[$table][$column])) {
				switch($tableDescriptors[$table][$column]->type) {
					case RecessType::DATETIME: case RecessType::TIMESTAMP:
						if(is_int($criterion->value) || is_numeric($criterion->value)) {
							$criterion->value = date('Y-m-d H:i:s', (int)$criterion->value);
						} else {
							$criterion->value = null;
						}
						break;
					case RecessType::DATE:
						$criterion->value = date('Y-m-d', $criterion->value);
						break;
					case RecessType::TIME:
						$criterion->value = date('H:i:s', $criterion->value);
						break;
					case RecessType::BOOLEAN:
						$criterion->value = $criterion->value == true ? 1 : 0;
						break;
					case RecessType::INTEGER:
						if(is_array($criterion->value)) {
							break;
						} else if (is_numeric($criterion->value)) {
							$criterion->value = (int)$criterion->value;
						} else {
							$criterion->value = null;
						}
						break;
					case RecessType::FLOAT:
						if(!is_numeric($criterion->value)) {
							$criterion->value = null;
						}
						break;
				}
			}
		}
		
		$sql = $builder->$action();
		$statement = $source->prepare($sql);
		$arguments = $builder->getPdoArguments();
		foreach($arguments as &$argument) {
			// Begin workaround for PDO's poor numeric binding
			$param = $argument->getQueryParameter();
			if(is_numeric($param)) { continue; }
			if(is_string($param) && strlen($param) > 0 && substr($param,0,1) !== ':') { continue; }
			// End Workaround
			
			// Ignore parameters that aren't used in this $action (i.e. assignments in select)
			if(''===$param || strpos($sql, $param) === false) { continue; } 
			$statement->bindValue($param, $argument->value);
		}
		return $statement;
	}
	
	/**
	 * @param SqlBuilder $builder
	 * @param string $action
	 * @param PdoDataSource $source
	 * @return boolean
	 */
	function executeSqlBuilder(SqlBuilder $builder, $action, PdoDataSource $source) {		
		return $this->getStatementForBuilder($builder, $action, $source)->execute();
	}
}
?>