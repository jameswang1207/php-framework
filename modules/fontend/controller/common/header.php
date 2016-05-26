<?php
class ControllerCommonHeader extends Controller {

	/**
	 * this function is default.
	 * @url GET /
	 */
	public function index() {
		// var_dump($this->load->controller(DIR_CONTROLLER_FONTEND,'common/home'));
		$this->log->write($this);
		die();
		$this->load->model(DIR_MODEL_FONTEND,'common/home');
		$result = $this->model_common_home->getTest();
        var_dump($result);
        die();
	}
}