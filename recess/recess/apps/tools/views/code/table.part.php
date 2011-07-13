<?php
Part::input($controller, 'Controller');
Part::input($objects, 'ModelSet');
Part::input($columns, 'int', 4);

$count = $objects->count();
$perColumn = ceil($count / $columns);
echo '<table>';
for($row = 0 ; $row < $perColumn ; $row++) {
	echo '<tr>';
	for($col = 0 ; $col < $columns ; $col++) {
		$object = isset($objects[$perColumn*$col+$row]) ? $objects[$perColumn*$col+$row] : '';
		
		$value = is_object($object) ? $object->name : '';
		if($object instanceof RecessReflectorClass && $object->getPackage() != null) {
			$prefix = $object->getPackage()->name . '.';
			$linkTo = 'class';
		} else {
			$prefix = '';
			$linkTo = 'package';
		}
		//$linkTo = get_class($object) == 'RecessReflectorClass' ? 'class' : 'package';
		echo '<td><a href="', $controller->urlTo($linkTo . 'Info', $prefix . $value),'">', $value, '</a></td>';
	}
	echo '</tr>';
}
echo '</table>';
?>