<?php


#ini_set("log_errors", 1);
#ini_set("error_log", "/tmp/php-error.log");
#error_log( "START of error log" );

	/* 
		every file gets this line. nothing goes above it.
	*/
	$app_root = "";
	$parts = explode("/", __DIR__);
	$app_root = implode("/", array_slice($parts, 0, count($parts)-3));

	require_once $app_root . '/app/index_gearman.php';
	use app\Workers\NotificationWorker;

	$gmw = new NotificationWorker();
	$gmw->init();

#error_log( "END of error log" );





