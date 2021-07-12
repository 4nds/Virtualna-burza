<?php

// index.php --> korisnikController, index
// index.php?rt=something --> somethingController, index
// index.php?rt=something/action --> somethingController, action

require_once __DIR__ . '/app/debug.php';

$x = '1';

$default_controller = 'login';
$default_action = 'index';

$controller = $default_controller;
$action = $default_action;


if(isset($_GET['rt'])) {
	$parts = explode('/', $_GET['rt']);
	if (isset($parts[0]) && ctype_alnum($parts[0])) {
		$controller = $parts[0];
	}
	if (isset($parts[1]) && ctype_alnum($parts[1])) {
		$action = $parts[1];
	}
}


$controller_classname = $controller . 'Controller';
$controller_filename = __DIR__ . '/controller/' . $controller_classname . '.php';

if (! file_exists($controller_filename)) {
	raiseErrorAndExit('Error', 'Ne postoji controller ' . $controller_classname . '.');
}
require_once $controller_filename;

if(! class_exists($controller_classname)) {
	raiseErrorAndExit('Error', 'Ne postoji controller ' . $controller_classname . '.');
}

$con = new $controller_classname;

if (! method_exists($con, $action)) {
	raiseErrorAndExit('Error', 'Ne postoji metoda ' . $action . ' u controlleru ' . $controller_classname);
}

$con->$action();


?>