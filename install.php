<?php
class Install {
	public static function installConfig(){
		//文件局部变量的写入
		$modules_dir = DIR_APPLICATION.'modules/';
		$files = self::getDir($modules_dir);
		foreach ($files as $file) {
			$install_path = DIR_APPLICATION . 'modules/'.$file.'/config.php';
	  		if(!file_exists($install_path)){
				fopen($install_path, "w+");
	  		}
            
	  		if(filesize($install_path) <= 0){
				self::createFile($file,$install_path,true);
	  		}
	  		require_once($install_path);
	  	}
	  	//文件全局变量的写入
	  	$global_dir =  DIR_APPLICATION.'global_config.php';
	  	if(filesize($global_dir) <= 0 ){
            self::createFile($file,$global_dir,false);
	  	}
	  	require_once($global_dir);
	}
    
	private static function createFile($file_dir,$install_path,$flag){
		$output  = '<?php' . "\n";
        $file_temp = str_replace(array(" ","　","\t","\n","\r") , array("","","","","") , strtoupper($file_dir));
		if($flag){
			$file_dir = 'modules/' .$file_dir;
			$output .= 'define(\'DIR_' . $file_temp . '\', \'' . DIR_APPLICATION . $file_dir .'\');' . "\n";
			$output .= 'define(\'DIR_IMAGE_' . $file_temp . '\', \'' . DIR_APPLICATION . $file_dir .'/images/\');' . "\n";
			$output .= 'define(\'DIR_LANGUAGE_' . $file_temp . '\', \'' . DIR_APPLICATION . $file_dir . '/language/\');' . "\n";
			$output .= 'define(\'DIR_TEMPLATE_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/template/\');' . "\n";
			$output .= 'define(\'DIR_CSS_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/css/\');' . "\n";
			$output .= 'define(\'DIR_FONT_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/font/\');' . "\n";
			$output .= 'define(\'DIR_IMAGES_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/images/\');' . "\n";
			$output .= 'define(\'DIR_JS_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/js/\');' . "\n";
			$output .= 'define(\'DIR_JSLIB_' . $file_temp . '\', \'' . DIR_APPLICATION .  $file_dir .'/view/jslib/\');' . "\n";
		}else{
			$output .= 'define(\'DIR_SYSTEM\', \'' . DIR_APPLICATION . 'system/\');' . "\n";
			$output .= 'define(\'DIR_CONFIG\', \'' . DIR_APPLICATION . 'system/config/\');' . "\n";
			$output .= 'define(\'DIR_CACHE\', \'' . DIR_APPLICATION . 'system/storage/cache/\');' . "\n";
			$output .= 'define(\'DIR_LOGS\', \'' . DIR_APPLICATION . 'system/storage/logs/\');' . "\n";
			$output .= 'define(\'DIR_UPLOAD\', \'' . DIR_APPLICATION . 'system/storage/upload/\');' . "\n";
		}
		$file = fopen($install_path, 'w');
		fwrite($file, $output);
		fclose($file);
	}

	private static function getDir($dir){
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
}