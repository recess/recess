<?php
Library::import('recess.http.Response');
Library::import('recess.lang.Object');

/**
 * Renders a Response in a desired format by sending relevant
 * HTTP headers usually followed by a rendered body.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @author Joshua Paine
 * 
 * @abstract 
 */
abstract class AbstractView extends Object {
	protected $response;
	
	public abstract function canRespondWith(Response $response);
	
	/**
	 * The entry point from the Recess with a Response to be rendered.
	 * Delegates the two steps in rendering a view: 1) Send Headers, 2) Render Body
	 *
	 * @param Response $response
	 */
	public final function respondWith(Response $response) {
		if(!headers_sent())
			$this->sendHeadersFor($response);
		
		if(ResponseCodes::canHaveBody($response->code) && !$response instanceof ForwardingResponse) {
			$this->response = $response;
			$this->render($response);
		}
	}
	
	/**
	 * Get the Request this view is being used in response to
	 * 
	 * @return Request 
	 */
	public function getRequest() {
		return $this->response->request;
	}
	
	/**
	 * Get the response
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * Import and (as required) initialize helpers for use in the view.
	 * Helper is the path and name of a class as used by Library::import().
	 * For multiple helpers, pass a single array of helpers or use multiple arguments.
	 * 
	 * @param $helper
	 */
	public function loadHelper($helper) {
		$helpers = is_array($helper) ? $helper : func_get_args();
		foreach($helpers as $helper) {
			Library::import($helper);
			$init = array(Library::getClassName($helper),'init');
			if(is_callable($init)) call_user_func($init, $this); 
		}
	}
		
	/**
	 * Responsible for sending all headers in a Response. Marked final because
	 * all headers should be bundled in Response object.
	 *
	 * @param Response $response
	 * @final
	 */
	protected function sendHeadersFor(Response $response) {
		
		header('HTTP/1.1 ' . ResponseCodes::getMessageForCode($response->code));
		
		$format = $response->request->accepts->format();
		header('Content-Type: ' . MimeTypes::preferredMimeTypeFor($format));
		
		foreach($response->headers as $header) {
			header($header);
		}
		
		foreach($response->getCookies() as $cookie) {
			if($cookie->value == '') {
				setcookie($cookie->name, '', time() - 10000, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
			} else {
				setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httponly);
			}
		}
		
		flush();
		
		// TODO: Determine other headers to send here. Content-Type, Caching, Etags, ...
	}

	/**
	 * Realizes HTTP's body content based on the Response parameter. Responsible
	 * for returning content in the format desired. The render method likely uses
	 * inversion of control which delegates to another method within the view to 
	 * realize the Response.
	 *
	 * @param Response $response
	 * @abstract 
	 */
	protected abstract function render(Response $response);

}
?>