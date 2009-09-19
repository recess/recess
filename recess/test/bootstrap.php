<?php

date_default_timezone_set('America/New_York');

$_ENV['dir.bootstrap'] = str_replace('\\','/',realpath(dirname(__FILE__) . '/../../')) . '/';
$_ENV['url.base'] = str_replace('AllTests.php', '', $_SERVER['PHP_SELF']);

$_ENV['dir.recess'] = $_ENV['dir.bootstrap'] . 'recess/';
$_ENV['dir.plugins'] = $_ENV['dir.bootstrap'] . 'plugins/';
$_ENV['dir.apps'] = $_ENV['dir.bootstrap'] . 'apps/';
$_ENV['dir.test'] = $_ENV['dir.bootstrap'] . 'recess/test/';
$_ENV['dir.temp'] = $_ENV['dir.bootstrap'] . 'data/temp/';

require_once($_ENV['dir.recess'] . 'recess/lang/Library.class.php');
Library::addClassPath($_ENV['dir.recess']);
?>
