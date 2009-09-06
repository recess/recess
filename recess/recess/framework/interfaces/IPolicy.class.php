<?php

interface IPolicy {
	public function preprocess(Request $request);
	
	public function getControllerFor(Request $request, RtNode $routes);
	
	public function getViewFor(Response $response);
}

?>