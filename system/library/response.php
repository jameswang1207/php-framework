<?php
class Response {
	private $headers = array();
	private $level = 0;
	private $output;

	public function addHeader($header) {
		$this->headers[] = $header;
	}

	public function redirect($url, $status = 302) {
		header('Location: '. $url, $status);
		exit();
	}

	public function setCompression($level) {
		$this->level = $level;
	}

	public function dispatch($output) {
		$this->output = $output;
	}

    public function setOutput($output) {
		$this->output = $output;
	}

	public function getOutput() {
		return $this->output;
	}

	private function compress($data, $level = 0) {
        //查找 "php" 在字符串中第一次出现的位置：strpos
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

        //检查一个扩展是否已经加载 
		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

        //headers_sent() 函数检查 HTTP 标头是否已被发送以及在哪里被发送。如果报头已发送，则返回 true，否则返回 false。 
		if (headers_sent()) {
			return $data;
		} 
        //函数返回当前的连接状态
		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}

	public function output() {
		if ($this->output) {
			if ($this->level) {
				$output = $this->compress($this->output, $this->level);
			} else {
				$output = $this->output;
			}

			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					header($header, true);
				}
			}
			echo $output;
		}
	}
}