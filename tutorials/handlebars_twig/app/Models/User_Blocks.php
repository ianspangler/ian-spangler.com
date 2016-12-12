<?php

namespace app\Models;
use Exception;


class User_Blocks extends BaseModel{

	protected static $table = 'user_blocks';
	
	protected static $use_db = CHAT_DB;//'IflistMessages';

	/* 
		only these columns will be pulled from user table
	*/	
	private static $allowed_fields = array('user_blocks_id','user_id', 'blocked_user_id', 'created_at', 'updated_at');
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'user_blocks_id';
	/* 
		object to store
	*/	
	private static $result = null;
	/*
		holder for the count
	*/
	private static $result_num = null;
	/*

	*/
	protected static $cache_key_prefix = 'blocks_';


	public function __construct($config = null){ 

		if(is_array($config) && isset($config['set_size'])){
			self::set_size($config['set_size']);			
		}
		//
		parent::__construct();
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new User_Blocks();
		}
		return $inst;
	}
	 
	/*
		
	*/
	public static function block_user($_user_id, $_blocked_user_id, $options = array()) {
		// will switch DB if needed
        self::_use_alternate_db();

		$now = getNow();
        // $user_id, $recipient_id,
        $created_at = $updated_at = $now;

        $sql = "INSERT INTO ".self::$table." (user_id, blocked_user_id, created_at, updated_at) 
		VALUES($_user_id, $_blocked_user_id, '$created_at', '$updated_at') " ;

		$result = self::_handle_db_query($sql, array("alt_db"=>@$options['alt_db'] ) );

		if ($result) {

			self::$cacheClearer->clear_cache_for_blocks($_user_id, $_blocked_user_id);

			return true;
		}
		else {
			$err = 'ERROR '.self::$myDBHandler->error(true);
			error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
			throw new Exception(__CLASS__.':'.__FUNCTION__ . " (3) Error Un Blocking User($err)", 1);

			print self::$myDBHandler->error(DEBUGGING);
			return false;	
		}
		
	}

	public static function unblock_user($_user_id, $_blocked_user_id, $options = array()) {
		// will switch DB if needed
        self::_use_alternate_db();
		
        $result = self::delete(array("user_id"=>$_user_id, "blocked_user_id"=>$_blocked_user_id), array("alt_db"=>@$options['alt_db']) );

        self::$cacheClearer->clear_cache_for_blocks($_user_id, $_blocked_user_id);
        return $result;        
		
	}

	public static function is_blocked($_user_id, $_blocked_user_id, $options = array()) {
		// will switch DB if needed
        self::_use_alternate_db();
		
        $sql = "SELECT ".implode(",",self::$allowed_fields)." FROM ".self::$table." WHERE user_id = $_user_id AND blocked_user_id = $_blocked_user_id" ;

		try{
			$result = self::_handle_db_query($sql, array(self::$primary_id=>$_user_id."_".$_blocked_user_id, "key_name"=>"isblckd_", "alt_db"=>@$options['alt_db'] ) );
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage();
            error_log(	__FUNCTION__ . " ERROR ".$e->getMessage());
            return false;
		}

		// we get an array even if nothing found
		if (is_array($result)) {
			// if the array not empty then we got something
			if (count($result) > 0) {
				return true;
			}
			// otherwise there was no record found. so false
			return false;			
		} 
		else {
			$err = 'ERROR '.self::$myDBHandler->error(true);
			error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
			if(DEBUGGING) print __CLASS__.':'.__FUNCTION__ . " (3) Error Un Blocking User($err)";

			print self::$myDBHandler->error(DEBUGGING);
			return false;	
		}
	}	
	/*
		required by interface
		to be used to attach secondary and associated data
	*/
	protected function _has_relationships(){
		//self::$has_many ;
	} 

	/*
	this needs to match the schema
	*/
	protected function _get_filtered_values($pairs){

		$filter = array( );
		$arr = filter_var_array($pairs);//, $filter);
		
		/*
			filter out blank values
		*/
		$arr = array_filter($arr, function ($item) use (&$arr) {
		    if($arr[key($arr)] == ""){ next($arr); return false; }
		    next($arr);
		    return true;
		});

    	return $arr;

	}	
	/*
		this method gets the followed id(s) that match for the given follower id
	*/
	/*public function get_user_is_following($user_id, $user_ids_arr) {

		
		$key_name = self::$cache_key_prefix.$user_id."-".implode(',',$user_ids_arr);

		$sql = "SELECT followed_id FROM following WHERE follower_id = ".$user_id." AND followed_id IN (".implode(',',$user_ids_arr).")";

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		$result = self::$myDBHandler->get($sql, $key_name, $expires);

		if (is_array($result)) {

			$followed_user_ids_arr = array();
		 	foreach($result as $row){
				//print('<br>user_id: '.$row['followed_id']);
				array_push($followed_user_ids_arr, $row['followed_id']);
			}

			return $followed_user_ids_arr;
		}
		else {
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
		
		
	}
	*/

}

