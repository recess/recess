<?php
Library::import('recess.framework.AbstractView');

class NativeView extends AbstractView {
	 function getTemplateFor($response) {
		// TODO: Cache in production mode
		$format = $response->request->accepts->format();

		if(is_string($format)) {
			$extension = ".$format.php";
		} else  {
			$extension = '.php';
		}

		$template = $response->meta->viewsPrefix . 
					$response->meta->viewName . 
					$extension;
				
		return $template;
	}

	function canRespondWith(Response $response) {
		// TODO: Cache in production mode
		return Application::active()->findView($this->getTemplateFor($response));
	}

	protected function render(Response $response) {
		$context = $response->data;
		$context['viewsDir'] = $response->meta->app->getViewsDir();
		extract($context);
		include $this->getTemplateFor($response);
	}
}
?>
