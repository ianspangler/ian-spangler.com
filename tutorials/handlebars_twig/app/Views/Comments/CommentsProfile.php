<?php

namespace app\Views\Comments;
use StdClass;
use Exception;

use app\Views\Shared\AbstractProfile;

use app\Models\Comments;
use app\Models\Users; 

class CommentsProfile extends AbstractProfile{

	/* 
		primary id column name in user table
	*/	
	private static $primary_id = 'comments_id';

	/*

	*/
	protected static $cache_key_prefix = 'comments_';
	
	/* 
	this is the data we want everytime we request the primary data 
		eg: so $user will have $user->notifications by default
	*/
	private static $related_data = array();

	public static $comments_model = null;
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
		self::$comments_model = new Comments();
		self::$users_model = new Users($config);

		parent::__construct();
			
	}
 
	/*
        get a user by id or (FUTURE) url_handle
    */
    public static function get_a_user($_mixed){
        self::$user = self::$users_model->get_result((int)$_mixed, array(), array(
            "what"=>array(
                "user_id",
                "last_name",
                "first_name",
                "username"
                )  
        ) );//, self::$related_data);
        if(self::$user == null){ return null; }
        self::$user->pic = self::_get_user_image( self::$users_model->object_to_array(self::$user) );

        return self::$user;
    }

    /** 
		For single Comment page
	*/
	public function get_one($_id) {
		print __FUNCTION__;
		return self::$comments_model->get($_id);
	}	

	/** 
		For Regular Comment list page
	*/
	public function get_all_for_profile($itemtype, $profile_id, $set_num = 1) {
		global $item_type_ids;
		self::$comments_model->set_num($set_num);

		$f_items = array();

		//calculate offset
		$offset = 0;
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}

		//determine limit
		$setsize = self::set_size();
		if ((int)$set_num == 0) {
			$setsize = 1000;
		}
		
		$f_items = self::$comments_model->get_result($profile_id, "", array_search($itemtype, $item_type_ids), array("offset"=>$offset, "limit"=>$setsize) );
		
		$items = array();
		foreach($f_items as $item){
			array_push($items, self::array_to_object( $item ));
		}
				

		/*return array_slice($items, $offset, self::set_size());*/

		return $items;
			
	}

	public static function thread_is_deactivated($item_id, $item_type_id){
		return self::$comments_model->thread_is_deactivated($item_id, $item_type_id);
	}
 

 	public static function get_comments_count($item_id, $item_type){
		return self::$comments_model->get_count((int)$item_id, $item_type);
	}
	
}

