<?php
final class Front {
	private $registry;
	private $pre_action = array();

	public function __construct($registry) {
		$this->registry = $registry;
	}
	

	public function dispatch(Action $action) {
		while ($action instanceof Action) {
			$action = $this->execute($action);
		}
	}

	private function execute(Action $action) {
		$result = $action->execute($this->registry);
		if ($result instanceof Action) {
			return $result;
		}
		if ($result instanceof Exception) {
			return $action;
		}
	}
}
