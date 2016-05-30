<?php
class PHP_Job
{
	/**
	 *  方法perform是必须的,主要用来处理任务.
	 * @return [type] [description]
	 */
	public function perform()
	{
        echo $this->args['name'];
        echo "=============================";
	}
}