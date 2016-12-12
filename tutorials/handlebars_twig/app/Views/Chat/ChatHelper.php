<?php


namespace app\Views\Chat;
use app\Views\Shared\HelperBase;
use app\Services\Router;
use app\Services\TemplateManager;

/*
	this class has functions used on the home page for logged in users
*/
class ChatHelper extends HelperBase{


	function json_response($response, $details){
		global $templateMgr, $controller, $response, $details, $parts, $ChatConfig;	

		header("Content-Type: application/json");
		print $response;
		return;
	}

	function ajax_response(){		
		global $templateMgr, $get, $server_vars, $props_arrays, $header_vars_array, $session_vars, $controller, $response, $details, $parts, $ChatConfig;

		// added this in order to share values for the whole chat area
		$response['config'] = $ChatConfig::get_config();		
		if (in_array('debug', $parts)){
			var_dump($response);
			exit;
		} else { 

			if (isset($response['requested_action']) && $response['requested_action'] == "view_chat_list") {
				
				echo  $templateMgr->load('/user/messages/chats_list.html', 
					array(
						'session'=> $session_vars,  
						'list_items'=> $response['chat_list'],
						'set_num'=>$details->args['set_num'],
						'total_count' => $response['total_count'], //testing

						'total_conversation_count' => $response['total_conversation_count'],
						'total_unread_message_count' => $response['total_unread_message_count'],

						'limit' => $controller->set_size(),
						'page_name' => "messages"
					) 
				);				 
			}	
			
			if (isset($response['requested_action']) && $response['requested_action'] == "history") {
				
				echo  $templateMgr->load('/shared/user/chat_messages.html', 
					array(
						'session'=> $session_vars,  
						'list_items'=> $response['history'],
							'set_num'=>$details->args['set_num'],
							'total_count' => $response['total_count'], //testing

							'total_conversation_count' => $response['total_conversation_count'],
							'total_unread_message_count' => $response['total_unread_message_count'],

							'limit' => $controller->set_size(),
							'page_name' => "chat",
					) 
				);
			} 
			else {


				// called for individual chat on desktop, in popup
				if (isset($response['requested_action']) && ($response['requested_action'] == "view" 
							|| $response['requested_action'] == "start")){  

					if($response['requested_action'] == "start"){
						$recip = $response['recipient'];
					}else{
						$recip = $response['result'][0]->other->user;
					}


					$_SESSION['unread_messages_count'] = $response['total_unread_message_count'];
					$session_vars['unread_messages_count'] = $response['total_unread_message_count'];

					$templateMgr->render('/user/messages/chat_base.html', 
						array(
							'get'=>$get,			
							'server' => $server_vars,
							'session'=> $session_vars,  
							'props_arrays'=> $props_arrays,
							'header_vars' => $header_vars_array,
							'device_is_mobile' => Router::device_is_mobile(),
							'config' => $response['config'],
							'new_recipient_id' => $recip->user_id, // used for 'start'
							'new_recipient_name' => $recip->username, // used for 'start'
							'recipient_id' => $recip->user_id,
							'recipient_name' => $recip->username,
							'total_count' => $response['total_count'],
	
							'total_conversation_count' => 0,
							'total_unread_message_count' => 0,
						 
			 				'limit' => $controller->set_size(),
							'page_title' => "Chat",
							'page_name' => "chat",
							'post_script' => '/chat/history/',
							'action' => $response['requested_action']
						));
				
				}

			}
		
		}

		////return;
	}

	/* default response */
	function html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array, $ChatConfig;

		/****** DEBUGGING *****/
		if( ( $response['arguments']['action'] == 'auth') ){
			$this->json_response($response, $details); 
			return;
		}
		
		// added this in order to share values for the whole chat area
		$response['config'] = $ChatConfig::get_config(); 


