<?php
Library::import('recess.database.pdo.IPdoDataSourceProvider');
Library::import('recess.database.pdo.RecessType');
Library::import('recess.database.sql.PgSqlBuilder');
Library::import('recess.database.sql.ISqlSelectOptions');
Library::import('recess.database.sql.ISqlConditions');


/**
 * Pgsql Data Source Provider.
 *
 * PostgreSQL doesn't have an auto_increment option so we must create sequences
 * to support that funtionality. Our sequence format is:
 *
 *      {table_name}_{column_name}_seq
 *
 * This should give us unique sequences to track as we add auto_increment columns
 * and drop tables and columns.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @contributor Ryan Day <ryanday2@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class PgsqlDataSourceProvider implements IPdoDataSourceProvider {
	protected static $postgresqlToRecessMappings;
	protected static $recessToPostgresqlMappings;
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
     * Each DataSourceProvider is responsible for it's own SqlBuilder.
     *
     * @return SqlBuilder The SqlBuilder able to construct correct queries
     */
	function getBuilder() {
		return new PgSqlBuilder();
	}

	/**
	 * List the tables in a data source.
	 * @return array(string) The tables in the data source ordered alphabetically.
	 */
	function getTables() {
		$results = $this->pdo->query('SELECT table_name FROM information_schema.tables where table_schema = \'public\' AND table_type=\'BASE TABLE\' ORDER BY table_name ASC');
		
		$tables = array();
		
		foreach($results as $result) {
			$tables[] = $result[0];
		}
		
		return $tables;
	}
	
	/**
	 * List the column names of a table alphabetically.
	 * @param string $table Table whose columns to list.
	 * @return array(string) Column names sorted alphabetically.
	 */
	function getColumns($table) {
		try {
			$results = $this->pdo->query('SELECT column_name FROM information_schema.columns WHERE table_name = \''. $table . '\'');
		} catch(Exception $e) {
			return array();
		}
		
		$columns = array();
		
		foreach($results as $result) {
			$columns[] = $result['column_name'];
		}
		
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
			$results = $this->pdo->query('SELECT keys.column_name as pri, cols.column_name as Field, 
						data_type as Type, column_default as default, is_nullable as null 
						FROM information_schema.columns cols
							LEFT JOIN information_schema.key_column_usage keys
							ON keys.table_name=cols.table_name
							AND keys.contraint_name = \''. $table . '_pkey\' 
						WHERE cols.table_name = \''. $table . '\'');

			$tableDescriptor->tableExists = true;
		} catch (PDOException $e) {
			$tableDescriptor->tableExists = false;
			return $tableDescriptor;
		}
		
		foreach($results as $result) {
			$tableDescriptor->addColumn(
				$result['field'],
				$this->getRecessType($result['type']),
				$result['null'] == 'NO' ? false : true,
				$result['pri'] == $result['field'] ? true : false,
				$result['default'] == null ? '' : $result['default'],
				substr($result['default'], 7) == "nextval" ? array('autoincrement' => true) : array());
		}

		return $tableDescriptor;
	}
	
	function getRecessType($postgresqlType) {
		if(strtolower($postgresqlType) == 'boolean')
			return RecessType::BOOLEAN;

		if( ($parenPos = strpos($postgresqlType,'(')) !== false ) {
			$postgresqlType = substr($postgresqlType,0,$parenPos);
		}
		if( ($spacePos = strpos($postgresqlType,' '))) {
			$postgresqlType = substr($postgresqlType,0,$spacePos);
		}
		$postgresqlType = strtolower(rtrim($postgresqlType));
		
		$postgresqlToRecessMappings = PgsqlDataSourceProvider::getPostgresqlToRecessMappings();
		if(isset($postgresqlToRecessMappings[$postgresqlType])) {
			return $postgresqlToRecessMappings[$postgresqlType];
		} else {
			return RecessType::STRING;
		}
	}
	
	static function getPostgresqlToRecessMappings() {
		if(!isset(self::$postgresqlToRecessMappings)) {
			self::$postgresqlToRecessMappings = array(
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
				'timestamp' => RecessType::DATETIME,
//				'timestamp' => RecessType::TIMESTAMP,
				'time' => RecessType::TIME,
			
				'point' => RecessType::POINT,
				'box' => RecessType::BOX,
			); 
		}

		return self::$postgresqlToRecessMappings;
	}
	
	static function getRecessToPostgresqlMappings() {
		if(!isset(self::$recessToPostgresqlMappings)) {
			self::$recessToPostgresqlMappings = array(
				RecessType::BLOB => 'BLOB',
				RecessType::BOOLEAN => 'BOOLEAN',
				RecessType::DATE => 'DATE',
				RecessType::DATETIME => 'timestamp',
				RecessType::FLOAT => 'FLOAT',
				RecessType::INTEGER => 'INTEGER',
				RecessType::STRING => 'VARCHAR(255)',
				RecessType::TEXT => 'TEXT',
				RecessType::TIME => 'TIME',
//				RecessType::TIMESTAMP => 'TIMESTAMP',
				RecessType::POINT => 'POINT',
				RecessType::BOX => 'BOX',
			);
		}
		return self::$recessToPostgresqlMappings;
	}
	
	/**
	 * Drop a table from Postgresql database and clear any sequences as well.
     * Sequences must follow the {table_name}_{column_name}_seq format.
	 *
	 * @param string $table Name of table.
	 */
	function dropTable($table) {
		$results = $this->pdo->query('SELECT sequence_name
                                        FROM information_schema.sequences
                                        WHERE sequence_name LIKE \''.$table.'_%\'');

        $this->pdo->exec('DROP TABLE ' . $table);
        foreach($results as $result) {
            $this->pdo->exec('DROP SEQUENCE '.$result['sequence_name']);
        }

	}
	
	/**
	 * Empty a table from Postgresql database.
	 *
	 * @param string $table Name of table.
	 */
	function emptyTable($table) {
		return $this->pdo->exec('DELETE FROM ' . $table);
	}
	
	/**
	 * Given a Table Definition, return the CREATE TABLE SQL statement
	 * in the Postgresql's syntax.
	 *
	 * @param RecessTableDescriptor $tableDescriptor
	 */
	function createTableSql(RecessTableDescriptor $definition) {
		$sql = 'CREATE TABLE "' . $definition->name . '"';

		$mappings = PgsqlDataSourceProvider::getRecessToPostgresqlMappings();
		
		$columnSql = null;
		foreach($definition->getColumns() as $column) {
			if(isset($columnSql)) { $columnSql .= ', '; }
			$columnSql .= "\n\t \"" . $column->name . '" ' . $mappings[$column->type];

            /*  Postgres doesn't have the AUTO_INCREMENT option, so we have to use the sequence */
            if(isset($column->options['autoincrement'])) {
                $columnSql .= ' NOT NULL';
                $this->pdo->exec('CREATE SEQUENCE ' . $definition->name . '_'.$column->name.'_seq');
                $columnSql .= ' DEFAULT nextval(\'' . $definition->name . '_'.$column->name.'_seq\') ';
            }
			if($column->isPrimaryKey) {
				$columnSql .= ' PRIMARY KEY ';
			}
		}
		$columnSql .= "\n";
		return $sql . ' (' . $columnSql . ')';
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
	 * TODO: Refactor this into the query code so that Postgresql does the type conversion
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
			$pointColumns = array();
			$boxColumns = array();
			for($i = 0 ; $i < $columnCount; $i++) {
				$meta = $statement->getColumnMeta($i);
				if(isset($meta['native_type'])) {
					switch($meta['native_type']) {
						case 'timestamp': case 'date':
							$dateColumns[] = $meta['name'];
							break;
						case 'time':
							$timeColumns[] = $meta['name'];
							break;
						case 'point':
							$pointColumns[] = $meta['name'];
							break;
						 case 'box':
						 	$boxColumns[] = $meta['name'];
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
				!empty($timeColumns) ||
				!empty($pointColumns) ||
				!empty($boxColumns)) {
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
					$result->$column = strtotime($result->$column);
				}
				foreach($pointColumns as $column) {
					$matches = array();
					preg_match('/^\((.*),(.*)\)$/', $result->$column, $matches);
					if( count($matches) == 3 ) {
						$result->$column = array('latlong'=>$matches[0],
													'latitude'=>$matches[1],
													'longitude'=>$matches[2]);
					}
				}
				foreach($boxColumns as $column) {
					$matches = array();
					preg_match('/^\((.*),(.*)\),\((.*),(.*)\)$/', $result->$column, $matches);
					if(count($matches) == 5) {
						$result->$column = array('box'=>$matches[0],
													'neLatitude'=>$matches[1],
													'neLongitude'=>$matches[2],
													'swLatitude'=>$matches[3],
													'swLongitude'=>$matches[4],);
					}
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
            /* Our column has already been escaped, so remove those quotes
             * to do the type check.
             */
            $column = trim($column, '"');
			if(isset($tableDescriptors[$table][$column])) {
				switch($tableDescriptors[$table][$column]->type) {
					case RecessType::DATETIME: case RecessType::TIMESTAMP:
						if(is_int($criterion->value)) {
							$criterion->value = date('Y-m-d H:i:s', $criterion->value);
						} else {
							$criterion->value = $criterion->value = null;
						}
						break;
					case RecessType::DATE:
						$criterion->value = date('Y-m-d', $criterion->value);
						break;
					case RecessType::TIME:
						$criterion->value = date('H:i:s', $criterion->value);
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
