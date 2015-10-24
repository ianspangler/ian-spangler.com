<?php

namespace app\Views\User;
use app\Views\Shared\HelperBase;
use app\Services\Auth\Auth;
use app\Services\Router;
use app\Services\UserOnline;
use app\Services\TemplateManager;
use app\Models\User_Blocks;
 
/*
	this class has functions used on the home page for logged in users
*/
class UserProfileHelper extends HelperBase{

	//private $page;

	function ajax_response(){
		
		global $templateMgr, $controller, $response, $details, $initial_section_list_items, $initial_section_total_count, $parts ;
		
		if(in_array('debug', $parts)){
		
			var_dump($response['activity']);
		
		}else{ 

			
			echo  $templateMgr->load('/shared/user/'.$response['requested_action'].'_base.html', 
					array('list_items'=> $initial_section_list_items,
							'set_num'=>$details->args['set_num'],
							'total_count' => $initial_section_total_count,
							'section_limit' => $controller->set_size(),
							'device_is_mobile' => Router::device_is_mobile(),
							'page_name' => "user_profile",
					) 
				);
		}

	}

	/* default response */
	function html_response($response, $details){

		///if (Auth::user_is_logged_in()) { print("HTML RESPONSE -- USER PROFILE"); exit; }

		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array, $initial_section_list_items, $initial_section_total_count;


		$loggedin_user_id = Auth::get_logged_in_user_id();

		$user_isblocked = User_Blocks::is_blocked((int)$loggedin_user_id, (int)$response['user']->user_id, array('alt_db'=>true));  
		

		if ($header_vars_array['messaged'] == 'Y' && Router::device_is_mobile()) { //checking if logging in to message
			
			if ($user_isblocked == 1 || $header_vars_array['messaged_user_id'] == $loggedin_user_id){ // $_SESSION['id']) {
				//user is attempting to message a blocked user or trying to message themselves
				//we keep them directed to the user profile
			}
			else {
				header('Location: /chat/start/'.$header_vars_array['messaged_user_id']);
			}
		} 


		$templateMgr->render('/user/userprofile/userprofile_base.html', 
			array(
				'get'=>$get,			
				'server' => $server_vars,
				'session'=> $session_vars,  
				'props_arrays'=> $props_arrays,
				'header_vars' => $header_vars_array,
				'device_is_mobile' => Router::device_is_mobile(),
				'profile_id' => $response['user']->user_id,
				'page_title'=> $response['user']->username,
				'meta_keywords'=> 'story, movie, film, TV, cast, dream cast, author',
				'meta_description'=> 'Vote for '. @$profile_info['name'].' to be made into a movie, and propose your dream cast',
				'user_name'=> $response['user']->username,
				'user_firstname'=> $response['user']->first_name,	
				'user_uploadedpic'=>$response['user']->main_pic,
				'user_pic' => get_user_profile_image( $response['user']->user_id, $response['user']->main_pic, $response['user']->oauth_uid ),
				'follow_state' => get_user_follow_state( $loggedin_user_id, $response['user']->user_id ),
				'initial_section' => $response['requested_action'],

				'initial_section_list_items' => $initial_section_list_items,
				'initial_section_total_count' => $initial_section_total_count,

				'activity_section_total_count' => $response['activity_count'],//num_activity,
				'followers_section_total_count' => $response['followers_count'],//num_followers,
				'following_section_total_count' => $response['following_count'],//num_following,
				'section_limit' => $controller->set_size(),
				'page_name' => "user_profile",
				'post_script' => '/users/',
				'user_is_blocked'=>$user_isblocked,
				'online' => UserOnline::is_online($response['user']->user_id)
			));
	}

	 
	function set_initial_values(){
		
		global $response, $details, $initial_section_list_items, $initial_section_total_count;

		$loggedin_user_id = Auth::get_logged_in_user_id();

		
		if(isset($response['requested_action']) && $response['requested_action'] == $details->default_action){
			// this is the default view
			$initial_section_list_items = $response['activity'];
			$initial_section_total_count = $response['activity_count'];		

		} else if(isset($response['requested_action']) && ($response['requested_action'] == 'followers' || $response['requested_action'] == 'rr_followers')) {
			// this is the followers view
			$initial_section_list_items = register_follow_states($response['user']->followers, $loggedin_user_id);
			$initial_section_total_count = $response['followers_count'];//num_followers;

		}else if(isset($response['requested_action']) && ($response['requested_action'] == 'following' || $response['requested_action'] == 'rr_following')){
			// this is the following view
			$initial_section_list_items = register_follow_states($response['following'], $loggedin_user_id);
			$initial_section_total_count = $response['following_count'];
		} 
	} 
	 
}

