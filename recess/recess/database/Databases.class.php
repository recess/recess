<?php
/**
 * Registry of Database Sources
 *
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class Databases {
	
	const DEFAULT_SOURCE = 'Default';
	
	static $sources = array();
	static $default = null;
	
	/**
	 * Retrieve a named data source.
	 *
	 * @param string $name
	 * @return PdoDataSource
	 */
    static function getSource($name) {
        if(isset(self::$sources[$name])) {
            return self::$sources[$name];
        }
        else
            if(isset(self::$inactive_sources[$name])) {
                self::addActiveSource($name, new ModelDataSource(self::$inactive_sources[$name]));
                return self::$sources[$name];
            } else return null;
    }

    static function addActiveSource($name, PdoDataSource $source) {
        self::$sources[$name] = $source;
    }

    /**
     * Add a named datasource.
     *
     * @param string $name
     * @param PdoDataSource $source
     */
    static function addSource($name, $source) {
        self::$inactive_sources[$name] = $source;
    }
	
	/**
	 * Get all named data sources.
	 *
	 * @return array of PdoDataSource
	 */
	static function getSources() {
		return self::$sources;
	}
	
	/**
	 * Set the default data source
	 *
	 * @param PdoDataSource $source
	 */
	static function setDefaultSource(PdoDataSource $source) {
		self::$sources[self::DEFAULT_SOURCE] = $source;
	}
	
	/**
	 * Retrieve the default data source
	 *
	 * @return PdoDataSource
	 */
	static function getDefaultSource() {
		return self::$sources[self::DEFAULT_SOURCE];
	}
	
}

?>
