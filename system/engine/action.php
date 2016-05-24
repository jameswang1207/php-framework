<?php
class Action {
	private $id;
	private $route;
	private $method = 'index';
	private $modules_dir;

	public function __construct($modules_dir,$route) {
		$this->id = $route;
		$this->modules_dir = $modules_dir;
		
		$parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route));
		// Break apart the route
		while ($parts) {
			$file = $this->modules_dir . '/controller/' . implode('/', $parts) . '.php';
			if (is_file($file)) {
				$this->route = implode('/', $parts);
				break;
			} else {
				$this->method = array_pop($parts);			
			}
		}
	}
	
	function getId() {
		return $this->id;
	}
	
	public function execute($registry, array $args = array()) {
		// Stop any magical methods being called
		if (substr($this->method, 0, 2) == '__') {
			return new \Exception('Error: Calls to magic methods are not allowed!');
		}

		$file = $this->modules_dir . 'controller/' . $this->route . '.php';		
		$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $this->route);
		
		// Initialize the class
		if (is_file($file)) {
			include_once($file);
		
			$controller = new $class($registry);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
		
		$reflection = new ReflectionClass($class);
		
		if ($reflection->hasMethod($this->method) && $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
			return call_user_func_array(array($controller, $this->method), $args);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
	}
}
