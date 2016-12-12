<?php

namespace app\Views\User;
use app\Views\Shared\HelperBase;
use app\Services\Router;
use app\Services\TemplateManager;
use shared\Services\CacheClearer;
use app\Services\Auth\Auth;


/*
	this class has functions used on the home page for logged in users
*/
class UserHomeHelper extends HelperBase{

	///private $page;

	function ajax_response(){
		global $templateMgr, $controller, $response, $details, $parts;

		if(in_array('debug', $parts)){
			var_dump($response['user']->news);
		}else{ 

			echo  $templateMgr->load('/shared/user/'.$this->initial_section.'_base.html', 
					array('list_items'=> $this->initial_section_list_items,
							'set_num'=> $details->args['set_num'],
							'total_count' => $this->initial_section_total_count,
							'section_limit' => $controller->set_size(),
							'device_is_mobile' => Router::device_is_mobile(),
							'page_name' => "user_home"
					) 
				);

		}

	}

	/* default response */
	function html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array;

		///$this->page = "user_home";

		$templateMgr->render('/user/userhome/userhome_base.html', 
		array(
			'get'=>$get,			
			'server' => $server_vars,
			'session'=> $session_vars,  
			'props_arrays'=> $props_arrays,
			'header_vars' => $header_vars_array,
			'device_is_mobile' => Router::device_is_mobile(),
			'list_items'=>$this->initial_section_list_items,
			'initial_section_total_count'=>$this->initial_section_total_count,
			'activity_section_total_count'=>$response['news_count'],
			'notifications_section_total_count'=>$response['notification_count'],
			'following_count'=>$response['following_count'],
			'followers_count'=>$response['followers_count'],
			'initial_section' => $this->initial_section,
			'section_limit' => $controller->set_size(),
			'page_title' => "Home",
			'page_name' => "user_home",
			'post_script' => '/'.$response['requested_action'].'/'
		));
 
	}

	 
	function set_initial_values(){
		global $response, $details, $session_vars;

		if(isset($response['requested_action']) && $response['requested_action'] == $details->default_action){
			// this is the default view
			$this->initial_section_list_items = $response['user']->news;
			$this->initial_section_total_count = $response['news_count'];	

			$this->initial_section = "activity";

		} else if(isset($response['requested_action']) && $response['requested_action'] == 'notifications'){
			
			/* 
				added 4/10
			 	clear viewed messages everytime this page loads
            */
			$_SESSION['notifications_count'] = 0;
            $session_vars['notifications_count'] = 0;
            
            // clear the cache too
			@CacheClearer::clear_cache_for_notifications_count($response['user']->user_id);

			// this is the following view
			$this->initial_section_list_items = $response['notification_messages'];
			$this->initial_section_total_count = $response['notification_count'];

			$this->initial_section = "notifications";
		
		} 

		//get followers/ following counts for desktop rail
		if (!Router::device_is_mobile()) {
			$up = new UserProfile(NULL);
			$response['following_count'] = $up->get_user_following_count(Auth::get_logged_in_user_id());
			$response['followers_count'] = $up->get_user_followers_count(Auth::get_logged_in_user_id());
		}
		else {
			$response['following_count'] = NULL;
			$response['followers_count'] = NULL;
		}
		
		

	}

	 
}

