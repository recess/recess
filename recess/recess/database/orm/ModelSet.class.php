<?php

Library::import('recess.lang.Inflector');
Library::import('recess.database.pdo.PdoDataSet');

class ModelSet extends PdoDataSet {
	
	function __call($name, $arguments) {
		if(empty($arguments)) {
			$descriptor = Model::getDescriptor($this->rowClass);
			$attachedMethod = $descriptor->getAttachedMethod($name);
			if(!$attachedMethod) {
				if(Inflector::isPlural($name)) {
					$attachedMethod = $descriptor->getAttachedMethod(Inflector::toSingular($name));
				}
			}
			if($attachedMethod) {
				$params = $attachedMethod->getParameters();
				if(count($params) === 0) {
					return call_user_func(array($attachedMethod->object,$attachedMethod->method),$this);
				}
			}
		}
		
		throw new RecessException('Method "' . $name . '" does not exist on ModelSet nor is attached to '.$this->rowClass.'.', get_defined_vars());
	}
	
	function update() {
		return $this->source->executeStatement($this->sqlBuilder->useAssignmentsAsConditions(false)->update(), $this->sqlBuilder->getPdoArguments());
	}
	
	function delete($cascade = true) {
		foreach($this as $model) {
			$model->delete($cascade);
		}
	}
}