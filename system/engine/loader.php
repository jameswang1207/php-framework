<?php
final class Loader {
	private $register;
	private $method;

	public function __construct($register) {
		$this->register = $register;
	}

	public function controller($root,$route,$data = array()) {
        $root = $root . $route;
		$parts = explode('/', str_replace('../', '', (string)$root));
		$controllerParts = explode('/', str_replace('../', '', (string)$route));
	    // Break apart the route
	    while ($parts) {
			if(count($controllerParts) == 3){
				array_pop($parts);
			    $file = implode('/', $parts) . '.php';
				$this->method = array_pop($controllerParts);
			}else{
				$file = implode('/', $parts) . '.php';
				$this->method = 'index';
			}

            array_walk($controllerParts,function(&$value){$value = ucfirst($value);});

			$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', implode('/', $controllerParts));

			if (is_file($file)) {
				include_once($file);
				break;
			}else{
				throw new Exception('404','Not find Controller.');
			}
	    }
		// Stop any magical methods being called
		if (substr($this->method, 0, 2) == '__') {
			return false;
		}
		$controller = new $class($this->register);
		if(method_exists($class,$this->method)){
			$output = '';
			// 检测参数是否为合法的可调用结构 is_callable
			//  把第一个参数作为回调函数调用 call_user_func
			if (is_callable(array($controller, $this->method))) {
				$output = call_user_func(array($controller, $this->method), $data);
			}

		    return $output;
		}else{
            throw new Exception('404','Not find methods.');
		}
	}

	public function view($template, $data = array()) {
        $isCompression = $this->register->get('config')->get('is_compression_html');
        $minity = $this->register->get('minity');
		$file = $template;
		if (file_exists($file)) {

			extract($data);

			ob_start();

			require($file);
			$output = ob_get_contents();

			ob_end_clean();
		} else {
			trigger_error('Error: Could not load template ' . $file . '!');
			exit();
		}

		if($isCompression){
			$output = $minity->minify_html($output);
		}

		return $output;
	}


	public function model($root,$route, $data = array()) {
         
        $paths = $root.$route;

		$roots= explode('/', str_replace('../', '', (string)$paths));

		$route = explode('/', str_replace('../', '', (string)$route));

		$modelParts =  $route;

		$file = implode('/',$roots) . '.php';

        array_walk($modelParts,function(&$value){$value = ucfirst($value);});
		$class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '',  implode('/',$modelParts));

		if (file_exists($file)) {
			include_once($file);
			$this->register->set('model_' . str_replace('/', '_', implode('/',$route)), new $class($this->register));
		} else {
			trigger_error('Error: Could not load model ' . $file . '!');
			exit();
		}
	}
}
