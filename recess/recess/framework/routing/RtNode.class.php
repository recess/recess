<?php
Library::import('recess.framework.routing.RoutingResult');
Library::import('recess.framework.routing.Rt');

/**
 * Routing nodes are used to build a routing tree which maps a requested
 * URI string and HTTP Method to a Route. Example Route paths:
 * 
 * /pages/				-> matches /pages/
 * /pages/$id			-> matches /pages/1 ... (id => 1)
 * /pages/slug/$slug	-> matches /pages/slug/some-slug-here (slug => some-slug-here)
 * 
 * For the purposes of this class a URI path is broken into parts delimited
 * with a '/'. There are two kinds of path parts: static and parametric. Static matches
 * have precedence over parametric matches. For example, if you have the following routes:
 * 
 * (1) /pages/$page_title/
 * (2) /pages/a-page/
 * (3) /pages/$page_title/$id
 * 
 * A request of "/pages/a-page/" will match (2) and the result will not contain an argument.
 * A request of "/pages/b-page/" will match (1) and the result will contain argument ("page_title" => "b_page")
 * A request of "/pages/a-page/1" will match (3) with result arguments ("page_title" => "a_page", "id" => "1")
 * 
 * Note: Because routing trees are serialized and unserialized frequently I am breaking the naming
 * conventions and using short, one-letter member names.
 * 
 * @todo Add regular expression support to the parametric parts (/pages/:id(regexp-goes-here?)/)
 * 
 * @author Kris Jordan <krisjordan@gmail.com> <kris@krisjordan.com>
 * @copyright Copyright (c) 2008, Kris Jordan 
 * @package recess.routing
 */
class RtNode {
	
	protected $c = ''; // (c)ondition
	protected $m; // (m)ethods
	protected $s; // (s)tatic children
	protected $p; // (d)ynamic children
	
	/**
	 * Used to add a route to the routing tree.
	 * 
	 * @param Route The route to add to this routing tree.
	 */
	public function addRoute($app, Route $route, $prefix) {
		if($route->path == '') return;
		
		$route = clone $route;
		
		$route->app = $app;
		
		if($route->path[0] != '/') {
			if(substr($route->path,-1) != '/') {
				$route->path = $prefix . '/' . trim($route->path);
			}else{
				$route->path = $prefix . trim($route->path);
			}
		}
		
		$pathParts = $this->getRevesedPathParts($route->path);
		$this->addRouteRecursively($pathParts, count($pathParts) - 1, $route);
	}
	
	/**
	 * The recursive method powering addRouteFor(Request).
	 * 
	 * @param array Part of a path in reverse order.
	 * @param int Current index of path part array - decrements with each step.
	 * @param Route The route being added
	 * 
	 * @return FindRouteResult
	 */
	private function addRouteRecursively(&$pathParts, $index, $route) {
		// Base Case
		if($index < 0) {
			foreach($route->methods as $method) {
				if(isset($this->m[$method])) {
					Library::import('recess.framework.routing.DuplicateRouteException');
					throw new DuplicateRouteException($method . ' ' . str_replace('//','/',$route->path), $route->fileDefined, $route->lineDefined);
				}
				$this->m[$method] = new Rt($route);
			}
			return;
		}

		$nextPart = $pathParts[$index];
		
		if($nextPart[0] != '$') {
			$childrenArray = &$this->s;
			$nextKey = $nextPart;
			$isParam = false;
		} else {
			$childrenArray = &$this->p;
			$nextKey = substr($nextPart, 1);
			$isParam = true;
		}
		
		if(!isset($childrenArray[$nextKey])) {
			$child = new RtNode();
			if($isParam) {
				$child->c = $nextKey;
			}
			$childrenArray[$nextKey] = $child;
		} else {
			$child = $childrenArray[$nextKey];
		}
		
		$child->addRouteRecursively($pathParts, $index - 1, $route);
	}
	
	/**
	 * Traverses children recursively to find a matching route. First looks
	 * to see if a static (non-parametric, i.e. /this_is_static/ vs. /$this_is_dynamic/)
	 * match exists. If not, we match against dynamic children. We reverse and step backwards
	 * through the array because $index > 0 is less costly than $index < count($parts)
	 * in PHP.
	 * 
	 * @param Request The recess.http.Request object to find a matching route for.
	 * 
	 * @return RoutingResult
	 */
	public function findRouteFor(Request $request) {
		$pathParts = array_reverse($request->resourceParts);
		return $this->findRouteRecursively($pathParts, count($pathParts) - 1, $request->method);
	}
	
	/**
	 * The recursive method powering findRouteFor(Request).
	 * 
	 * @param array Part of a path in reverse order.
	 * @param int Current index of path part array - decrements with each step.
	 * @param string The HTTP METHOD desired for this route.
	 * 
	 * @return RoutingResult
	 */
	private function findRouteRecursively(&$pathParts, $index, &$method) {
		// Base Case - We've gone to the end of the path.
		if($index < 0) {
			$result = new RoutingResult();
			if(!empty($this->m)) { // Leaf, now check HTTP Method Match
				if(isset($this->m[$method])) {
					$result->routeExists = true;
					$result->methodIsSupported = true;
					$result->route = $this->m[$method]->toRoute();
				} else {
					$result->routeExists = true;
					$routes = array_values($this->m);
					$result->route = $routes[0]->toRoute();
					$result->route->methods = array_values($this->m);
					$result->methodIsSupported = false;
					$result->acceptableMethods = array_keys($this->m);
				}
			} else { // Non-leaf, no match
				$result->routeExists = false;
			}
			return $result;
		}
		
		// Find a child for the next part of the path.
		$nextPart = &$pathParts[$index];
		
		$result = new RoutingResult();
		
		// Check for a static match
		if(isset($this->s[$nextPart])) {
			$child = $this->s[$nextPart];
			$result = $child->findRouteRecursively($pathParts, $index - 1, $method);
		}
		
		if(!$result->routeExists && !empty($this->p)) {
			foreach($this->p as $child) {
				if($child->matches($nextPart)) {
					$result = $child->findRouteRecursively($pathParts, $index - 1, $method);
					if($result->routeExists) {
						if($child->c != '') {
							$result->arguments[$child->c] = urldecode($nextPart);
						}
						return $result;
					}
				}
			}
		}
		
		return $result;
	}
	
	public function getStaticPaths() {
		if(is_array($this->s)) return $this->s;
		else return array();
	}
	
	public function getParametricPaths() {
		if(is_array($this->p)) return $this->p;
		else return array();
	}
	
	public function getMethods() {
		if(is_array($this->m)) return $this->m;
		else return array();
	}
	
	public function matches($path) {
		return $path != '';
	}
	
	public static function __set_state($array) {
		$node = new RtNode();
		$node->c = $array['c'];
		$node->m = $array['m'];
		$node->s = $array['s'];
		$node->p = $array['p'];
		return $node;
	}
	
	// Helper Methods
	
	/**
	 * Explodes a string by forward slashes, removes empty first/last node
	 * and finally reverses the array.
	 * @param string Path to be split and reversed.
	 */
	private function getRevesedPathParts($path) {
		$parts = explode('/',$path);
		$count = count($parts);
		$return = array();
		for($i = $count - 1; $i >= 0; $i--) {
			if($parts[$i] !== '') {
				$return[] = $parts[$i];
			}
		}
		return $return;
	}
}
?>