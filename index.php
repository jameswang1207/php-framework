<?php
// Version
define('VERSION', '0.0.0.1');

//error level
error_reporting(E_ALL);

// Check if SSL
if ((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) || $_SERVER['SERVER_PORT'] == 443) {
	$protocol = 'https://';
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
	$protocol = 'https://';
} else {
	$protocol = 'http://';
}

define('HTTP_SERVER', $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/.\\') . '/');

//Project root dir
define('DIR_APPLICATION', str_replace('\\', '/', realpath(dirname(__FILE__))) . '/');

//DB
if(is_file('db_config.php')){
   require_once('db_config.php');
}

//Install
if(is_file('install.php')){
	require_once('install.php');
	// Init config
	Install::installConfig();
}

//start up this project
if(is_file(DIR_SYSTEM.'startup.php')){
	require_once(DIR_SYSTEM.'startup.php');
}

//Run this framework
start();