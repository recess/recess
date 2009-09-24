<?php
Library::import('recess.framework.forms.Form');

class ModelForm extends Form {
	protected $model = null;
	
	function input($name, $class = '') {
		$this->inputs[$name]->setValue($this->model->$name);
		parent::input($name, $class);
	}
	
	function changeInput($name, $newInput) {
		$current = $this->inputs[$name];
		$newInput .= 'Input';
		$this->inputs[$name] = new $newInput($this->name . '[' . $name . ']');
		$this->inputs[$name]->setValue($current->getValue());
	}
	
	function __construct($name, $values, Model $model = null) {		
		$this->name = $name;
		$this->model = $model;
		
		if($model != null) {			
			$properties = Model::getProperties($model);
			$this->inputs = array();
									
			foreach($properties as $property) {
				$propertyName = $property->name;
				
				$inputName = $this->name . '[' . $propertyName . ']';
				$inputValue = isset($values[$propertyName]) ? $values[$propertyName] : '';

				switch($property->type) {
					case RecessType::STRING: 
					case RecessType::FLOAT:
					case RecessType::INTEGER:
						$this->inputs[$propertyName] = new TextInput($inputName);
						break;
					case RecessType::BOOLEAN:
						$this->inputs[$propertyName] = new BooleanInput($inputName);
						break;
					case RecessType::TEXT:
						$this->inputs[$propertyName] = new TextAreaInput($inputName);
						break;
					case RecessType::BLOB:
						$this->inputs[$propertyName] = new LabelInput($inputName);
						break;
					case RecessType::DATE:
						$this->inputs[$propertyName] = new DateTimeInput($inputName);
						$this->inputs[$propertyName]->showTime = false;
						break;
					case RecessType::DATETIME:
						$this->inputs[$propertyName] = new DateTimeInput($inputName);
						$this->inputs[$propertyName]->showTime = false;
						break;
					case RecessType::TIME:
						$this->inputs[$propertyName] = new DateTimeInput($inputName);
						$this->inputs[$propertyName]->showDate = false;
						break;
					case RecessType::TIMESTAMP:
						$this->inputs[$propertyName] = new DateLabelInput($inputName);
						break;
					case RecessType::POINT:
						$this->inputs[$propertyName] = new PointInput($inputName);
						break;
					case RecessType::BOX:
						$this->inputs[$propertyName] = new BoxInput($inputName);
						break;
					default:
						echo $property->type;
				}
				
				if($property->isPrimaryKey) {
					$this->inputs[$propertyName] = new HiddenInput($propertyName);
				}
				
				if(isset($this->inputs[$propertyName]) && isset($values[$propertyName])) {
					$this->inputs[$propertyName]->setValue($values[$propertyName]);
					$model->$propertyName = $this->inputs[$propertyName]->getValue();
				}
			}
		}
	}
}

?>