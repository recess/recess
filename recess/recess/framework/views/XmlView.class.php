<?php
Library::import('recess.framework.AbstractView');

class XmlView extends AbstractView {
	
	public function canRespondWith(Response $response) {
		return 'xml' === $response->request->accepts->format();
	}
	
	protected function render(Response $response) {
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');

		$response = clone $response;
		foreach($response->data as $key => $value) {
			if($value instanceof ModelSet) {
				$response->data[$key] = $value->toArray();
			}
			if($value instanceof Form) {
				unset($response->data[$key]);
			}
			if(substr($key,0,1) == '_') {
				unset($response->data[$key]);
			}
		}
		if(isset($response->data['application'])) unset ($response->data['application']);
		if(isset($response->data['controller'])) unset ($response->data['controller']);

		foreach($response->data as $key=>$value) {
			$xml->startElement($key);
			$this->createXML($xml, $value);
			$xml->endElement();
		}
		
		echo $xml->outputMemory(true);
	}
	
	private function createXML(&$xml, $value) {
		foreach($value as $k=>$v) {
			if(is_array($v)) {
				$xml->startElement($k);
				$this->createXML($xml, $v);
				$xml->endElement();
			} else {
				$xml->writeElement($k, $v);
			}
		}
	}
}
?>