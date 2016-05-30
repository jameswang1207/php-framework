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
        // 2.在controller中直接拿到register中的内容是在Controller中重写了Controller的__get与__set方法
		// return $this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data);
		//loader 某个model进入controller
	}


	/**
	 *
	 * @url GET /index
	 */
	public function test() {
		$data = array();
		$this->response->dispatch($this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data));
	}

	/**
	 *
	 * @url GET /index/$id
	 */
	public function getId($id) {
		var_dump($id);
        die();
	}

	/**
	 *
	 * @url GET /save
	 */
	public function save() {
        return "Hello World";
	}


	//init 进controller进行初始话
    // public function  init(){
    // 	var_dump("this controller is coming");
    // }
}