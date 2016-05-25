<?php
class Action {
	public $url;
	public $method;
	public $params;
	public $format;
	public $cacheDir = __DIR__;
	public $realm;
	public $mode;
	public $root;
	public $rootPath;
	public $jsonAssoc = false;
	protected $map = array();
	protected $errorClasses = array();
	protected $cached;

	private $class;
	private $fileBasePath;

	public function __construct($parts) {
		// Break apart the route
		$file = DIR_APPLICATION .'modules/'. $parts[0] . '/controller/' . $parts[1] . '/' . $parts[2] . '.php';
		if (file_exists($file)) {
			$this->fileBasePath = $file;
			require_once($file);
			$this->class = 'Controller' . ucfirst($parts[1]). ucfirst($parts[2]);
		}else{
			throw new Exception('Class file not found');
		}
	}

	public function execute($registry) {
        $this->init('debug');
        if(class_exists($this->class)){
            $this->addClass($this->class);
            $this->handle();
        }else{
            throw new Exception('Not found class');
        }
	}

	public function init($mode = 'debug', $realm = 'rest server'){   
		$this->cacheDir = $this->cacheDir . '/storage/cache';
		$this->mode = $mode;
		$this->realm = $realm;
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
			$noAuth = strpos($doc, '@noAuth') !== false;
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
					$call[] = $noAuth;

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

	//Get response data type
	public function getFormat(){
		$format = RestFormat::PLAIN;
		$accept_mod = null;
		if(isset($_SERVER["HTTP_ACCEPT"])) {
			//将http_accept中的空格去掉
			$accept_mod = preg_replace('/\s+/i', '', $_SERVER['HTTP_ACCEPT']);
		}
		$accept = explode(',', $accept_mod);
		$override = '';

		if (isset($_REQUEST['format']) || isset($_SERVER['HTTP_FORMAT'])) {
			// 优先给GET/POST重写请求头
			$override = isset($_SERVER['HTTP_FORMAT']) ? $_SERVER['HTTP_FORMAT'] : '';
			$override = isset($_REQUEST['format']) ? $_REQUEST['format'] : $override;
			$override = trim($override);
		}

		// Check for trailing dot-format syntax like /controller/action.format -> action.json
		if(preg_match('/\.(\w+)$/i', strtok($_SERVER["REQUEST_URI"],'?'), $matches)) {
			$override = $matches[1];
		}

		// Give GET parameters precedence before all other options to alter the format
		$override = isset($_GET['format']) ? $_GET['format'] : $override;
		if (isset(RestFormat::$formats[$override])) {
			$format = RestFormat::$formats[$override];
		} elseif (in_array(RestFormat::JSON, $accept)) {
			$format = RestFormat::JSON;
		}
		return $format;
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
		$this->format = $this->getFormat();

		if ($this->method == 'PUT' || $this->method == 'POST') {
			$this->data = $this->getData();
		}

		list($obj, $method, $params, $this->params, $noAuth) = $this->findUrl();
		if ($obj) {
			if (is_string($obj)) {
				if (class_exists($obj)) {
					$obj = new $obj();
				} else {
					throw new Exception("Class $obj does not exist");
				}
			}
			$obj->server = $this;
			try {
				if (method_exists($obj, 'init')) {
					$obj->init();
				}
				if (!$noAuth && method_exists($obj, 'authorize')) {
					if (!$obj->authorize()) {
						$this->sendData($this->unauthorized(true)); //@todo unauthorized returns void
						exit;
					}
				}
				$result = call_user_func_array(array($obj, $method), $params);
				if ($result !== null) {
					$this->sendData($result);
				}
			} catch (RestException $e) {
				$this->handleError($e->getCode(), $e->getMessage());
			}
		} else {
			$this->handleError(404);
		}
	}

    ##############################
    #   错误处理方法
    ##############################
	public function unauthorized($ask = false){
		if ($ask) {
			header("WWW-Authenticate: Basic realm=\"$this->realm\"");
		}
		throw new RestException(401, "You are not authorized to access this resource.");
	}

	public function handleError($statusCode, $errorMessage = null){
		$method = "handle$statusCode";
		foreach ($this->errorClasses as $class) {
			if (is_object($class)) {
				$reflection = new ReflectionObject($class);
			} elseif (class_exists($class)) {
				$reflection = new ReflectionClass($class);
			}

			if (isset($reflection))
			{
				if ($reflection->hasMethod($method))
				{
					$obj = is_string($class) ? new $class() : $class;
					$obj->$method();
					return;
				}
			}
		}
		if (!$errorMessage)
		{
			$errorMessage = $this->codes[$statusCode];
		}
		$this->setStatus($statusCode);
		$this->sendData(array('error' => array('code' => $statusCode, 'message' => $errorMessage)));
	}
	
	###################
	#  Response data
	###################
	
    //Response status code
	public function setStatus($code){
		if (function_exists('http_response_code')) {
			http_response_code($code);
		} else {
			$protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			$code .= ' ' . $this->codes[strval($code)];
			header("$protocol $code");
		}
	}

	//send data to client
	public function sendData($data){
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: 0");
		header('Content-Type: ' . $this->format);

		if ($this->format == RestFormat::XML) {

		if (is_object($data) && method_exists($data, '__keepOut')) {
				$data = clone $data;
				foreach ($data->__keepOut() as $prop) {
					unset($data->$prop);
				}
			}
			$this->xml_encode($data);
		} else {
			if (is_object($data) && method_exists($data, '__keepOut')) {
				$data = clone $data;
				foreach ($data->__keepOut() as $prop) {
					unset($data->$prop);
				}
			}
			$options = 0;
			if ($this->mode == 'debug') {
				$options = JSON_PRETTY_PRINT;
			}
			$options = $options | JSON_UNESCAPED_UNICODE;
			echo json_encode($data, $options);
		}
	}

    // response xml
	private function xml_encode($mixed, $domElement=null, $DOMDocument=null) {
		if (is_null($DOMDocument)) {
			$DOMDocument =new DOMDocument;
			$DOMDocument->formatOutput = true;
			$this->xml_encode($mixed, $DOMDocument, $DOMDocument);
			echo $DOMDocument->saveXML();
		}
		else {
			if (is_array($mixed)) {
				foreach ($mixed as $index => $mixedElement) {
					if (is_int($index)) {
						if ($index === 0) {
							$node = $domElement;
						}
						else {
							$node = $DOMDocument->createElement($domElement->tagName);
							$domElement->parentNode->appendChild($node);
						}
					}
					else {
						$plural = $DOMDocument->createElement($index);
						$domElement->appendChild($plural);
						$node = $plural;
						if (!(rtrim($index, 's') === $index)) {
							$singular = $DOMDocument->createElement(rtrim($index, 's'));
							$plural->appendChild($singular);
							$node = $singular;
						}
					}

					$this->xml_encode($mixedElement, $node, $DOMDocument);
				}
			}
			else {
				$domElement->appendChild($DOMDocument->createTextNode($mixed));
			}
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
		'401' => 'Unauthorized',
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
