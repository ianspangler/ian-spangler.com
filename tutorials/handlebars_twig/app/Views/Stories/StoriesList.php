<?php

namespace app\Views\Stories;
use app\Models\Titles;
use app\Models\Activity;
use app\Views\Shared\ListItem;
use app\Views\Shared\AbstractProfile;

use StdClass;
use Exception;


class StoriesList extends AbstractProfile {

	private static $stories_model = null;

	protected static $cache_key_prefix = 'sl_';

	public function __construct($set_size = 30, $set_num = 1){

		self::set_size($set_size);
		self::set_num($set_num);

		/*
			create the models that will be needed for this profile
		*/
		$config = array(
			'set_size'=>self::set_size()
		);

		//create instance of titles model
		self::$stories_model = new Titles();
		
		parent::__construct();
			
	}

	public function get_list_count() {
		return self::$stories_model->get_titles_count();	
	}

	/** 
		For Regular Stories page
	*/
	public function get_all($set_num = 1) {

	}

	/** 
		For Featured Stories page: this method calls Activity model to 
		instantiate activity-type items
	*/
	public function get_featured($set_num = 1) {

		$offset = 0;
		$story_items = self::$stories_model->get_featured_story_items();

		$stories_items = array();
		foreach($story_items as $item){
			array_push($stories_items, self::array_to_object( $item ));
		}

		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}
		
		$activity_model = new Activity();

		$sliced_stories = array_slice($stories_items, $offset, self::set_size());
		self::set_num($set_num);
		$activity_model->set_num($set_num);

		$list_item_class = new ListItem();	
		$story_activity_items = $list_item_class->get_activity_sub_type( 
				$activity_model->get_activity_details(
					$sliced_stories, 
						0,
						array(
						'key_name'=>'feat_stor')
					)
			);
		
		return $story_activity_items;
	
	}
	



}