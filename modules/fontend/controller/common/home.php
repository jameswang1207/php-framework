<?php
class ControllerCommonHome extends Controller {

	/**
	 * this function is default.
	 * @url GET /
	 */
	public function index() {
		$data = array();
		// var_dump(file_exists(DIR_TEMPLATE_FONTEND . 'common/index.tpl'));
		// // die();
		$this->response->dispatch($this->load->view(DIR_TEMPLATE_FONTEND . 'common/index.tpl', $data));
	}

	/**
	 *
	 * @url GET /index
	 */
	public function test() {
        return "Hello World";
	}

	/**
	 *
	 * @url GET /index/$id
	 */
	public function getId($id) {
        return $id;
	}

	/**
	 *
	 * @url GET /save
	 */
	public function save() {
        return "Hello World";
	}
}