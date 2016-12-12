<?php

namespace app\Models;


class DislikeUndislike extends BaseModel {
	
	private $user_id;
	private $item_id;
	private $item_type;

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
			$inst = new DislikeUndislike();
		}
		return $inst;
	}
	


	/*
		called from post_dislike_undislike
	*/
	public function post_dislike($user_id, $item_id, $item_type, $datetime) {
		
		global $item_type_ids;

		$this->_set_ids($user_id, $item_id, $item_type);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}


		$sql = "INSERT IGNORE INTO dislikes(user_id, item_id, item_type_id, datetime)
								VALUES (".$user_id.", ".$item_id.", ".$item_type_ids[$item_type].", '".$datetime."')
						ON DUPLICATE KEY UPDATE active=1";
								
		return $this->run_action($sql, "added");
		
	} 


	// remove dislike
	public function remove_dislike($user_id, $item_id, $item_type) {

		global $item_type_ids;

		$this->_set_ids($user_id, $item_id, $item_type);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}


		$sql = "UPDATE dislikes SET active = 0 
				WHERE user_id =".$user_id." AND item_id =".$item_id." AND item_type_id =".$item_type_ids[$item_type];
		

		return $this->run_action($sql, "removed");
	
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

	private function run_action($sql, $action) {
	
		if ($query = self::$myDBHandler->query($sql)) {
			
			//clear the dislikes caches!!
			///clear_fans_cache_for_profile($this->item_id, $this->item_type);
			//clear_disliked_cache($this->user_id, $this->item_id, $item_type_ids[$this->item_type]);
			self::$cacheClearer->clear_supporters_count_for_profile($this->item_id, $this->item_type);
			self::$cacheClearer->clear_likesdislikes_cache($this->item_id, $this->item_type); //for post page
			self::$cacheClearer->clear_likesdislikes_list_cache($this->item_type); //for list page

			return array("action"=>$action);
			exit();
			
		}
		else { //send back an error that the action failed			
			return getError(0, "An error occurred registering dislike/undislike."); 
			exit();
		}

	} 

	private function validate(){
		if( ($this->user_id == "") || ($this->user_id == "") ) { return false; } 
		if( ((int)$this->item_id < 1) || ((int)$this->item_id < 1) ) { return false; }
		return true;
	}

	private function _set_ids($user_id, $item_id, $item_type) {
		$this->user_id = $user_id;
		$this->item_id = $item_id;
		$this->item_type = $item_type;
	}
	
	

}

