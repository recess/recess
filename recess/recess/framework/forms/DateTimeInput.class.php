<?php
Library::import('recess.framework.forms.FormInput');
class DateTimeInput extends FormInput {
	
	public $showDate = true;
	public $showTime = true;
	
	protected static $months = array('Jan',
									 'Feb', 
									 'Mar',
									 'Apr',
									 'May',
									 'June',
									 'July', 
									 'Aug', 
									 'Sept', 
									 'Oct', 
									 'Nov',
									 'Dec');
									 
	protected static $meridiems = array(
									 self::AM,
									 self::PM
										);
	const MONTH = 'month';
	const DAY = 'day';
	const YEAR = 'year';
	const HOUR = 'hour';
	const MINUTE = 'minute';
	const MERIDIEM = 'meridiem';
	const AM = 'am';
	const PM = 'pm';
	const PM_HOURS = 12;
	
	function getValue() {
		return $this->value;
	}
	
	function getValueOrZero($array, $key) {
		if(isset($array[$key]) && $array[$key] != '')
			return $array[$key];
		else
			return 0;
	}
	
	function setValue($value) {
		if(is_array($value)) {
			$month = $this->getValueOrZero($value, self::MONTH);
			$day = $this->getValueOrZero($value, self::DAY);
			$year = $this->getValueOrZero($value, self::YEAR);
			$hour = $this->getValueOrZero($value, self::HOUR);
			$minute = $this->getValueOrZero($value, self::MINUTE);
			$meridiem = $this->getValueOrZero($value, self::MERIDIEM);
			
			if($meridiem == self::PM) {
				$hour += self::PM_HOURS;
			}
			
			$this->value = mktime($hour,$minute,1,$month,$day,$year);
		} else {
			$this->value = $value;
		}
	}
	
	function render() {
		
		if($this->showDate) {
			$this->printMonthInput();
			$this->printDayInput();
			$this->printYearInput();
      echo ' '; // make sure there's spacing to the time
		}
		
		if($this->showTime) {
			$this->printHourInput();
			$this->printMinuteInput();
			$this->printmeridiemInput();
		}
    if(!empty($this->flash)) {
        echo ' <span class="'.$this->class.'">'.$this->flash.'</span>';
    }	
	}
	
	function printMonthInput() {
		$this->printSelect($this->name . '[' . self::MONTH . ']', self::$months, date('n', $this->value));
	}
	
	function printDayInput() {
		$this->printSelect($this->name . '[' . self::DAY . ']', range(1,31), date('j', $this->value));
	}
	
	function printYearInput() {
		$this->printText($this->name . '[' . self::YEAR . ']', date('Y', $this->value));
	}
	
	function printHourInput() {
		$this->printSelect($this->name . '[' . self::HOUR . ']', range(1,12), date('g', $this->value));
	}
	
	function printMinuteInput() {
		$this->printSelect($this->name . '[' . self::MINUTE . ']', range(0,60,15), (int)date('i', $this->value));
	}
	
	function printMeridiemInput() {
		$this->printSelect($this->name . '[' . self::MERIDIEM . ']', self::$meridiems, date('a', $this->value));
	}
	
	function printSelect($name, $values, $selected) {
    echo '<select name="', $name, '"', empty($this->class) ? '' : " class=\"$this->class\"", '>';
		
		foreach($values as $key => $value) {
			$key++;
			echo '<option value="', $key, '"';
			if($key == $selected) {
				echo ' selected="selected"';
			}
			echo '>', $value, '</option>', "\n";
		}
		
		echo '</select>';
	}
	
	function printText($name, $value = '') {
    echo '<input class="text short', empty($this->class) ? '' : " $this->class", '" name="' . $name . '" value="' . $value . '" />';
	}
}
?>
