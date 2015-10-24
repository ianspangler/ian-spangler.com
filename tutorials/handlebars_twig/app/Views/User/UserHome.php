<?php

namespace app\Views\User;
use StdClass;
use Exception;

use app\Views\Shared\AbstractProfile;

use app\Models\Followers;
use app\Models\Following;
use app\Models\Users;
use app\Models\News;
use app\Models\Notifications;
use app\Models\NotificationMessage;

class UserHome extends AbstractProfile{

	/* 
		primary id column name in user table
	*/	
	private static $primary_id = 'user_id';

	/*

	*/
	protected static $cache_key_prefix = 'uhome_';
	
	private static $news = null;
	// private static $notifications = null;
	private static $notification_count = null;
	/* 
	this is the data we want everytime we request the primary data 
		eg: so $user will have $user->notifications by default
	*/
	private static $related_data = array('notifications');

	protected static $notif_message_model = null;
	protected static $notifications_model = null;

	public function __construct($set_size = 30, $set_num = 1){

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
		self::$news_model = new News();
		self::$notifications_model = new Notifications();
		self::$notif_message_model = new NotificationMessage();

		parent::__construct();
			
	}
	/*
		get a user by id or (FUTURE) url_handle
	*/	
	public static function get_a_user($_mixed){
 		self::$user = self::$users_model->get_result((int)$_mixed, self::$related_data);
		if(self::$user == null){ return null; }	
		return self::$user;
	}

	/*
		get a user object with news
	*/
	public function get_user_news($_mixed, $type = null){
		$news = array();
		 
		if(self::$user == null){ 
			self::$user = self::get_a_user((int)$_mixed);
		}
		if(self::$user == null){  return null; }

		if(isset(self::$user->news) && (int)self::$user->user_id == (int)$_mixed){  
			// with news!
			return self::$user;
		}else{
			self::$user->news = self::_get_news((int)$_mixed, $type);
		}

		return self::$user;
	}
	
	/*
		get a user object with notifications
	*/
	public function get_user_notifications($_mixed, $type = null){
		$notifications = array();

		if(self::$user == null){ 
			self::$user = self::get_a_user((int)$_mixed);
		}
		if(self::$user == null){  return null; }

		if(isset(self::$user->notifications) && (int)self::$user->user_id == (int)$_mixed){  
			
			#print("Notification Rows= " .count(self::$user->notifications)).PHP_EOL;
			
			// with notifications!
			return self::$user;
		}else{
			self::$user->notifications = self::_get_notifications((int)$_mixed, $type);
		}

		return self::$user;
	}

	public static function get_user_news_count($type = null){
		return self::$news_model->get_news_count();
	}

	public static function get_notifications_details($_mixed, $set_num = 1){
		global $notify_action_ids;
		
		// ensure the model knows which set to get
		self::$notifications_model->set_num($set_num);

		// get the aggregted data from the notifications table
		$data = self::$notifications_model->get_message_data(self::$user->user_id, (int)$set_num);

		// each item in the list gets a sentance
		$data_updated = array();
		
		// sort by date
		$data = self::_sort_by_date( $data );
		foreach($data as $item){
			// each item needs additional data
			$kw = array_search($item['action_id'], $notify_action_ids);
			$f = "get_message_for_".$kw;
			// here we get data from other models and apply some logic for what data to send back to the front end
			$item = self::$notif_message_model->get_message($item, $f);// {}($item);
			$data_updated[] = $item;

	  	}
	  	// set notification count
	  	self::$notification_count = self::$notifications_model->get_total_message_count(self::$user->user_id);//count($data_updated);
	  	return $data_updated;
	}
	 
	public static function set_active($where_array, $properties_array){
	  	return self::$notifications_model->set_active_for_related_notifications($where_array, $properties_array);
	}

	//
	
	/* 
		call to clear notification count
		called whenever the user is viewing his notifications page
	*/
	public function clear_unread_message_count(){
		return self::$notifications_model->clear_unread_message_count(self::$user->user_id);
	}  

	// get notification count
	public function get_total_message_count(){
		if(!isset(self::$notification_count)){
			self::$notification_count = self::$notifications_model->get_total_message_count(self::$user->user_id);
		}
		return self::$notification_count;
	}  

	/*
		get a set of activity items 		
		!!!!!!
			due to the huge size of this result, we already sliced this in get_result  
		!!!!!
	*/	
	public function get_user_news_set($set_num = 1){
		if(self::$user == null){  
			throw new Exception(__METHOD__." USER IS NULL "); 
			return null;
		}
		if(isset(self::$user->news) == null){  
			throw new Exception(__METHOD__." USER NEWS IS NULL "); 
			return null; 
		}

		// ensure we have the right set
		self::set_num($set_num);
		
					// use the parent's function   ---------------------------- \/ use the parent's function
		return self::$news_model->get_activity_sub_type( 
			self::$news_model->get_activity_details(
				self::$user->news, 
				self::$user->user_id, 
				array('order_by_date'=>true,
					'key_name'=>'user_hom')
				) 
			); 
	}

	private static function _sort_by_date( $data ){
		usort($data,  function($a, $b) {
		    return strtotime($b['last_updated']) - strtotime($a['last_updated']);
		});
		return $data;
	}

	/*
		get news items array from News Model
	*/
	private function _get_news($_mixed, $type = null){
		
 		$news = array();

 		// ensure the model knows which set to get
 		self::$news_model->set_num(self::set_num());

		$result = self::$news_model->get_result($_mixed, self::$cache_key_prefix, null);
		
		if (is_array($result)) {
			foreach($result as $row){
				array_push($news, self::array_to_object( $row )) ;
			} 
			// return the user object(with news)
			return $news;
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
	}

	/*
		get notification items array from notifications Model
	*/
	private function _get_notifications($_mixed, $type = null){
		
 		$notifications = array();
		$result = self::$notifications_model->get_result($_mixed, self::$cache_key_prefix, null); 

		if (is_array($result)) {

			foreach($result as $row){
				array_push($notifications, self::array_to_object( $row ));
			} 
			
			// return the user object(with notifications)
			self::$user->notification_count = self::$notifications_model->get_total_message_count(self::$user->user_id);//count($notifications);
			return $notifications;

		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
	}
	
}

