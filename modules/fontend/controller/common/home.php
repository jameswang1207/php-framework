<?php
class ControllerCommonHome extends Controller {

	/**
	 * this function is default.
	 * @url GET /
	 */
	public function index() {
		$data = array();

		// 请求转化
		// $this->response->dispatch($this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data));
		
		//重定向
		// $this->response->redirect($this->url->link('/fontend/common/home/index','hhe',true));
		
		// 返回json
        // $this->response->addHeader('Content-Type: application/json');
        // $json = array(
        //    'name'=>'james',
        //    'age' =>15,
        //    'page'=>23
        // );
		// $this->response->setOutput(json_encode($json));

        // loader某个模板进入Controller中
        // 1.
        // request api  content : $this->load->controller(DIR_CONTROLLER_FONTEND,'common/home');
        // 2.
		// return $this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data);
		
		//loader 某个model进入controller
	}


	/**
	 *
	 * @url GET /index
	 */
	public function test() {
        var_dump("james");
        die();
	}

	/**
	 *
	 * @url GET /index/$id/$name
	 */
	public function getId($id,$name) {
        var_dump($id.$name);
        die();
	}

	/**
	 *
	 * @url GET /save
	 */
	public function save() {
        return "Hello World";
	}
}