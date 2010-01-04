<?php
Library::import('recess.framework.AbstractView');

class EmptyView extends AbstractView {
	
	public function canRespondWith(Response $response) {
		return true;
	}
	
	protected function render(Response $response) {
		// No-op.
	}
}
?>