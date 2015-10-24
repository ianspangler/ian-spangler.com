<?php

namespace app\Models;
use app\Services\GearManager;


class LikeUnlike extends BaseModel {
	
	private $user_id;
	private $item_id;
	private $item_type;
	private $item_type_id;

	//auto-like IDs
	private $talent_id;
	private $role_id;
	private $title_id;

	private $datetime;

	//stores whether the primary like is an insert (new like) or update (re-like)
	private $inserted_new = false; 


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
			$inst = new LikeUnlike();
		}
		return $inst;
	}
	

	/*
		called from post_like_unlike
	*/
	public function post_like($user_id, $item_id, $item_type, $talent_id, $role_id, $title_id, $datetime) {
		
		global $item_type_ids;

		$this->_set_ids($user_id, $item_id, $item_type, $talent_id, $role_id, $title_id, $datetime);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}
		
		//INSERT THE PRIMARY LIKE
		$sql = "INSERT IGNORE INTO likes (user_id, item_id, item_type_id, datetime, auto_like)
									VALUES (".$user_id.", ".$item_id.", ".$item_type_ids[$item_type].", '".$datetime."', 0)";
		
		$sql .= " ON DUPLICATE KEY UPDATE active=1";


		//INSERT THE AUTO-LIKES
		if ($item_type == "proposal" || $item_type == "credit") {
			
			//we are counting each proposal and credit like as a vote of support for each associated profile (talent, role, title)
			$autolike_sql = "INSERT IGNORE INTO likes (user_id, item_id, item_type_id, datetime, auto_like) VALUES ";
			
			//all proposals & credits have a talent associated - so no need to check if talent_id is set
			$autolike_sql .= "(".$user_id.", ".$talent_id.", ".$item_type_ids['talent'].", '".$datetime."', 1)";
																		
			
			//only casting proposals & credits have a role associated - we should check that the role_id is set
			if ($role_id != NULL) {
				$autolike_sql .= ", (".$user_id.", ".$role_id.", ".$item_type_ids['role'].", '".$datetime."', 1)";
			}
			
			//some casting proposals don't have a title associated -- we should check that the title_id is set				
			if ($title_id != NULL) {
				if ($item_type == "proposal") { $title_type_id = $item_type_ids['title']; }
				else if ($item_type == "credit") { $title_type_id = $item_type_ids['movie_title']; } 
				
				$autolike_sql .= ", (".$user_id.", ".$title_id.", ".$title_type_id.", '".$datetime."', 1)";

			}

			$autolike_sql .= " ON DUPLICATE KEY UPDATE active=1";

			$this->run_action($sql, "added", false);
			return $this->run_action($autolike_sql, "added", true, true);
			
		}
		else { //story, talent, or role

			return $this->run_action($sql, "added", true);
		}
		
	} 


	// remove like
	public function remove_like($user_id, $item_id, $item_type) {

		global $item_type_ids;

		$this->_set_ids($user_id, $item_id, $item_type);

		if(!$this->validate()){ 
			return array("status"=>"fail");
			exit();
		}


		$sql = "UPDATE likes SET active = 0
				WHERE user_id=".$user_id." AND item_id=".$item_id." AND item_type_id=".$item_type_ids[$item_type];
		
		return $this->run_action($sql, "removed", true);
	
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

	private function run_action($sql, $action, $return_response = false, $autolike = false) {
	
		global $item_type_ids;

		if ($query = self::$myDBHandler->query($sql)) {
			
			if ($return_response) { //last action run
				
				//clear the likes caches!!
				////clear_fans_cache_for_profile($this->item_id, $this->item_type);
				self::$cacheClearer->clear_liked_cache($this->user_id, $this->item_id, $item_type_ids[$this->item_type]);
				self::$cacheClearer->clear_supporters_count_for_profile($this->item_id, $this->item_type);
				/////clear_cache_for_rollovers($this->item_id, $this->item_type);
				self::$cacheClearer->clear_likesdislikes_cache($this->item_id, $this->item_type); //for post page
				self::$cacheClearer->clear_likesdislikes_list_cache($this->item_type); //for list page
				
				if ($autolike) { 
					self::$cacheClearer->clear_supporters_count_for_profile($this->talent_id, "talent");
					self::$cacheClearer->clear_supporters_count_for_profile($this->role_id, "role");
					self::$cacheClearer->clear_supporters_count_for_profile($this->title_id, "title");
				}

				if ($autolike) { $inserted = $this->inserted_new; } else { $inserted = $this->getInsertedOrUpdated(); }
				
				if ($action == "added" && $inserted) { //not a re-like
					///print("inserted: ".$inserted);
					$this->startNotifications();
					////$return_arr['inserted_new'] = $inserted; 
				}

				///return $return_arr;
				return array("action"=>$action);
				exit();
			} 
			else { //not the last action run
				$this->inserted_new = $this->getInsertedOrUpdated();
			}	
		}
		else { //send back an error that the action failed			
			return getError(0, "An error occurred registering like/unlike."); 
			exit();
		}

	} 

	private function getInsertedOrUpdated() {

		//print(self::$myDBHandler->affected_rows());
		if (self::$myDBHandler->affected_rows() !== 2) { //new row inserted
			return true;
		}
		//else if (self::$myDBHandler->affected_rows() == 2) { //existing row updated
		return false;
		//}
	}

	private function startNotifications() {

		global $item_type_ids;

		//NOTIFY on proposal, story, or talent like
		$item_type_id = $item_type_ids[$this->item_type];

		if ($item_type_id == 1 || $item_type_id == 3 || $item_type_id == 5) {
		
			$args = array('item_id'=>$this->item_id, 'item_type_id'=>$item_type_id, 'user_id'=>$this->user_id, 'datetime'=>$this->datetime);

			////print_r($args);

			GearManager::process('notify_on_like_inserted', $args);
		}	
	
	}

	private function validate(){
		if( ($this->user_id == "") || ($this->user_id == "") ) { return false; } 
		if( ((int)$this->item_id < 1) || ((int)$this->item_id < 1) ) { return false; }
		return true;
	}

	private function _set_ids($user_id, $item_id, $item_type, $talent_id = NULL, $role_id = NULL, $title_id = NULL, $datetime = NULL) {
		$this->user_id = $user_id;
		$this->item_id = $item_id;
		$this->item_type = $item_type;
		$this->talent_id = $talent_id;
		$this->role_id = $role_id;
		$this->title_id = $title_id;
		$this->datetime = $datetime;
	}

}
