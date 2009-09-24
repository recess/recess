<?php
Library::import('recess.framework.forms.FormInput');

class PointInput extends FormInput {
	private $latitude;
	private $longitude;
	
	function render() {
		echo 'Latitude: <input class="text short" name="' . $this->name . '[latitude]" value="' . $this->latitude . '">';
		echo 'Longitude: <input class="text short" name="' . $this->name . '[longitude]" value="' . $this->longitude . '">';
	}

	function setValue($value) {
		if(is_array($value)) {
			$this->latitude = $value['latitude'];
			$this->longitude = $value['longitude'];
		}
		
		$this->value = "({$this->latitude},{$this->longitude})";
	}
}
?>