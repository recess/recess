<?php
Library::import('recess.lang.Object');
Library::import('recess.lang.reflection.RecessReflectionClass');
Library::import('recess.lang.Annotation');
Library::import('recess.framework.interfaces.IController');
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
 */
abstract class AbstractController extends Object implements IController {
	
	public abstract function init();
	
	public static function getViewClass($class) {
		return self::getClassDescriptor($class)->viewClass;
	}
	
	public static function getviewsPrefix($class) {
		return self::getClassDescriptor($class)->viewsPrefix;
	}
	
	public static function getRoutes($class) {
		return self::getClassDescriptor($class)->routes;
	}

	protected function ok($viewName = null) {
		Library::import('recess.http.responses.OkResponse');
		$response = new OkResponse($this->request);
		if(isset($viewName)) $response->meta->viewName = $viewName;
		return $response;
	}
	
	protected function conflict($viewName) {
		Library::import('recess.http.responses.ConflictResponse');
		$response = new ConflictResponse($this->request);
		$response->meta->viewName = $viewName;
		return $response;
	}
	
	protected function redirect($redirectUri,$scheme=null) {
		Library::import('recess.http.responses.TemporaryRedirectResponse');
		$response = new TemporaryRedirectResponse($this->request, $this->buildUrl($redirectUri,$scheme));
		return $response;
	}
	
	protected function found($redirectUri,$scheme=null) {
		Library::import('recess.http.responses.FoundResponse');
		$response = new FoundResponse($this->request, $this->buildUrl($redirectUri,$scheme));
		return $response;
	}
	
	protected function moved($redirectUri,$scheme=null) {
		Library::import('recess.http.responses.MovedPermanentlyResponse');
		$response = new MovedPermanentlyResponse($this->request, $this->buildUrl($redirectUri,$scheme));
		return $response;
	}

	protected function buildUrl($uri, $scheme=null) {
		$parts = parse_url($uri);
		if(!is_null($scheme)) {
			$parts['scheme'] = $scheme;
			if(!empty($parts['host'])) $parts['host'] = $_SERVER['SERVER_NAME'];
		}
		$url = '';
		if(!empty($parts['scheme'])) {
			$url .= $parts['scheme'].'://';
			if(!empty($parts['user'])) $url .= $parts['user'] . (empty($parts['pass']) ? '' : $parts['pass']) .'@';
			$url .= $parts['host'];
			if(!empty($parts['port'])) $url .= ':'.$parts['port'];
		}
		$url .= empty($parts['path']) ? '/' : $parts['path'];
		if(!empty($parts['query'])) $url .= '?'.$parts['query'];
		if(!empty($parts['fragment'])) $url .= '#'.$parts['fragment'];
		return $url;
	}
	
	protected function forwardOk($forwardedUri) {
		Library::import('recess.http.responses.ForwardingOkResponse');
		return new ForwardingOkResponse($this->request, $forwardedUri);
	}
	
	protected function forwardNotFound($forwardUri, $flash = '') {
		Library::import('recess.http.responses.ForwardingNotFoundResponse');
		return new ForwardingNotFoundResponse($this->request, $forwardUri, array('flash' => $flash));
	}
	
	protected function created($resourceUri, $contentUri = '') {
		Library::import('recess.http.responses.CreatedResponse');
		if($contentUri == '') $contentUri = $resourceUri;
		return new CreatedResponse($this->request, $resourceUri, $contentUri);
	}
	
	protected function unauthorized($forwardUri, $realm = '') { 
		Library::import('recess.http.responses.ForwardingUnauthorizedResponse');
		return new ForwardingUnauthorizedResponse($this->request, $forwardUri, $realm);
	}

    protected function accepted($resourceUri, $contentUri = '') {
        Library::import('recess.http.responses.AcceptedResponse');
        if($contentUri == '') $contentUri = $resourceUri;
        return new AcceptedResponse($this->request, $resourceUri, $contentUri);
    }

    // XXX
    // Sometimes I don't have an object to forward the creator to the details
    // of, I just want to display something and give the 201 code
    protected function noforwardCreated($viewName = null) {
        Library::import('recess.http.responses.NoForwardCreatedResponse');
        $response = new NoForwardCreatedResponse($this->request);
        if(isset($viewName)) $response->meta->viewName = $viewName;
        return $response;
    }

}

?>
