<?php
if(empty($argv[1])) {
	die('Specify the name of a job to add. e.g, php queue.php PHP_Job');
}

require __DIR__ . '/init.php';
date_default_timezone_set('GMT');

//Connect the redis.
Resque::setBackend('127.0.0.1:6379',4);

//Task content.
$args = array(
        'name' => 'Chris'
        );
//Add data to the queue
//parameters:
//1:消息队列的名称.
//2:第二个参数表示取出任务后,由My_Job这个类来处理此条任务.

/**
 * Create a new job and save it to the specified queue.
 *
 * @param string $queue The name of the queue to place the job in.
 * @param string $class The name of the class that contains the code to execute the job.
 * @param array $args Any optional arguments that should be passed when the job is executed.
 * @param boolean $trackStatus Set to true to be able to monitor the status of a job.
 *
 * @return string|boolean Job ID when the job was created, false if creation was cancelled due to beforeEnqueue
*/
$jobId = Resque::enqueue('default', 'PHP_Job', $args, true);

echo "Queued job ".$jobId."\n\n";