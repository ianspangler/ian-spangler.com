<?php

namespace app\Models;
use app\Services\GearManager;



class Followers extends BaseModel{
	/*
		-- SQL for followers --
		number of followers for each user, ordered by number of followers
		select u.user_id, u.first_name, count(u.user_id) as num_followers, f.* from users u , following f where u.user_id = f.followed_id group by u.user_id ORDER BY `num_followers` DESC

		people following themselves
		select u.user_id, u.first_name, f.* from users u , following f where f.follower_id = f.followed_id and f.followed_id = u.user_id

		people ordered by number of people they are following
		select u.user_id, u.first_name, count(u.user_id) as num_following, f.* from users u , following f where u.user_id = f.follower_id group by u.user_id ORDER BY `num_following` DESC
	*/

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
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'followers_';

	private $follower_id;
	private $followed_id;
	private $datetime;

	public function __construct($config = null){ 
		parent::__construct();

		if(is_array($config) && isset($config['set_size'])){
			self::set_size($config['set_size']);			
		}
		//
		parent::__construct();
		
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Followers();
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

		$sql = "SELECT COUNT(f.followed_id) AS count FROM ".self::$table." f WHERE f.followed_id = ".$user_id;

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
	
	public function get_result($user_id, $set_num = 1) {

		if(isset(self::$result)){
			return self::_get_set($set_num);
		}
		self::$result = array();

		/******CACHING *******/
		$key_name = self::$cache_key_prefix.$user_id;	

		$sql = "SELECT f.id, f.follower_id AS user_id, f.datetime, u.username, u.main_pic, u.oauth_uid,  
		COUNT(p.proposal_id) AS num_proposals 
		FROM ".self::$table." f 
		LEFT JOIN users u ON f.follower_id = u.user_id
		LEFT JOIN proposals p ON p.user_id = f.follower_id AND p.anonymous <> 'Y'
		WHERE f.followed_id = ".$user_id." GROUP BY f.id ORDER BY f.datetime DESC";

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



	/*
		called from post_follow_unfollow
	*/
	public function post_follow($follower_id, $followed_id, $datetime) {

		$this->_set_ids($follower_id, $followed_id, $datetime);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}

		$sql = "INSERT IGNORE INTO following (follower_id, followed_id, datetime)
			VALUES (".$follower_id.", ".$followed_id.", '".$datetime."')";

		return $this->run_action($sql, "followed");
		
	}
	

	/*
		called from post_follow_unfollow
	*/
	public function remove_follow($follower_id, $followed_id) {

		$this->_set_ids($follower_id, $followed_id);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}


		$sql = "DELETE FROM following WHERE follower_id = ".$follower_id." AND followed_id = ".$followed_id;
		
		return $this->run_action($sql, "unfollowed");
	
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

	private function validate(){
		if( ($this->follower_id == "") || ($this->followed_id == "") ) { return false; } 
		if( ((int)$this->follower_id < 1) || ((int)$this->followed_id < 1) ) { return false; }
		if( ($this->follower_id ==  $this->followed_id )) { return false; }
		return true;
	}

	private function _get_set($set_num = 1){		
		$offset = 0;
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}
		return array_slice(self::$result, $offset, self::set_size());
	}

	private function _set_ids($follower_id, $followed_id, $datetime = NULL) {
		$this->follower_id = $follower_id;
		$this->followed_id = $followed_id;
		$this->datetime = $datetime;
	}

	private function run_action($sql, $action) {


		if ($query = self::$myDBHandler->query($sql)) {
			//remember to clear the followers cache!!
			self::$cacheClearer->clear_following_caches($this->follower_id, $this->followed_id);
			
			if ($action == "followed") {
				$this->_start_notification();
			}

			return array("action"=>$action);
			exit();	
		}
		else { //send back an error that the action failed			
			return getError(0, "An error occurred registering follow/ unfollow."); 
			exit();
		}
	} 

	/* 
	this method initiates the spawning of a notification when someone gets followed
	*/
	private function _start_notification() {

		//NOTIFY the followee
		$args = array('follower_id'=>$this->follower_id, 'followed_id'=>$this->followed_id, 'datetime'=>$this->datetime);
		#error_log(__METHOD__." DEBUG starting gearmanager call",1);
		GearManager::process('notify_on_follower_inserted', $args);

	}


}

