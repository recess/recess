<?php
Library::import('recess.framework.forms.FormInput');
Library::import('recess.framework.forms.TextAreaInput');
Library::import('recess.framework.forms.DateTimeInput');
Library::import('recess.framework.forms.TextInput');
Library::import('recess.framework.forms.LabelInput');
Library::import('recess.framework.forms.DateLabelInput');
Library::import('recess.framework.forms.BooleanInput');
Library::import('recess.framework.forms.HiddenInput');
Library::import('recess.framework.forms.ModelSelectInput');

class Form {
	protected $name;
	
	public $method;
	public $action;
	public $flash;
	
	function __construct($name) {
		$this->name = $name;
	}
	
	public $hasErrors = false;
	public $inputs = array();
	
	function __get($name) {
		if(isset($this->inputs[$name])) {
			return $this->inputs[$name];
		} else {
			return '';
		}
	}
	
//	function __set($name, $value) {
//		if(isset($this->inputs[$name])) {
//			$this->inputs[$name]->value = $value;
//		}
//	}
	
	function to($method, $action) {
		$this->method = $method;
		$this->action = $action;
	}
	
	function begin() {
		if($this->method == Methods::DELETE || $this->method == Methods::PUT) {
			echo '<form method="POST" action="' . $this->action . '">';
			echo '<input type="hidden" name="_METHOD" value="' . $this->method . '" />';
		} else {
			echo '<form method="' . $this->method . '" action="' . $this->action . '">';
		}
	}
	
	function input($name, $class = '') {
		if($class != '') {
			$this->inputs[$name]->class = $class;
		}
		$this->inputs[$name]->render();
	}
	
	function changeInput($name, $newInput) {
		$current = $this->inputs[$name];
		$newInput .= 'Input';
		$this->inputs[$name] = new $newInput($name);
		$this->inputs[$name]->setValue($current->getValue());
    if(!empty($current->class)) {
      $this->inputs[$name]->class = $current->class;
    }
    if(!empty($current->flash)) {
      $this->inputs[$name]->flash = $current->flash;
    }
	}
	
	function fill(array $keyValues) {
		foreach($this->inputs as $key => $value) {
			if(isset($keyValues[$key])) {
				$this->inputs[$key]->setValue($keyValues[$key]);
			}
		}
	}
	
	function assertNotEmpty($inputName) {
		if(isset($this->inputs[$inputName]) && $this->inputs[$inputName]->getValue() != '') {
			return true;
		} else {
			$this->inputs[$inputName]->class = 'highlight';
			$this->inputs[$inputName]->flash = 'Required.';
			$this->hasErrors = true;
			return false;
		}
	}
	
	function hasErrors() {
		return $this->hasErrors;
	}
	
	function end() {
		echo '</form>';
	}
}
?>
