<?php
class Action {
	public $url;
	public $method;
	public $params;
	public $cacheDir = __DIR__;
	public $realm;
	public $mode;
	public $root;
	public $rootPath;
	public $jsonAssoc = false;
	protected $map = array();
	protected $cached;

	private $class;
	private $fileBasePath;
	private $registry;
	private $result;

	public function __construct($parts) {
		// Break apart the route
		$file = DIR_APPLICATION .'modules/'. $parts[0] . '/controller/' . $parts[1] . '/' . $parts[2] . '.php';
		if (file_exists($file)) {
			$this->fileBasePath = $parts[0] . '/' . $parts[1] . '/' . $parts[2] . '/';
			require_once($file);
			$this->class = 'Controller' . ucfirst($parts[1]). ucfirst($parts[2]);
		}else{
			throw new Exception('Class file not found');
		}
	}

	public function execute($registry) {
        $this->init('debug');
        if(class_exists($this->class)){
        	$this->registry = $registry;
            $this->addClass($this->class,$this->fileBasePath);
            $this->handle();
            return $this->result;
        }else{
            new Exception('404','Not found class');
        }
	}

	public function init($mode = 'debug'){   
		$this->cacheDir = $this->cacheDir . '/storage/cache';
		$this->mode = $mode;
		// Set the root
		$dir = str_replace('\\', '/', dirname(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME'])));
		if ($dir == '.') {
			$dir = '/';
		} else {
			// add a slash at the beginning and end
			if (substr($dir, -1) != '/') $dir .= '/';
			if (substr($dir, 0, 1) != '/') $dir = '/' . $dir;
		}
		$this->root = $dir;
	}

    ###########################
    #  解析对应controller中的数据
    ###########################
	//url cache
	protected function loadCache(){
		if ($this->cached !== null) {
			return;
		}
		$this->cached = false;
		if ($this->mode == 'production') {
			if (function_exists('apc_fetch')) {
				$map = apc_fetch('urlMap');
			} elseif (file_exists($this->cacheDir . '/urlMap.cache')) {
				$map = unserialize(file_get_contents($this->cacheDir . '/urlMap.cache'));
			}
			if (isset($map) && is_array($map)) {
				$this->map = $map;
				$this->cached = true;
			}
		} else {
			if (function_exists('apc_delete')) {
				apc_delete('urlMap');
			} else {
				//delete cache file
				@unlink($this->cacheDir . '/urlMap.cache');
			}
		}
	}

    //解析方法中的annotation
	protected function generateMap($class, $basePath){
		if (is_object($class)) {
			$reflection = new ReflectionObject($class);
		} elseif (class_exists($class)) {
			$reflection = new ReflectionClass($class);
		}
		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $method) {
			$doc = $method->getDocComment();
		    /**
		    *GET（SELECT）：从服务器取出资源（一项或多项）
			*POST（CREATE）：在服务器新建一个资源
			*PUT（UPDATE）：在服务器更新资源（客户端提供改变后的完整资源）
			*PATCH（UPDATE）：在服务器更新资源（客户端提供改变的属性）
			*DELETE（DELETE）：从服务器删除资源
			*HEAD：获取资源的元数据
            *OPTIONS：获取信息，关于资源的哪些属性是客户端可以改变的
		    */
			if (preg_match_all('/@url[ \t]+(GET|POST|PUT|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s', $doc, $matches, PREG_SET_ORDER)) {

				$params = $method->getParameters();

				foreach ($matches as $match) {
					//get http method
					//java requestMapping
					//@url GET /charts/$id/$date/$interval/
					$httpMethod = $match[1];
					$url = $basePath . $match[2];
					if ($url && $url[strlen($url) - 1] == '/') {
						$url = substr($url, 0, -1);
					}
					$call = array($class, $method->getName());
					$args = array();
					foreach ($params as $param) {
						$args[$param->getName()] = $param->getPosition();
					}
					$call[] = $args;
					$call[] = null;

					$this->map[$httpMethod][$url] = $call;
				}
			}
		}
	}

    // Handle controller class
	public function addClass($class, $basePath = ''){
		$this->loadCache();

		if (!$this->cached) {
			if (is_string($class) && !class_exists($class)){
				throw new Exception('Invalid method or class');
			} elseif (!is_string($class) && !is_object($class)) {
				throw new Exception('Invalid method or class; must be a classname or object');
			}

			if (substr($basePath, 0, 1) == '/') {
				$basePath = substr($basePath, 1);
			}
			if ($basePath && substr($basePath, -1) != '/') {
				$basePath .= '/';
			}
			$this->generateMap($class, $basePath);
		}
	}

    #######################################################################
    #                      接收请求并开始处理                               #
    #######################################################################
    
    //Get request URI
	public function getPath(){
		$path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
		// remove root from path
		if ($this->root) $path = preg_replace('/^' . preg_quote($this->root, '/') . '/', '', $path);
		// remove trailing format definition, like /controller/action.json -> /controller/action
		$path = preg_replace('/\.(\w+)$/i', '', $path);
		// remove root path from path, like /root/path/api -> /api
		if ($this->rootPath) $path = str_replace($this->rootPath, '', $path);
		return $path;
	}

	//Get request method
	public function getMethod(){
		$method = $_SERVER['REQUEST_METHOD'];
		$override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');
		if ($method == 'POST' && strtoupper($override) == 'PUT') {
			$method = 'PUT';
		} elseif ($method == 'POST' && strtoupper($override) == 'DELETE') {
			$method = 'DELETE';
		}
		return $method;
	}

    //Get request original data's stream
	public function getData(){
		$data = file_get_contents('php://input');
		$data = json_decode($data, $this->jsonAssoc);
		return $data;
	}

    protected function findUrl(){
		$urls = $this->map[$this->method];
		if (!$urls) return null;
		foreach ($urls as $url => $call) {
			$args = $call[2];
			if (!strstr($url, '$')) {
				if ($url == $this->url) {
					if (isset($args['data'])) {
						$params = array_fill(0, $args['data'] + 1, null);
						$params[$args['data']] = $this->data;   //@todo data is not a property of this class
						$call[2] = $params;
					} else {
						$call[2] = array();
					}
					return $call;
				}
			} else {
				$regex = preg_replace('/\\\\\$([\w\d]+)\.\.\./', '(?P<$1>.+)', str_replace('\.\.\.', '...', preg_quote($url)));
				$regex = preg_replace('/\\\\\$([\w\d]+)/', '(?P<$1>[^\/]+)', $regex);
				if (preg_match(":^$regex$:", urldecode($this->url), $matches)) {
					$params = array();
					$paramMap = array();
					if (isset($args['data'])) {
						$params[$args['data']] = $this->data;
					}
					foreach ($matches as $arg => $match) {
						if (is_numeric($arg)) continue;
						$paramMap[$arg] = $match;

						if (isset($args[$arg])) {
							$params[$args[$arg]] = $match;
						}
					}
					ksort($params);
					// make sure we have all the params we need
					end($params);
					$max = key($params);
					for ($i = 0; $i < $max; $i++) {
						if (!array_key_exists($i, $params)) {
							$params[$i] = null;
						}
					}
					ksort($params);
					$call[2] = $params;
					$call[3] = $paramMap;
					return $call;
				}
			}
		}
	}

    //Call this function and start handle request
	public function handle(){
		$this->url = $this->getPath();
		$this->method = $this->getMethod();
		if ($this->method == 'PUT' || $this->method == 'POST') {
			$this->data = $this->getData();
		}
		list($obj, $method, $params, $this->params) = $this->findUrl();
		if ($obj) {
			if (is_string($obj)) {
				if (class_exists($obj)) {
					$obj = new $obj($this->registry);
				} else {
					throw new Exception("Class $obj does not exist");
				}
			}
			$obj->server = $this;
			try {
				if (method_exists($obj, 'init')) {
					$obj->init();
				}
                
				$result = call_user_func_array(array($obj, $method), $params);
				$this->result = $result;
                //reurn send data
			} catch (RestException $e) {
				// call handleError  
				$this->result = new RestException($e->getCode(),$e->getMessage());
			}
		} else {
			// call  handleError
			// $this->handleError(404);
			$this->result = new RestException(404,'Object is not found.');
		}
	}
	private $codes = array(
		'100' => 'Continue',
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'307' => 'Temporary Redirect',
		'400' => 'Bad Request',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'503' => 'Service Unavailable'
	);
}
