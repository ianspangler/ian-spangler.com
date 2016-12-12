<?php

namespace app\Views\Shared;
use app\Services\ItemBuilder;

class ListItem {

	public function __construct(){
			
	}

	public static function Instance(){
		
		static $inst = null;
		if ($inst === null) {
			$inst = new ListItem();
		}
		return $inst;
	}

	/*
		similar to whats in Activity
	*/
	public static function get_activity_sub_type($activities){
		/*
			get the id of the item that was liked by calling internal method to match
		*/			
		$activity_ids = array_map(array(__CLASS__, 'get_item_id_for_like'), $activities);

		// we have to pass a copy of this so that we can use the current_user_id 
		$item_builder = new ItemBuilder(static::Instance());

		/*
			go get the ids of everything liked by this user
		*/
		$my_liked_ids = $item_builder->get_liked_ids($activity_ids);

		/*
			go get the ids of everything disliked by this user
		*/
		$my_disliked_ids = $item_builder->get_disliked_ids($activity_ids);

		$activities = $item_builder->init($activities, $my_liked_ids, $my_disliked_ids);

		return $activities;
	}

	public static function get_item_id_for_like($activity_item){
		
		#print "IN ".__METHOD__.PHP_EOL;
		
		switch ($activity_item->activity_type) {
			case 'credit':
				#print $activity_item->activity_type ." GOT ".$activity_item->id .PHP_EOL;
				$activity_item->likeable_item = $activity_item->id;
				return $activity_item->id; 
				break;
			case 'like_credit':
				#print $activity_item->activity_type ." GOT ".$activity_item->talent_id .PHP_EOL;
				$activity_item->likeable_item = $activity_item->talent_id;
				return $activity_item->talent_id; 
				break;
			
			case 'support_proposal':
				#print $activity_item->activity_type ." GOT ".$activity_item->more->talent_id .PHP_EOL;
				$activity_item->likeable_item = $activity_item->more->talent_id;
				return $activity_item->more->talent_id; 
				break;
			case 'proposal':
				#print $activity_item->activity_type ." GOT ".$activity_item->more->id .PHP_EOL;
				$activity_item->likeable_item = $activity_item->more->id;
				#print_r($activity_item);
				return $activity_item->more->id; 
				break;
			
			case 'role':
				#print " GOT ".$activity_item->more->id ." ".$activity_item->activity_type .PHP_EOL;
				$activity_item->likeable_item = $activity_item->id;
				return $activity_item->id; 
				break;

			case 'title':
				#print " GOT ".$activity_item->more->id ." ".$activity_item->activity_type .PHP_EOL;
				$activity_item->likeable_item = $activity_item->id;
				return $activity_item->id; 
				break;

			case 'like':

				if(isset($activity_item->more->proposal_id)){ // liked a proposal

					$activity_item->likeable_item = $activity_item->more->proposal_id;
					return $activity_item->more->proposal_id; 

				}elseif ($activity_item->more->proposal_type == "casting" || $activity_item->more->proposal_type == "crew"){
					$activity_item->likeable_item = $activity_item->more->talent_id;
					return $activity_item->more->talent_id; 

				}else{
					$activity_item->likeable_item = $activity_item->more->id;
					return $activity_item->more->id; 

				}

				break;
			
			case 'like_profile':
				
				// print " GOT ".$activity_item->activity_type ." ".PHP_EOL;			
				// print_r($activity_item);
				// print PHP_EOL;
				
				if( $activity_item->role_id == 3 ){ // item_type_id = book
					$activity_item->likeable_item = $activity_item->more->title_id;
					return $activity_item->more->title_id; 
				}else{
					$activity_item->likeable_item = $activity_item->talent_id;
					return $activity_item->talent_id; 
					
				}		
				break;
			
			
				// case 'comment':
				// we dont have likes for comments

			default:
				
				// if(DEBUGGING) print "GOT ".$activity_item->activity_type ." ?????".PHP_EOL;
				// if(DEBUGGING) print_r($activity_item);
				// if(DEBUGGING) print PHP_EOL;
				
				return 9999; 
				break;
		}
	}
}
