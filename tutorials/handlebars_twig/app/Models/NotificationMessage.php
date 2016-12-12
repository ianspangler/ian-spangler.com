<?php

namespace app\Models;

use StdCLass;
use Exception;
use app\Models\Notifications;
use app\Models\Users;
use app\Models\Titles;
use app\Models\Credits;

class NotificationMessage extends BaseModel{
	
	var $debugging = false;

	public function get_message($item, $function_name){

		/* do for all */
		$item['time_lapsed'] = self::_get_time_elapsed($item['last_updated']);  
		$item['is_multiple'] = ((($item['count']-1)) ? 1 : 0);		
		$item['item_type'] = self::_get_type_for_item($item);

		return self::{$function_name}($item);
	}	

	private static function _get_type_for_item($item){

		switch ($item['action_id']) {
			case 1:

				if( $item['item_type_id'] == 1){// proposal
					return 'comment_added_for_proposal_you_support';				
				
				}else if( $item['item_type_id'] == 2){// credit
					return 'comment_added_for_credit_you_support';
				
				}else if ($item['item_type_id'] == 3){ //  title
					return 'endorsement_for_profile_you_support';				
				}

				break;
			
			case 2:

				if( $item['item_type_id'] == 1){// proposal
					return 'supporters_gained_for_proposal_you_support';				
				
				}else if ($item['item_type_id'] == 3){ //  title
					return 'supporters_gained_for_profile_you_support';				
				
				}else if ($item['item_type_id'] == 5){ //  talent
					return 'supporters_gained_for_profile_you_support';				
				}

				break;
			
			case 3:
 				return 'proposal_created_for_profile_you_support';
				break;
			
			case 4:
				////print __FUNCTION__." ?? ".$item['action_id'].PHP_EOL;
				return null;
 				//$item['item_type'] = 'role_gained_for_proposal_you_support';
				break;
			
			case 5:
				return 'follower';
				break;
			
			case 6:
				if( $item['item_type_id'] == 1){// credit
						return 'comment_added_for_proposal_you_support';					
				}else if( $item['item_type_id'] == 2){// credit
					return 'comment_added_for_credit_you_support';
				}else if (($item['item_type_id'] == 3) || ($item['item_type_id'] == 5)){ //  talent					
					return 'endorsement_for_profile_you_support';
				}else{
					print __FUNCTION__." ??? ".$item['action_id'].PHP_EOL;
				}

				break;
			
			default:
				print __FUNCTION__." ?? ".$item['action_id'].PHP_EOL;
				return null;
				break;
		}
	}

	public function get_message_for_follower($item){

		if($this->debugging) print __FUNCTION__.PHP_EOL;
		$item_data = null;

		$users_model = new Users(array());
		$item_data = $users_model::get_result((string)$item['active_user_id']);

		$item['user_name'] = $item_data->first_name ." ".$item_data->last_name;
		$item['img'] = get_user_profile_image((string)$item['active_user_id'], $item_data->main_pic, $item_data->oauth_uid, array() )  ;
 		$item['url'] = get_url_for_user($item['user_id'])."/followers";
		
 		return $item;
	} 

	public function get_message_for_role($item){

		if($this->debugging) print __FUNCTION__.PHP_EOL;
		/*
			$item_model = null;
			$item_data = null;

			$item['profile_name'] = 'XXROLEXX';
			$item['time_lapsed'] = self::get_time_elapsed($item['last_updated']); 
			$item['is_multiple'] = ((($item['count']-1)) ? 1 : 0);
			//$item['item_type'] = 'role_gained_for_proposal_you_support';
	 		$item['url'] = "XXXX";//get_url_for_proposal($item_data['id']);

			$item['img'] = get_profile_pic_url( array(null, null, $item_data['main_title_pic']), array(null, null, $item_data['title_id'])) ;		
			$item['role_name'] = $item_data['role_name'];
			$item['talent_name'] = $item_data['first_name'] ." ". $item_data['last_name'];
		*/

 		return $item;
	}

	public function get_message_for_proposal($item){

		if($this->debugging) print __FUNCTION__.PHP_EOL;

		// get title data
		$item = self::_title_data($item);

 		return $item;
	}

	public function get_message_for_supporter($item){

		if($this->debugging) print __FUNCTION__.PHP_EOL;
		$item_model = null;
		$item_data = null;
		
		if( $item['item_type_id'] == 1){// credit

			$item = self::_proposal_data($item);
	 
		}else if( $item['item_type_id'] == 3){ // story
			$item = self::_title_data($item);

	
		}else if( $item['item_type_id'] == 5){ // story
			$item = self::_talent_data($item);

		}else{
			print "Else!! ".$item['item_type_id'].PHP_EOL;
			
		} 

 		return $item;

	}

