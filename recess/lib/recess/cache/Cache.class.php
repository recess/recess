<?php
/**
 * Cache is a low level service offering volatile key-value pair
 * storage. 
 * 
 * @author Kris Jordan
 * @todo Actually write tests for this this (and implement subclasses.)
 *  */
abstract class Cache {
	protected static $reportsTo;

	static function reportsTo(ICacheProvider $cache) {
		if(!$cache instanceof ICacheProvider) {
			$cache = new NoOpCacheProvider();
		}
		
		if(isset(self::$reportsTo)) {
			$temp = self::$reportsTo;
			self::$reportsTo = $cache;
			self::$reportsTo->reportsTo($temp);
		} else {
			self::$reportsTo = $cache;
		}
	}
	
	static function set($key, $value, $duration = 0) {
		return self::$reportsTo->set($key, $value, $duration);
	}
	
	static function get($key) {
		return self::$reportsTo->get($key);
	}
	
	static function delete($key) {
		return self::$reportsTo->delete($key);
	}
	
	static function clear() {
		return self::$reportsTo->clear();
	}
}

/**
 * Common interface for caching subsystems.
 * @author Kris Jordan
 */
interface ICacheProvider {
	/**
	 * Enter description here...
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param unknown_type $duration
	 */
	function set($key, $value, $duration = 0);
	function get($key);
	function delete($key);
	function clear();
}

class NoOpCacheProvider implements ICacheProvider {
	function set($key, $value, $duration = 0) {}
	function get($key) { return false; }
	function delete($key) {}
	function clear() {}
}

Cache::reportsTo(new NoOpCacheProvider());

class ApcCacheProvider implements ICacheProvider {
	protected $reportsTo;

	function reportsTo(ICacheProvider $cache) {
		if(!$cache instanceof ICacheProvider) {
			$cache = new NoOpCacheProvider();
		}
		
		if(isset($this->reportsTo)) {
			$temp = $this->reportsTo;
			$this->reportsTo = $cache;
			$this->reportsTo->reportsTo($temp);
		} else {
			$this->reportsTo = $cache;
		}
	}
	
	function set($key, $value, $duration = 0) {
		apc_store($key, $value, $duration);
		$this->reportsTo->set($key, $value, $duration);
	}
	
	function get($key) {
		$result = apc_fetch($key);
		if($result === false) {
			$result = $this->reportsTo->get($key);
			if($result !== false) {
				$this->set($key, $result);	
			}
		}
		return $result;
	}
	
	function delete($key) {
		apc_delete($key);
		$this->reportsTo->delete($key);
	}
	
	function clear() {
		apc_clear_cache('user');
		$this->reportsTo->clear();
	}
}

class SqliteCacheProvider implements ICacheProvider {
	protected $reportsTo;
	protected $pdo;
	protected $setStatement;
	protected $getStatement;
	protected $deleteStatement;
	protected $time;

	const VALUE = 0;
	const EXPIRE = 1;
	
	function __construct() {
		$this->pdo = new Pdo('sqlite:' . $_ENV['dir.temp'] . 'sqlite-cache.db');
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		try {
			$this->setStatement = $this->pdo->prepare('INSERT OR REPLACE INTO cache (key,value,expire) values (:key,:value,:expire)');
			$this->getStatement = $this->pdo->prepare('SELECT value,expire FROM cache WHERE key = :key');
		} catch(PDOException $e) {
			$this->pdo->exec('CREATE TABLE "cache" ("key" TEXT PRIMARY KEY  NOT NULL , "value" TEXT NOT NULL , "expire" INTEGER NOT NULL)');
			$this->pdo->exec('CREATE INDEX "expiration" ON "cache" ("expire" ASC)');
			$this->setStatement = $this->pdo->prepare('INSERT OR REPLACE INTO cache (key,value,expire) values (:key,:value,:expire)');
			$this->getStatement = $this->pdo->prepare('SELECT value,expire FROM cache WHERE key = :key');
		}
		$this->time = time();
	}
	
	function reportsTo(ICacheProvider $cache) {
		if(!$cache instanceof ICacheProvider) {
			$cache = new NoOpCacheProvider();
		}
		
		if(isset($this->reportsTo)) {
			$temp = $this->reportsTo;
			$this->reportsTo = $cache;
			$this->reportsTo->reportsTo($temp);
		} else {
			$this->reportsTo = $cache;
		}
	}
	
	function set($key, $value, $duration = 0) {
		$this->setStatement->execute(array(':key' => $key, ':value' => var_export($value, true), ':expire' => $duration == 0 ? 0 : time() + $duration));
		$this->reportsTo->set($key, $value, $duration);
	}
	
	function clearStaleEntries() {
		$this->pdo->exec('DELETE FROM cache WHERE expire != 0 AND expire < ' . $this->time);
	}
	
	function get($key) {
		$this->getStatement->execute(array(':key' => $key));
		$result = $this->getStatement->fetch(PDO::FETCH_NUM);
		
		if($result !== false) {
			if($result[self::EXPIRE] == 0 || $result[self::EXPIRE] <= $this->time) {
				echo $key . ' ';
				echo $result[self::VALUE];
				echo '<br /><br />';
				eval('$result = ' . $result[self::VALUE] . ';');
			} else {
				$this->clearStaleEntries();
			}
		} else {
			$result = $this->reportsTo->get($key);
		}
		
		return $result;
	}
	
	function delete($key) {
		if($this->deleteStatement == null) {
			$this->deleteStatement = $this->pdo->prepare('DELETE FROM cache WHERE key = :key OR (expire != 0 AND expire < ' . $this->time . ')');
		}
		$this->deleteStatement->execute(array(':key' => $key));
		$this->reportsTo->delete($key);
	}
	
	function clear() {
		$this->pdo->exec('DELETE FROM cache');
		$this->reportsTo->clear();
	}
}

?>