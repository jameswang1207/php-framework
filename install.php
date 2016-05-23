<?php
class Install {
	public static function installConfig(){
		$modules_dir = DIR_APPLICATION.'modules/';
		$files = self::getDir($modules_dir);
		foreach ($files as $file) {
			$install_path = DIR_APPLICATION . 'modules/'.$file.'/config.php';
	  		if(!file_exists($install_path)){
				fopen($install_path, "w+");
	  		}
            
	  		if(filesize($install_path) <= 0){
				self::createFile($file,$install_path);
	  		}
	  	}
	}

	private static function createFile($file_dir,$install_path){   
		$output  = '<?php' . "\n";

		$output .= '// DIR' . "\n";
		$output .= 'define(\'DIR_SYSTEM\', \'' . DIR_APPLICATION . 'system/\');' . "\n";
		$output .= 'define(\'DIR_IMAGE\', \'' . DIR_APPLICATION . $file_dir .'/images/\');' . "\n";			
		$output .= 'define(\'DIR_LANGUAGE\', \'' . DIR_APPLICATION . $file_dir . '/language/\');' . "\n";
		$output .= 'define(\'DIR_TEMPLATE\', \'' . DIR_APPLICATION .  $file_dir .'/view/template/\');' . "\n";
		$output .= 'define(\'DIR_CONFIG\', \'' . DIR_APPLICATION . 'system/config/\');' . "\n";
		$output .= 'define(\'DIR_CACHE\', \'' . DIR_APPLICATION . 'system/storage/cache/\');' . "\n";
		$output .= 'define(\'DIR_LOGS\', \'' . DIR_APPLICATION . 'system/storage/logs/\');' . "\n";
		$output .= 'define(\'DIR_UPLOAD\', \'' . DIR_APPLICATION . 'system/storage/upload/\');' . "\n";
        
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