<?php
Library::import('recess.framework.AbstractController');

Library::import('recess.lang.Annotation');
Library::import('recess.framework.controllers.annotations.ViewAnnotation');
Library::import('recess.framework.controllers.annotations.RouteAnnotation');
Library::import('recess.framework.controllers.annotations.RoutesPrefixAnnotation');
Library::import('recess.framework.controllers.annotations.PrefixAnnotation');
Library::import('recess.framework.controllers.annotations.RespondsWithAnnotation');

/**
 * The controller is responsible for interpretting a preprocessed Request,
 * performing some action in response to the Request (usually CRUDS), and
 * returning a Response which contains relevant state for a view to render
 * the Response.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @author Joshua Paine
 */
abstract class Controller extends AbstractController {
	
	const CLASSNAME = 'Controller';
	
	/** @var Request */
	protected $request;
	
	protected $headers;
	
	/** @var Application */
	protected $application;
		
	public function __construct($application = null) {
		$this->application = $application;
	}
	
	public function init() { }

	protected static function initClassDescriptor($class) {
		$descriptor = new ClassDescriptor();
		$descriptor->routes = array();
		$descriptor->methodUrls = array();
		$descriptor->routesPrefix = '';
		$descriptor->viewClass = 'LayoutsView';
		$descriptor->viewsPrefix = '';
		$descriptor->respondWith = array();
		return $descriptor;
	}

	protected static function shapeDescriptorWithMethod($class, $method, $descriptor, $annotations) {
		$unreachableMethods = array('serve','urlTo','__call','__construct','init','application');

		if(in_array($method->getName(), $unreachableMethods)) return $descriptor;
		
		if(	empty($annotations) && 
				$method->isPublic() && 
				!$method->isStatic()
			   ) {
			   	$parameters = $method->getParameters();
			   	$parameterNames = array();
			   	foreach($parameters as $parameter) {
			   		$parameterNames[] = '$' . $parameter->getName();
			   	}
			   	if(!empty($parameterNames)) {
			   		$parameterPath = '/' . implode('/',$parameterNames);
			   	} else {
			   		$parameterPath = '';
			   	}
				// Default Routing for Public Methods Without Annotations
				$descriptor->routes[] = 
					new Route(	$class, 
								$method->getName(), 
								Methods::GET, 
								$descriptor->routesPrefix . $method->getName() . $parameterPath);
		}
		return $descriptor;
	}

	/**
	 * urlTo is a helper method that returns the url to a controller method.
	 * Examples:
	 * 	$controller->urlTo('someMethod'); => /route/to/someMethod/
	 *  $controller->urlTo('someMethodOneParameter', 'param1');  =>  /route/to/someMethodOneParam/param1
	 *  $controller->urlTo('OtherController::otherMethod'); => Returns the route to another controller's method
	 *  
	 * Thanks to Joshua Paine for improving the API of urlTo!
	 * 
	 * @param $methodName
	 * @return string The url linking to controller method.
	 */
	public function urlTo($methodName) {
		$args = func_get_args();
		
		// First check to see if this is a urlTo on another Controller Class
		if(strpos($methodName,'::') !== false) {
		    return call_user_func_array(array($this->application,'urlTo'),$args);
		}		
    
    // Check to see if this urlTo contains args in the methodName
		// Ignores keys, assuming params are in the proper order.
		// Ex. $controller->urlTo("details?id=43")
		if(strpos($methodName,'?') !== false) {
		    list($methodName, $params) = explode('?', $methodName, 2);
			$args = empty($args)? explode('&',$params) : $args;
			foreach($args as $i => $arg) {
				$val = strpos($arg, '=') !== false ? substr($arg,strrpos($arg, '=')+1) : $arg;
				$args[$i] = $val;
			}
		}
		
		array_shift($args);
		$descriptor = Controller::getClassDescriptor($this);
		if(isset($descriptor->methodUrls[$methodName])) {
			$url = $descriptor->methodUrls[$methodName];
			if($url[0] != '/') {
				$url = $this->application->routingPrefix . $url;
			} else {
				$url = substr($url, 1);
			}
			
			if(!empty($args)) {
				$reflectedMethod = new ReflectionMethod($this, $methodName);
				$parameters = $reflectedMethod->getParameters();
				
				if(count($parameters) < count($args)) {
					throw new RecessException('urlTo(\'' . $methodName . '\') called with ' . count($args) . ' arguments, method "' . $methodName . '" takes ' . count($parameters) . '.', get_defined_vars());
				}
				
				$i = 0;
				$params = array();
				foreach($parameters as $parameter) {
					if(isset($args[$i])) $params[] = '$' . $parameter->getName();
					$i++;
				}
				$url = str_replace($params, $args, $url);
			}
			
			if(strpos($url, '$') !== false) { 
				throw new RecessException('Missing arguments in urlTo(' . $methodName . '). Provide values for missing arguments: ' . $url, get_defined_vars());
			}
			return trim($_ENV['url.base'] . $url);
		} else {
			throw new RecessException('No url for method ' . $methodName . ' exists.', get_defined_vars());
		}
	}
	
