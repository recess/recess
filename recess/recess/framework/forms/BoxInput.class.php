<?php
Library::import('recess.framework.forms.FormInput');

class BoxInput extends FormInput {
	private $swLatitude;
	private $swLongitude;
	private $neLatitude;
	private $neLongitude;
	
	function render() {
		echo '<table><tr><td>Southwest</td><td>Northeast</td></tr>
		<tr>
		  <td>
		    Latitude: <input class="text short" name="' . $this->name . '[swLatitude]" value="' . $this->swLatitude . '">
		  </td>
		  <td>
		    Latitude: <input class="text short" name="' . $this->name . '[neLatitude]" value="' . $this->neLatitude . '">
		  </td>
		</tr>
		<tr>
		  <td>
		    Longitude: <input class="text short" name="' . $this->name . '[swLongitude]" value="' . $this->swLongitude . '">
		  </td>
		  <td>
		    Longitude: <input class="text short" name="' . $this->name . '[neLongitude]" value="' . $this->neLongitude . '">
		  </td>
		</tr>
		</table>';
	}

	function setValue($value) {
		if(is_array($value)) {
			$this->swLatitude = $value['swLatitude'];
			$this->swLongitude = $value['swLongitude'];
			$this->neLatitude = $value['neLatitude'];
			$this->neLongitude = $value['neLongitude'];
		}

		$this->value = "(({$this->swLatitude},{$this->swLongitude}),({$this->neLatitude},{$this->neLongitude}))";
	}
}
?>