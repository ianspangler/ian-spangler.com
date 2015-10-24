<?php

namespace app\Views\User;
use StdClass;
use Exception;

use app\Views\Shared\AbstractProfile;

use app\Models\Followers;
use app\Models\Following;
use app\Models\Users;
use app\Models\Activity;

class UserProfile extends AbstractProfile{
		
	/* 
		primary id column name in user table
	*/	
	private static $primary_id = 'user_id';
	 
	/*

	*/
	protected static $cache_key_prefix = 'uprofile_';
	

	public function __construct($set_size, $set_num = 1){

		self::set_size($set_size);
		self::set_num($set_num);

		/*
			create the models that will be needed for this profile
		*/
		$config = array(
			'set_size'=>self::set_size()
		);
		/* 
			lets name all our DB based vars 
			[]_model to make it more clear
		*/
		self::$followers_model = new Followers($config);
		self::$following_model = new Following($config);
		self::$users_model = new Users($config);
		self::$activity_model = new Activity($config);
		parent::__construct();
			
	}

	/*
		get a user by id or (FUTURE) url_handle
	*/	
	public static function get_a_user($_mixed){
 		
 		self::$user = self::$users_model->get_result($_mixed);

		if(self::$user == null){ return null; }	

		self::$user->num_followers = self::$followers_model->get_count(self::$user->user_id);
		self::$user->num_following = self::$following_model->get_count(self::$user->user_id);	

		return self::$user;
	}
		
	/*
		based on the user id and optional filter, 
		get the initial full ID set and add the activity item to the user object
	*/	
	public static function get_user_activity($_mixed, $type = null){

		$activity = array();

		if(self::$user == null){ 
			self::$user = self::get_a_user((int)$_mixed);
		}
		if(self::$user == null){  return null; }

		if(isset(self::$user->activity) && self::$user->user_id == (int)$_mixed){  
			if( null !== $type ){ 
				return self::_get_user_activity_filtered($type);
			}
			return self::$user->activity;
		}

		// get activity
		self::$activity_model->set_num( (int)self::set_num() );
		try{
			$result = self::$activity_model->get_result((int)$_mixed, self::$cache_key_prefix, null);
		} catch (Exception $e) { 	
			throw new Exception(__CLASS__ ." (1)Error Processing Request", 1);
		}
		if (!is_array( $result)) {
			throw new Exception(__CLASS__ ." (2)Error Processing Request", 1);
			return array();	
		}
	
		foreach($result as $row){
			array_push($activity,  self::array_to_object( $row )) ;
		} 
		self::$user->activity = $activity;
		self::$user->num_activity = self::get_user_activity_count(null);
		
		// return the activity object , filtered by type
		if( null !== $type ){ 
			return self::_get_user_activity_filtered($type);
		}
		// return the user object(with activity)
		return self::$user;
	 

		return null;
	}	

	public static function get_user_activity_count($type = null){

		if($type == false && isset(self::$user->activity) ){
			return count(self::$user->activity);
		}
		return count( self::_get_user_activity_filtered($type) );
	}
	
	/* 
		get and individual item from the list using its index or a combination of id and type
		$id can be an index or the id of the item
		$type is required to access by id because two items(ie: comment and a like) could have the same id
	*/
	public static function get_activity_item($id = null, $type = null){
		if($id == null || (int)$id < 0){ return new StdClass(); }	
		/*
			we are accessing by its index in the array
			ie: the Nth item
		*/
		if($type == null && $id != null){ 

			return @self::$user->activity[(int)$id];
		
		}else if($id != null){
			foreach(self::$user->activity as $item){
				if((int)$item->id == (int)$id && $item->activity_type == $type){
					return $item;
				}
			}
		}

		return new StdClass();
	}
	
	/*
		get a set of activity items 
		grabs the full list of ids and slices it and 
	*/	
	public function get_user_activity_set($set_num = 1){
		if(self::$user == null){  print "USER IS NULL "; return null; }

		$offset = 0;
		$activity = null;
		
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}

		$sliced = array_slice(self::$user->activity, $offset, self::set_size());
		self::set_num($set_num);
		self::$activity_model->set_num($set_num);
		
		return self::$activity_model->get_activity_sub_type( 
			self::$activity_model->get_activity_details( 
				$sliced, 
				self::$user->user_id,
				array('order_by_date'=>true,
					'key_name'=>'user_prof')
				) 
			); 
	}
  
	/*******************	PRIVATE	METHODS		******************/

	/*
		get all activities of a certain type, or all if no type is defined
	*/	
	private static function _get_user_activity_filtered($type = null){
		$items = array(); 

		// get top level activity
		if(!isset(self::$user->activity)){
			self::$user->activity = self::get_user_activity(self::$user->user_id, null);
		}

		foreach(self::$user->activity as $item){
			if(!isset($type)){
				array_push($items, $item);
			}else if( $item->activity_type == $type){
				array_push($items, $item);
			}else{ 
			}
		}
		
		return $items;
	}

	
}

