<?php
	/**
	 * File name: dbconnect.php
	 *
	 * Description: A shared file for connecting to the DB
	 *
	 */
	use shared\Services\DB\DBHandler;

	$dbHandler = new DBHandler();
	 
	$dbHandler->query("SET CHARACTER SET utf8");
	$dbHandler->query("SET COLLATION_CONNECTION = 'utf8_bin'");
	$dbHandler->query("SET SQL_BIG_SELECTS=1");
	$dbHandler->query("SET group_concat_max_len = 4000");

 