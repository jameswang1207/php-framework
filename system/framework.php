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

function checkDirectory($dir,$checkDir){
	$files = getDir($dir);
    $flag = false;
    if(count($files) > 0){
		foreach ($files as $file) {
			if($file == $checkDir){
				$flag = true;
				break;
			}
		}
    }
    return $flag;
}


// function startupFramework($paths){
//     $controller->dispatch(new Action($paths), new Action($config->get('action_error')));
//     // Output
//    // $response->output();
// }

###########################################################
#php-framework url rule
#  domain/moduleName/packageName/fileName/methodName/parameter
#example
#  http://localhost:7777/fontend/common/home/index
###########################################################

$path = substr(preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']),1);
if($path){
	if(count(explode('/',$path)) >= 3 ){
		$paths = explode('/',$path);
		$moduleName = $paths[0];
		$modulePath = DIR_APPLICATION.'modules';
		if(checkDirectory($modulePath,$moduleName)){
			$controllerName = $paths[1];
			$controllerPath = $modulePath .'/'. $moduleName . '/controller';
			checkDirectory($controllerPath,$controllerName);
			if(checkDirectory($controllerPath,$controllerName)){
				$filePath = $controllerPath.'/'.$controllerName.'/'.$paths[2].'.php';
                if(file_exists($filePath)){
                    // startupFramework($paths);
                    $controller->dispatch(new Action($paths,true));
                }else{
					echo "not found controller";
                }
			}else{
				echo "not found controller folder";
			}
		}else{
			echo "not found this module";
		}
	}else{
        echo "not found page";
	}
}else{
	echo "plase create you project";
}