	public function get_message_for_comment($item){
		if($this->debugging) print __FUNCTION__."(type: ".$item['item_type_id']." id: ".$item['item_id'].")".PHP_EOL;
		$item_model = null;
		$item_data = null;
		
		if( $item['item_type_id'] == 1){// credit

			$item = self::_proposal_data($item);	

		}else if( $item['item_type_id'] == 2){// credit

			$item = self::_credit_data($item);

		}else if ($item['item_type_id'] == 3){ //  title

			$item = self::_title_data($item);

			
		
		}else{
			print "Else!! ".$item['item_type_id'].PHP_EOL;
			
		}
 
		return $item;
	}

	public function get_message_for_endorsement($item){

		if($this->debugging) print __FUNCTION__."(type: ".$item['item_type_id']." id: ".$item['item_id'].")".PHP_EOL;
		$item_model = null;
		$item_data = null;
		
		if( $item['item_type_id'] == 1){// credit

			$item = self::_proposal_data($item);
		 
		}else if( $item['item_type_id'] == 2){// credit

			$item = self::_credit_data($item);

		}else if ($item['item_type_id'] == 3){ //  title

			$item = self::_title_data($item);		 

		}else if ($item['item_type_id'] == 5){ //  talent

			$item = self::_talent_data($item);		 
			#print_r($item);

		}else{

			print "Else!! ".$item['item_type_id'].PHP_EOL;
			
		}
	 	
 		return $item;
	}
	
	private static function _credit_data($item){

		$item_model = new Credits();
		$result = $item_model->get_result((int)$item['item_id']);
		$items_arr = array();
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($items_arr, $row);
			}
			$item_data = $items_arr[0]; 
			#print_r($item_data);
			$item['img'] = get_profile_pic_url( 
				array(null, $item_data['main_talent_pic'], null, null, $item_data['movie_title_main_pic']), 
				array(null, $item_data['talent_id'], null, null, $item_data['title_id'])
			) ;
			$item['talent_name'] = $item_data['talent_first_name'] ." ". $item_data['talent_last_name'];
			$item['role_name'] = $item_data['role_name'];
			$item['identity_name'] = $item_data['identity_name']; //for filmmaker proposals
			$item['profile_name'] = $item_data['movie_title_name'];
			$item['url'] = get_url_for_credit( $item_data['credit_id']);

		}	
			#print_r($item);

		return $item;
	}

	private static function _talent_data($item){

		$item_model = new Talent();
		$item_data = $item_model::get((int)$item['item_id'])[0]; 

		$item['img'] = get_profile_pic_url( 
			array(null, $item_data['main_pic'],null), 
			array(null, $item_data['talent_id'], null)
			) ;
		$item['talent_name'] = $item_data['first_name'] ." ". $item_data['last_name'];
		$item['role_name'] = "ROLE"; 
		$item['profile_name'] = $item_data['first_name'] ." ". $item_data['last_name'];
		$item['url'] = get_url_for_profile(array(null, $item_data['url_handle']));
 
		return $item;
	}
	private static function _title_data($item){

		$item_model = new Titles();
		$item_data = $item_model::get((int)$item['item_id'])[0];

		$item['img'] = get_profile_pic_url( array(null, null, $item_data['main_pic']), array(null, null, $item_data['title_id'])) ;
		$item['profile_name'] = @$item_data['name'];
		$item['url'] = get_url_for_profile(array($item_data['url_handle']));

		return $item;
	}
		
	private static function _proposal_data($item){

		$items_arr = array();
		$item_model = new Proposals();
		$result = $item_model->get_result((int)$item['item_id']);  
		
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				array_push($items_arr, $row);
			}
			$item_data = $items_arr[0];  

			$item['img'] = get_profile_pic_url( 
				array(null, $item_data['main_talent_pic'], null, null, null), 
				array(null, $item_data['talent_id'], null, null, null)
   			);

			$item['profile_name'] = $item_data['title_name'];
			$item['url'] = get_url_for_proposal($item_data['id']);

			$item['role_name'] = $item_data['role_name'];
			$item['identity_name'] = $item_data['identity_name']; //for filmmaker proposals

			$item['talent_name'] = $item_data['first_name'] ." ". $item_data['last_name'];

		}

		return $item;
	}

	protected function _get_time_elapsed($timestamp){

		$date   = date('Ymd', strtotime($timestamp)); 
		$datediff = date('Ymd') - $date;

		if($datediff == 0) {
		    return getTimeElapsed( strtotime($timestamp) ) . " ago";
		} else if($datediff < 0) {
		    return  'future';
		} else {
			return date('l',  strtotime($timestamp) ) ;
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
		$arr = filter_var_array($pairs);//, $filter);
		
		/*
			filter out blank values
		*/
		$arr = array_filter($arr, function ($item) use (&$arr) {
		    if($arr[key($arr)] == ""){ next($arr); return false; }
		    next($arr);
		    return true;
		});

    	return $arr;

	}

}
