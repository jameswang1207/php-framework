<?php
class Url {
	private $url;
	public function link($route, $args = '', $secure = false) {
		if ($secure) {
			$url = $route;
		}
        //http_build_query(['name'=>'james','age'=>'14'])
        //name=james&age=14
		if ($args) {
			if (is_array($args)) {
				$url .= '/' . implode('/',$args);
			} else {
				throw new Exception('500','Internal Server Error');
			}
		}
		return $url;
	}
}