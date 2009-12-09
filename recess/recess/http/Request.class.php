<?php
Library::import('recess.lang.Object');
Library::import('recess.http.Accepts');

/**
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class Request {	
	
	public $accepts;
	
	public $ip;
	public $format;
	public $headers;
	public $resource;
	public $resourceParts = array();
	public $method;
	public $input;
	public $isAjax = false;

	public $get = array();
	public $post = array();
	public $put = array();

	public $cookies;
	
	public $meta; // Key/value store used by Policy to mark-up request
	
	public $username = '';
	public $password = '';
	
	public function __construct() {
		$this->meta = new Meta;
		$this->accepts = new Accepts(array());
	}
	
	public function setResource($resource) {
		if(isset($_ENV['url.base'])) {
			$resource = str_replace($_ENV['url.base'], '/', $resource);
		}
		$this->resource = $resource;
		$this->resourceParts = self::splitResourceString($resource);
	}
	
	public static function splitResourceString($resourceString) {
		$parts = array_filter(explode(Library::pathSeparator, $resourceString), array('Request','resourceFilter'));
		if(!empty($parts)) {
			return array_combine(range(0, count($parts)-1), $parts);
		} else {
			return $parts;	
		}
	}
	
	public static function resourceFilter($input) {
		return trim($input) != '';
	}
	
	public function data($name) {
		if(isset($this->post[$name])) {
			return $this->post[$name];
		} else if (isset($this->put[$name])) {
			return $this->put[$name];
		} else if (isset($this->get[$name])) {
			return $this->get[$name];
		} else {
			return '';
		}
	}
}

class Meta extends Object {}

?>