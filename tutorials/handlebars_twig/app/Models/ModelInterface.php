<?php

namespace app\Models;
/* nothing here yet */

interface ModelInterface {
	
		
	/*
	all PUBLICLY ACCESSIBLE METHODS YOU WANT TO FORCE THE CLASS TO HAVE
	*/
	
	public static function get($primary_key_value);   // read: catch all 
	
	/*
	public static function create(); // create
	public static function get();   // read: catch all 
	public static function update(); // delete ...
	public static function delete(); // and update!
	public static function save(); // save or update wrapper
	public static function view(); // customised read per our useage
	public static function is_irregular_plural();  // title/titles vs talent/talent 
	*/
}