	/**
	 * The serve method is where inversion of control occurs which delegates
	 * control to another method in the controller.
	 * 
	 * The method name and arguments should have been extracted in the 
	 * preprocessing step. Here we ensure that the method exists and that all 
	 * required parameters are provided as arguments from the request string.
	 * 
	 * Call the method and return its response.
	 *
	 * @param DefaultRequest $request The HTTP request being served.
	 * 
	 * !Wrappable serve
	 */
	function wrappedServe(Request $request) {		
		$this->request = $request;
		
		$shortWiredResponse = $this->init();
		if($shortWiredResponse instanceof Response) {
				$shortWiredResponse->meta->viewClass = 'LayoutsView';
				$shortWiredResponse->meta->viewsPrefix = '';
				return $shortWiredResponse;
		}
		
		$methodName = $request->meta->controllerMethod;
		$methodArguments = $request->meta->controllerMethodArguments;
		$useAssociativeArguments = $request->meta->useAssociativeArguments;
		
		// Does method exist? Do arguments match?
		if (method_exists($this, $methodName)) {
			$method = new ReflectionMethod($this, $methodName);
			$parameters = $method->getParameters();
			
			$callArguments = array();
			try {
				if($useAssociativeArguments) {
					$callArguments = $this->getCallArgumentsAssociative($parameters, $methodArguments);
				} else {
					$callArguments = $this->getCallArgumentsSequential($parameters, $methodArguments);
				}
			} catch(RecessException $e) {
				throw new RecessException('Error calling method "' . $methodName . '" in "' . get_class($this) . '". ' . $e->getMessage(), array());
			}
			
			$response = $method->invokeArgs($this, $callArguments);
		} else {
			throw new RecessException('Error calling method "' . $methodName . '" in "' . get_class($this) . '". Method does not exist.', array());
		}

		if(!$response instanceof Response) {
			Library::import('recess.http.responses.OkResponse');
			$response = new OkResponse($this->request);
		}
		
		$descriptor = self::getClassDescriptor($this);
		if(!$response instanceof ForwardingResponse && 
		   !isset($response->meta->viewName)) $response->meta->viewName = $methodName;
		// TODO: Remove this deprecated viewClass at 0.3
		$response->meta->viewClass = $descriptor->viewClass;
		$response->meta->viewsPrefix = $descriptor->viewsPrefix;
		
		$response->meta->respondWith = $descriptor->respondWith;
		if(empty($response->data)) $response->data = get_object_vars($this);

		if(is_array($this->headers)) { foreach($this->headers as $header) $response->addHeader($header); }
		
		if(is_array($response->data)) {
			$response->data['controller'] = $this;
			unset($response->data['request']);
			unset($response->data['headers']);
		}
		return $response;
	}

	private function getCallArgumentsAssociative($parameters, $arguments) {
		$callArgs = array();
		foreach($parameters as $parameter) {
			if(!isset($arguments[$parameter->getName()])) {
				if(!$parameter->isOptional()) {
					throw new RecessException('Expects ' . count($parameters) . ' arguments, given ' . count($arguments) . ' and missing required parameter: "' . $parameter->name . '"', array());
				}
			} else {
				$callArgs[] = $arguments[$parameter->getName()];
			}
		}
		return $callArgs;
	}

	private function getCallArgumentsSequential($parameters, $arguments) {
		$callArgs = array();
		$parameterCount = count($parameters);
		for($i = 0; $i < $parameterCount; $i++) {
			if(!isset($arguments[$i])) {
				if(!$parameters[$i]->isOptional()) {
					throw new RecessException('Expects ' . count($parameters) . ' arguments, given ' . count($arguments) . ' and missing required parameter # ' . ($i + 1) . ' named: "' . $parameters[$i]->name . '"', array());
				}
			} else {
				$callArgs[] = $arguments[$i];
			}
		}
		return $callArgs;
	}

	public function application() {
		return $this->application;
	}
}

?>