<?php
	
	/*
		this is the base file for all calls to the Server side of Gearman Operations
	*/
	$app_root = "";
	$parts = explode("/", __DIR__);
	$app_root = implode("/", array_slice($parts, 0, count($parts)-1)); 

	define('DOCROOT', $app_root);
	
	require_once DOCROOT . '/includes/app_gearman_global.php';   	
	
	/** 
	 / end Global stuff
	**/
