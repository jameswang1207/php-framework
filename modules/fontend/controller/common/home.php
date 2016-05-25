<?php
class ControllerCommonHome extends Controller {


	/**
	 *
	 * @url GET /
	 */
	public function test() {
        return "Hello World";
	}

	/**
	 *
	 * @url GET /index/$id
	 */
	public function index($id) {
        return "Hello World";
	}

	/**
	 *
	 * @url GET /save
	 */
	public function save() {
        return "Hello World";
	}
}