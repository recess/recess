<?php
Library::import('recess.framework.forms.FormInput');
class ModelSelectInput extends FormInput {
	protected $options;
	protected $optionsId;
	
	function __construct($name,$options=null,$optionsId=null) {
		$this->options = $options;
		$this->optionsId = $optionsId;
		return parent::__construct($name);
	}
	
	function render() {
		echo "<select name=\"$this->name\" id=\"$this->name\"", empty($this->class) ? '' : " class=\"$this->class\"", '>';
		echo '<option value="">None</option>';
		foreach($this->options as $opt) {
			echo '<option value="',$opt->{$this->optionsId},'"',$opt->{$this->optionsId}==$this->value ? ' selected' : '', '>', 
				htmlspecialchars($opt->__toString()),
				'</option>';
		}
		echo '</select>'.(empty($this->flash) ? '' : ' <span class="'.$this->class.'">'.$this->flash.'</span>');
	}
	
	function setOptions($options,$optionsId) {
		$this->options = $options;
		$this->optionsId = $optionsId;
		return $this;
	}
}
?>
