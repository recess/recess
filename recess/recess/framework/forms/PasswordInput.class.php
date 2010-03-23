<?php
Library::import('recess.framework.forms.FormInput');
class PasswordInput extends FormInput {
	function render() {
		echo '<input type="password" name="', $this->name, '"', ' id="' . $this->name . '"';
		if($this->class != '') {
			echo ' class="', $this->class, '"';
		}
		
		if($this->value != '') {
			echo ' value="', $this->value, '"';
		}
		echo ' />';
	}
}
?>

