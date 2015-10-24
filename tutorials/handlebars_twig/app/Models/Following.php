<?php

namespace app\Models;

class Following extends BaseModel{

	protected static $table = 'following';
	/* 
		only these columns will be pulled from user table
	*/	
	private static $allowed_fields = array('id','follower_id', 'followed_id', 'datetime');
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'id';
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
	protected static $cache_key_prefix = 'following_';


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
			$inst = new Following();
		}
		return $inst;
	}
	
	public function get_count($user_id){ 

		if(isset(self::$result_num)){
			#print " A ";
			return self::$result_num;
		}else if(isset(self::$result)){
			#print " B ";
			self::$result_num = count(self::$result);
			return self::$result_num;
		}

		/******CACHING *******/
		$key_name = self::$cache_key_prefix."num_".$user_id;	

		$sql = "SELECT COUNT(f.follower_id) AS count FROM following f WHERE f.follower_id = ".$user_id;

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		$result = self::$myDBHandler->get($sql, $key_name, $expires);
		
		if (is_array($result)) {
			self::$result_num = $result[0]['count'];
		 	return $result[0]['count'];
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		} 
	}

	public function get_result($user_id, $set_num = 1){ 
		
		// if we already have a result and its for this user, then retreive it from memory
		if(isset(self::$result) && isset(self::$result[0]) && self::$result[0]['user_id'] == $user_id){
			return self::_get_set($set_num);
		}
		self::$result = array();

		/******CACHING *******/
		$key_name = self::$cache_key_prefix."page_".$user_id;	

		$sql = "SELECT f.id, f.followed_id AS user_id, f.datetime, u.username, u.main_pic, u.oauth_uid, 
		COUNT(p.proposal_id) AS num_proposals 
		FROM ".self::$table." f 
		LEFT JOIN users u ON f.followed_id = u.user_id
		LEFT JOIN proposals p ON p.user_id = f.followed_id AND p.anonymous <> 'Y'
		WHERE f.follower_id = ".$user_id." GROUP BY f.id ORDER BY f.datetime DESC";

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		$result = self::$myDBHandler->get($sql, $key_name, $expires);
		
		if (is_array($result)) {
		 	foreach($result as $row){
		 		/* call global functions */		
		 		$row['user_url'] = get_url_for_user($row['user_id']);
				$row['user_pic'] = get_user_profile_image( $row['user_id'], $row['main_pic'], $row['oauth_uid'], array('size'=>'SM') );

				array_push(self::$result, $row);
		 	} 
		 	self::$result_num = count(self::$result);
		 	$set = self::_get_set($set_num); 			
			return $set;

		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		} 
	}

	private function _get_set($set_num = 1){	
		$offset = 0;
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		} 		
		return array_slice(self::$result, $offset, self::set_size());
	}

	/*
		this method gets all followed user id(s) for the given follower id
	*/
	public function get_user_followed_ids($user_id, $options = array()) {

		$offset_clause = "";
		$limit_clause = ""; 

		if(isset($options['OFFSET'])){
			$limit_clause = " OFFSET ".(int)$options['OFFSET'];
		}

		if(isset($options['LIMIT'])){
			$offset_clause = " LIMIT ".(int)$options['LIMIT'];
		}
		
		/******CACHING *******/
		$key_name = self::$cache_key_prefix."ids_".$user_id;

		$sql = "SELECT followed_id FROM following WHERE followed_id <> ".$user_id." AND follower_id = ".$user_id. $limit_clause . $offset_clause ;

		//print($sql);
		//exit;

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

		$filter = array( 
		);

    	return filter_var_array($pairs);//, $filter);

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

