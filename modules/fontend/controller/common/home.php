<?php

#@Controller('/home')
class ControllerCommonHome extends Controller {

	#@RequestMapping('/index/{username}')
	public function index() {
        echo "james";
	}
}