<?php
Library::import('recess.database.pdo.IPdoDataSourceProvider');

/**
 * Sqlite 3 Data Source Provider
 * @author Kris Jordan <krisjordan@gmail.com>
 */
class SqliteDataSourceProvider implements IPdoDataSourceProvider {
	const SQLITE_TABLE_PREFIX = 'sqlite_';
	
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
		$results = $this->pdo->query('SELECT tbl_name FROM sqlite_master WHERE type="table"');
		
		$tables = array();
		
		foreach($results as $result) {
			if(substr($result[0],0,strlen(self::SQLITE_TABLE_PREFIX)) != self::SQLITE_TABLE_PREFIX)
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
		$results = $this->pdo->query('PRAGMA table_info("' . $table . '");');
		
		$columns = array();
		
		foreach($results as $result) {
			$columns[] = $result['name'];
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
		$results = $this->pdo->query('PRAGMA table_info("' . $table . '");');
		
		$tableSql = $this->pdo->query('SELECT sql FROM sqlite_master WHERE type="table" AND name = "' . addslashes($table) . '"')->fetch();
		$tableSql = $tableSql['sql'];
		
		$columns = array();
		
		$tableDescriptor = new RecessTableDescriptor();
		
		foreach($results as $result) {
			$tableDescriptor->addColumn(
				$result['name'],
				SqliteDataSourceProvider::getRecessType($result['type']),
				$result['notnull'] == 0 ? true : false,
				$result['pk'] == 1 ? true : false,
				$result['dflt_value'] == null ? '' : $result['dflt_value'],
				strpos(	$tableSql, 
						$result['name'] . ' INTEGER PRIMARY KEY AUTOINCREMENT'
					  ) !== false 
					  ? array('autoincrement'=>true) : array()
				);
		}
		
		return $tableDescriptor;
	}
	
	/**
	 * Sanity check and semantic sugar from higher level
	 * representation of table pushed down to the RDBMS
	 * representation of the table.
	 *
	 * @param string $table
	 * @param RecessTableDescriptor $descriptor
	 * @return RecessTableDescriptor
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
				$sourceColumns[$column->name]->type = $column->type;
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
	
	
	static function getRecessType($sqliteType) {
		$recessType = $sqliteType;
		if($recessType == 'DATETIME') {
			$recessType = 'DateTime';
		} else {
			$recessType = ucfirst(strtolower($recessType));
		}
		if(!in_array($recessType, RecessType::all())) {
			$recessType = RecessType::TEXT;
		}
		return $recessType;
	}
	
	/**
	 * Drop a table from SQLite database.
	 *
	 * @param string $table Name of table.
	 */
	function dropTable($table) {
		return $this->pdo->exec('DROP TABLE ' . $table);
	}
	
	/**
	 * Empty a table from SQLite database.
	 *
	 * @param string $table Name of table.
	 */
	function emptyTable($table) {
		return $this->pdo->exec('DELETE FROM ' . $table);
	}
	
	/**
	 * Given a Table Definition, return the CREATE TABLE SQL statement
	 * in the Sqlite's syntax.
	 *
	 * @param RecessTableDescriptor $tableDescriptor
	 */
	function createTableSql(RecessTableDescriptor $definition) {
		$sql = 'CREATE TABLE ' . $definition->name;
		
		$columnSql = null;
		foreach($definition->getColumns() as $column) {
			if(isset($columnSql)) { $columnSql .= ', '; }
			$columnSql .= "\n\t" . $column->name . ' ' . strtoupper($column->type);
			if($column->isPrimaryKey) {
				$columnSql .= ' PRIMARY KEY';
				if(isset($column->options['autoincrement'])) {
					$columnSql .= ' AUTOINCREMENT';
				}
			}
		}
		$columnSql .= "\n";
		
		return $sql . ' (' . $columnSql . ')';
	}
	
	function fetchAll(PDOStatement $statement) {
		try {
			$columnCount = $statement->columnCount();
			$manualFetch = false;
			$booleanColumns = array();
			for($i = 0 ; $i < $columnCount; $i++) {
				$meta = $statement->getColumnMeta($i);
				if(isset($meta['sqlite:decl_type']) && $meta['sqlite:decl_type'] == 'BOOLEAN') {
					$manualFetch = true;
					$booleanColumns[] = $meta['name'];
				}
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
				$results[] = $result;
			}
			return $results;
		}
	}
	
	function getStatementForBuilder(SqlBuilder $builder, $action, PdoDataSource $source) {
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
	
	function executeSqlBuilder(SqlBuilder $builder, $action, PdoDataSource $source) {		
		return $this->getStatementForBuilder($builder, $action, $source)->execute();
	}
}

class SqliteType {
	const Text = 'TEXT';
	const Integer = 'INTEGER';
	const Real = 'REAL';
	const Blob = 'BLOB';
}
?>