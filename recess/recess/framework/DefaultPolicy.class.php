<?php
Library::import('recess.framework.controllers.Controller');
Library::import('recess.framework.views.LayoutsView');
Library::import('recess.framework.views.NativeView');
Library::import('recess.framework.views.JsonView');
Library::import('recess.framework.interfaces.IPolicy');
Library::import('recess.framework.http.MimeTypes');

// TODO: Remove this import in 0.3
Library::import('recess.framework.views.RecessView');

class DefaultPolicy implements IPolicy {
	protected $controller;
		
	/**
	 * Used to pre-process a request.
	 * This may involve extracting information and transforming values. 
	 * For example, Transforming the HTTP method from POST to PUT based on a POSTed field.
	 * 
	 * @param	Request The Request to refine.
	 * @return	Request The refined Request.
	 */
	public function preprocess(Request $request) {
		$this->getHttpMethodFromPost($request);

		$this->forceFormatFromResourceString($request);
			
		return $request;
	}
	
	public function getControllerFor(Request $request, RtNode $routes) {
		$routeResult = $routes->findRouteFor($request);
		
		if($routeResult->routeExists) {
			if($routeResult->methodIsSupported) {
				$controller = $this->getControllerFromRouteResult($request, $routeResult);
			} else {
				throw new RecessResponseException('METHOD not supported, supported METHODs are: ' . implode(',', $routeResult->acceptableMethods), ResponseCodes::HTTP_METHOD_NOT_ALLOWED, get_defined_vars());
			}
		} else {
			throw new RecessResponseException('Resource does not exist.', ResponseCodes::HTTP_NOT_FOUND, get_defined_vars());
		}
		
		Application::activate($request->meta->app);
		$this->controller = $controller;
		
		return $controller;
	}
	
	public function getViewFor(Response $response) {
		// TODO: When version 0.3 is released, remove this conditional
		// 		 and break backwards compatibility with versions <= 0.12
		if(!isset($response->meta->respondWith) || empty($response->meta->respondWith)) {
			$view = new $response->meta->viewClass;
			$response->meta->respondWith = array($view);
			if($view != 'LayoutsView') {
				$response->meta->respondWith[] = $view;
			}
			$response->meta->respondWith[] = 'JsonView';
		}
		
		if($response instanceof ForwardingResponse) {
			return new NativeView();
		}
		
		// Here we select a view that can respond in the desired format
		$viewClasses = $response->meta->respondWith;
		$views = array();
		foreach($viewClasses as $viewClass) {
			$views[] = new $viewClass();
		}
		
		$accepts = $response->request->accepts;
		$accepts->resetFormats();
		do {
			$format = $accepts->nextFormat();
			foreach($views as $view) {
				if($view->canRespondWith($response)) {
					return $view;
				}
			}
		} while ($format !== false);
		
		if(isset($response->meta->viewName)) {
			if(isset($response->meta->viewsPrefix)) {
				$view = $response->meta->viewsPrefix . $response->meta->viewName;
			} else {
				$view = $response->meta->viewName;
			}
			throw new RecessResponseException('Unable to provide desired content-type. Does the view "' . $view . '" exist?', ResponseCodes::HTTP_NOT_ACCEPTABLE, get_defined_vars());
		} else {
			throw new RecessResponseException('Unable to provide desired content-type. Does your view exist?', ResponseCodes::HTTP_NOT_ACCEPTABLE, get_defined_vars());
		}
		
	}
	
	/////////////////////////////////////////////////////////////////////////
	// Helper Methods

	const HTTP_METHOD_FIELD = '_METHOD';

	protected function getHttpMethodFromPost(Request $request) {
		if(array_key_exists(self::HTTP_METHOD_FIELD, $request->post)) {
			$request->method = $request->post[self::HTTP_METHOD_FIELD];
			unset($request->post[self::HTTP_METHOD_FIELD]);
			if($request->method == Methods::PUT) {
				$request->put = $request->post;
			}
		}
		return $request;
	}

	protected function forceFormatFromResourceString(Request $request) {
		$lastPartIndex = count($request->resourceParts) - 1;
		if($lastPartIndex < 0) return $request;
		
		$lastPart = $request->resourceParts[$lastPartIndex];
		
		$lastDotPosition = strrpos($lastPart, Library::dotSeparator);
		if($lastDotPosition !== false) {
			$format = substr($lastPart, $lastDotPosition + 1);
			if($format !== '') {
				$mime = MimeTypes::preferredMimeTypeFor($format);
				if($mime !== false) {
					$request->accepts->forceFormat($format);
					$request->format = $format;
					$request->setResource(substr($request->resource, 0, strrpos($request->resource, Library::dotSeparator)));
				}
			}
		}
		
		return $request;
	}

	// @Todo: Worry about the "input" problem. This isn't based on the format
	//			but rather it is based on the content-type of the entity.
	protected function reparameterizeForFormat(Request $request) {
		if($request->format == Formats::JSON) {
			$method = strtolower($request->method);
			$request->$method = json_decode($request->input, true);
		} else if ($request->format == Formats::XML) {
			// TODO: XML reparameterization in request transformer
		}
		return $request;
	}
	
	protected function getControllerFromRouteResult(Request $request, RoutingResult $routeResult) {
		$request->meta->app = $routeResult->route->app;
		$request->meta->controllerMethod = $routeResult->route->function;
		$request->meta->controllerMethodArguments = $routeResult->arguments;
		$request->meta->useAssociativeArguments = true;
		$controllerClass = $routeResult->route->class;
		Library::import($controllerClass);
		$controllerClass = Library::getClassName($controllerClass);
		$controller = new $controllerClass($routeResult->route->app);
		$request->meta->controller = $controller;
		return $controller;
	}

}

?>