		// individual chat view 
		if (isset($response['requested_action']) && ($response['requested_action'] == "view" 
			|| $response['requested_action'] == "start")){   

			if($response['requested_action'] == "start"){
				$recip = $response['recipient'];
			}else{
				$recip = $response['result'][0]->other->user;
			}

 				//  	var_dump($response);
				 // die();

			$_SESSION['unread_messages_count'] = $response['total_unread_message_count'];
			$session_vars['unread_messages_count'] = $response['total_unread_message_count'];

			// desktop
			if(!Router::device_is_mobile()){
				
				// show the user home page with the chat called up in pop
				$templateMgr->render('/user/userhome/userhome_base.html', 
				array(
					'get'=>$get,			
					'server' => $server_vars,
					'session'=> $session_vars,  
					'props_arrays'=> $props_arrays,
					'header_vars' => $header_vars_array,
					'device_is_mobile' => Router::device_is_mobile(),
					'list_items'=> $controller->cp->get_chat_list( $response['user']->user_id ),//[],//$response['chat_list'],
					'initial_section_total_count' => $response['total_conversation_count'],//total_count'],

					'total_conversation_count' => $response['total_conversation_count'],
					'total_unread_message_count' => $response['total_unread_message_count'],

					'recip_id' => $recip->user_id,
					'recip_name' => $recip->username,
					'following_count'=> 0, //may not be needed...
					'followers_count'=> 0, //may not be needed...
					'initial_section' => "chats",
					'section_limit' => $controller->set_size(),
					'page_title' => "Messages",
					'page_name' => "chats",
					'post_script' => '/chat/chat_list/',
					'requested_chat_id' => $response['arguments']['chat_id'] 
				));

			}else{ // mobile
				$templateMgr->render('/user/messages/chat_base.html', 
				array(
					'get'=>$get,			
					'server' => $server_vars,
					'session'=> $session_vars,  
					'props_arrays'=> $props_arrays,
					'header_vars' => $header_vars_array,
					'device_is_mobile' => Router::device_is_mobile(),
					'config' => $response['config'],
					'new_recipient_id' => $recip->user_id, // used for 'start'
					'new_recipient_name' => $recip->username, // used for 'start'
					'recipient_id' => $recip->user_id,
					'recipient_name' => $recip->username,
					'total_count' => $response['total_count'], 

						'total_conversation_count' => 0,
						'total_unread_message_count' => 0,

	 				'limit' => $controller->set_size(),
					'page_title' => "Chat",
					'page_name' => "chat",
					'post_script' => '/chat/history/',
					'action' => $response['requested_action']
				));
			}
			
		}
		else {	//chat list
			
			if (Router::device_is_mobile()) { 
				$templateMgr->render('/user/messages/chats_base.html', 
					array(
						'get'=>$get,			
						'server' => $server_vars,
						'session'=> $session_vars,  
						'props_arrays'=> $props_arrays,
						'header_vars' => $header_vars_array,
						'device_is_mobile' => Router::device_is_mobile(),
						'list_items'=>$response['chat_list'],
						'total_count'=> $response['total_count'], 
						'total_unread_messaage_count' => @$response['total_unread_messaage_count'], 
						'limit' => $controller->set_size(),
						'page_title' => "Messages",
						'page_name' => "chats",
						'post_script' => '/chat/chat_list/'
					));
			}
			else { //for desktop, we are showing it on user home page -- should probably find a better solution for this
				 			
				if (!empty($response['target_chat'])) { 
					$recip_id = $response['target_chat'][0]->other->user->user_id; 
					$recip_name = $response['target_chat'][0]->other->user->user_id; 
				} 
				else {
					$recip_id = "";
					$recip_name = "";
				}
			
				$templateMgr->render('/user/userhome/userhome_base.html', 
					array(
						'get'=>$get,			
						'server' => $server_vars,
						'session'=> $session_vars,  
						'props_arrays'=> $props_arrays,
						'header_vars' => $header_vars_array,
						'device_is_mobile' => Router::device_is_mobile(),
						'list_items'=>$response['chat_list'],
						'initial_section_total_count'=>$response['total_count'],

						'total_conversation_count' => $response['total_conversation_count'],
						'total_unread_message_count' => $response['total_unread_message_count'],

						'following_count'=> 0, //temp
						'followers_count'=> 0, //temp
						'recip_id' => $recip_id, //used on desktop when auto-showing a specific chat
						'recip_name' => $recip_name, //used on desktop when auto-showing a specific chat
						'initial_section' => "chats",
						'section_limit' => $controller->set_size(),
						'page_title' => "Messages",
						'page_name' => "chats",
						'post_script' => '/chat/chat_list/'
					));

			}
 		}

	}

	public function set_initial_values() {
		global $response, $details, $session_vars;
		return ;
	}

	 
}

