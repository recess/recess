<?php
Layout::extend('layouts/code');
$title = $package->name;
?>
<?php
function linkedPackagePath($package, $linkPrefix = "") {
	$parts = explode('.', $package);
	$partsSize = count($parts);
	
	for($i = 0; $i < $partsSize; $i++) {
		if($i > 0) echo '.';
		if($i + 1 != $partsSize)
			echo '<a href="' . $linkPrefix . implode('.', array_slice($parts,0,$i+1)) . '">' . $parts[$i] . '</a>';
		else
			echo $parts[$i];
	}
}
?>

<h1><?php linkedPackagePath($package->name); ?></h1>
<h2>Sub-packages</h2>
<ul>
<?php
foreach($package->children() as $child) {
	echo '<li><a href="' . $child->name . '">' . $child->name . '</a></li>';
}
?>
</ul>

<h2>Classes</h2>
<ul>
<?php
foreach($package->getClasses() as $class) {
	echo '<li><a href="../class/' . $package->name . '.' . $class->name . '">'. $class->name .'</a></li>';
}
?>
</ul>