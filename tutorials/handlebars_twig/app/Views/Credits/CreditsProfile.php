<?php

namespace app\Views\Credits;
use StdClass;
use Exception;

use app\Views\Shared\AbstractProfile;
use app\Models\Activity;
use app\Models\Credits;
use app\Models\Users;


class CreditsProfile extends AbstractProfile{
	/* 
		primary id column name in user table
	*/	
	private static $primary_id = 'credits_id';

	/*

	*/
	protected static $cache_key_prefix = 'credits_';
	
	/* 
	this is the data we want everytime we request the primary data 
		eg: so $user will have $user->notifications by default
	*/
	private static $related_data = array();
	public static $credits_model = null;
	public static $users_model = null;

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
		self::$credits_model = new Credits($config);
		//self::$users_model = new Users($config);

		parent::__construct();
			
	}
  
	public function get_list_count($credit_type = "casting") {
		return self::$credits_model->get_count($credit_type);
	}

	/** 
		For single Credits page
	*/
	public function get_one($_id) {
		print __FUNCTION__;
		return self::$credits_model->get_result($_id);

	}	
	/** 
		For Regular Credits list page
	*/
	public function get_all($set_num = 1, $credit_type = "casting") {
		return self::_build_from_activity($set_num, "all", $credit_type);
	}
	 
 
	public function get_featured($set_num = 1, $credit_type = "casting") {
		 ///print __FUNCTION__;
		return self::_build_from_activity($set_num, "featured", $credit_type);
	}

	/** 
		this method calls Activity model to instantiate activity-type items
	*/
	private function _build_from_activity($set_num, $list_type = "list", $credit_type) {
		// set the set number
		self::$credits_model->set_num($set_num);

		$f_items = array();
		if ($list_type == "featured") {
			$f_items = self::$credits_model->get_featured_items($credit_type);
		}
		else {
			$f_items = self::$credits_model->get_items($credit_type);
		}
		
		$items = array();
		foreach($f_items as $item){
			array_push($items, self::array_to_object( $item ));
		}
				
		$offset = 0;
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		} 

		$activity_model = new Activity();
		$sliced_items = array_slice($items, $offset, self::set_size());

		/* add numbering */
		$num = $offset+1;
		foreach($sliced_items as $item){
			if($num < 100){
				$item->item_number = $num;
				$num++;
			}else{
				$item->item_number = "";
			}	
		}

		/* get all the 'more' details */
		self::set_num($set_num);
		$activity_model->set_num($set_num);

		return $activity_model->get_activity_sub_type(
			$activity_model->get_activity_details(
				$sliced_items, 
				0, 
				array('order_by_date'=>false, 
					'key_name'=>$credit_type.$list_type)
			)
		);	
	}
	 
 	public function get_featured_list_count($credit_type = "casting") {
		//print __FUNCTION__;
		return self::$credits_model->get_featured_count($credit_type);
	} 
	
}

