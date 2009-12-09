<?php
Library::import('recess.http.Request');
Library::import('recess.http.Methods');
Library::import('recess.http.Accepts');

/**
 * @author Kris Jordan <krisjordan@gmail.com>
 * @contributor Luiz Alberto Zaiats
 * 
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
class Environment {

	public static function getRawRequest() {
		$request = new Request();
		
		$request->method = $_SERVER['REQUEST_METHOD'];
		
		$request->ip = $_SERVER['REMOTE_ADDR'];
		
		$request->format = 'html';
				
		$request->setResource(self::stripQueryString($_SERVER['REQUEST_URI']));
		
		$request->get = $_GET;
		
		$request->post = $_POST;
		
		if(	$request->method == Methods::POST ||
			$request->method == Methods::PUT )
		{
			$request->input = file_get_contents('php://input');

			if($request->method == Methods::POST) {
				$request->post = $_POST;
			} else {
				$request->put = self::getPutParameters($request->input);
			}
		}
		
		$request->headers = self::getHttpRequestHeaders();
		
		$request->accepts = new Accepts($request->headers);
		
		$request->username = @$_SERVER['PHP_AUTH_USER'];
		
		$request->password = @$_SERVER['PHP_AUTH_PW'];
		
		$request->cookies = $_COOKIE;
		
		$request->isAjax =  isset($request->headers['X_REQUESTED_WITH']) 
							&& $request->headers['X_REQUESTED_WITH'] == 'XMLHttpRequest';

		return $request;
	}
	
	private static function	stripQueryString($uri) {
		$questionMarkPosition = strpos($uri, '?');
		if($questionMarkPosition !== false) {
			return substr($uri,0,$questionMarkPosition);
		}
		return $uri;
	}
	
	private static function getHttpRequestHeaders() {
		$lengthOfHTTP_ = 5;
		$httpHeaders = array();
		
		foreach(array_keys($_SERVER) as $key) {
			if(substr($key,0,$lengthOfHTTP_) == 'HTTP_') {
				$httpHeaders[substr($key, $lengthOfHTTP_)] = $_SERVER[$key];
			}
		}
		return $httpHeaders;
	}
	
	private static function getPutParameters($input) {
		$putdata = $input;
		if(function_exists('mb_parse_str')) {
	    	mb_parse_str($putdata, $outputdata);
		} else {
			parse_str($putdata, $outputdata);
		}
    	return $outputdata;
	}
}

?>