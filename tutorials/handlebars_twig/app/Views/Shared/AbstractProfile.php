<?php
namespace app\Views\Shared;
use StdClass;
use app\Shared\AbstractClass;

abstract class AbstractProfile extends AbstractClass{

	private static $table;
	/* 
		user object to store
	*/	
	protected static $user; 
	/*
	*/
	protected static $cache_key_prefix = null;
		
	private static $set_size = null;

	private static $set_num = null;

	/*
		required models
		lets name all our DB based vars 
		[TABLE]_model to make it more clear
		or, when its an abstraction (eg:activity) name it
		[abstraction_name]_model
	*/
		// WHY Are these Here?
	static $followers_model = null;
	static $following_model = null;
	static $users_model = null;
	static $activity_model = null;
	static $news_model = null;

	protected static $myDBHandler = null;


	public function __construct(){
		global $dbHandler;
		if(!$dbHandler){ throw new Exception(__CLASS__." No DB Connection"); print "No DB Connection"; die; }	
		self::$myDBHandler = $dbHandler;
	}

	
	/**
	*
	*
	* Model-related functions accessible to any class that inherits from AbstractProfile
	*
	*
	**/


	/******** 	FOLLOWER METHODS 	*********/
	public static function get_user_followers($user_id, $set_num = 1){
		self::$user->followers = self::$followers_model->get_result((int)$user_id, (int)$set_num);
		return self::$user->followers;
	}

	public static function get_user_followers_count($user_id){
		return self::$followers_model->get_count((int)$user_id);
	}	

	/******** 	FOLLOWING METHODS 	*********/
	public static function get_user_following($user_id, $set_num = 1){
		self::$user->following = self::$following_model->get_result((int)$user_id, (int)$set_num);		
		return self::$user->following;
	}

	public static function get_user_following_count($user_id){
		return self::$following_model->get_count((int)$user_id);
	}	



/**
*
*
* Utilities
*
*
**/

	/*
		a getter/setter
		function($arg) sets the value
		function() gets the value
	*/
	public static function set_num($val = null){
		if($val != null){
			self::$set_num = $val;
		}
		return self::$set_num;
	} 
	/*
		a getter/setter
		function($arg) sets the value
		function() gets the value
	*/
	public static function set_size($val = null){
		if($val != null){
			self::$set_size = $val;
		}
		return self::$set_size;
	} 

	protected static function array_to_object( $row ){
        if($row == null){ return null; }
        
        $ob = new StdClass();
        
        foreach($row as $k=>$v){ $ob->$k = $v; } 
        return $ob;
    } 


}
