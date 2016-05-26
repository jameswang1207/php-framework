<?php
	//error level
	error_reporting(E_ALL);

	// Check Version
	if (version_compare(phpversion(), '5.3.0', '<') == true) {
		exit('PHP5.3+ Required');
	}

    //Magic Quotes Fix
    // Windows IIS Compatibility
    //Check if SSL
  

    //Auto_loader library
	function library($class) {
		$file = DIR_SYSTEM . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';
		if (is_file($file)) {
			include_once($file);
			return true;
		} else {
			return false;
		}
	}

	spl_autoload_register('library');
	spl_autoload_extensions('.php');
	
    //Rest server
	require_once(DIR_SYSTEM . 'engine/restException.php');
	require_once(DIR_SYSTEM . 'engine/restFormat.php');
	//Engine
	require_once(DIR_SYSTEM . 'engine/action.php');
	require_once(DIR_SYSTEM . 'engine/controller.php');
	require_once(DIR_SYSTEM . 'engine/front.php');
	require_once(DIR_SYSTEM . 'engine/loader.php');
	require_once(DIR_SYSTEM . 'engine/model.php');
	require_once(DIR_SYSTEM . 'engine/registry.php');


	function start() {
	    require_once(DIR_SYSTEM . 'framework.php');	
    }
?>