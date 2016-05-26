<?php
class Template {
	private $data = array();
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render() {
		$file = DIR_TEMPLATE . $template;

		if (file_exists($file)) {
			// $test = array('name'=>'james','age'=>'18');
			// extract($test);
			// var_dump($name); echo james;
			// var_dump($age);  echo 18;
			extract($data);

            //让你自由控制脚本中数据的输出
			ob_start();

			require($file);

			$output = ob_get_contents();

            //得到当前缓冲区的内容并删除当前输出缓冲区 
			ob_end_clean();

			return $output;
		} else {
			trigger_error('Error: Could not load template ' . $file . '!');
			exit();
		}
	}	
}