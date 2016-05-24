<?php
//install container
$register = new Registry();

//Loader
$loader = new loader($register);
$register->set('load',$loader);

// Request
$request = new Request();
$register->set('request', $request);

$config = new Config();
$config->load('default');
$register->set('config', $config);



$controller = new Front($register);
// Pre Actions
if ($config->has('action_pre_action')) {
	foreach ($config->get('action_pre_action') as $value) {
		$controller->addPreAction(new Action($value));
	}
}

function getDir($dir){
	$handler = opendir($dir);
	$files = array();
	while (($filename = readdir($handler)) !== false) {
		if ($filename != "." && $filename != "..") {
			$files[] = $filename ;
	 	}
	}
	closedir($handler);
	return $files;
}

// Dispatch
$uri = $request->server['REQUEST_URI'];
$moduleName = explode('/' , $uri)[1];
if(empty($moduleName)){
	$controller->dispatch(new Action(DIR_FONTEND,$config->get('action_default')), new Action($config->get('action_error')));
}else{
	$lower = str_replace(array(" ","　","\t","\n","\r") , array("","","","","") , strtolower($moduleName));
	$files = getDir(DIR_APPLICATION.'modules');
	$flag = false;
	foreach ($files as $file) {
		if($lower == $files){
			$flag = true;
			break;
		}
	}
	$router = DIR_APPLICATION . $lower . '/';
	if($flag){
		$controller->dispatch(new Action($router, $uri), new Action($router,$config->get('action_error')));
	}else{
		$controller->dispatch(new Action(DIR_FONTEND,$config->get('action_default')), new Action(DIR_FONTEND , $config->get('action_error')));
	}
}

// Output
// $response->output();