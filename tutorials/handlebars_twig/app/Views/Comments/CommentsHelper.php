<?php

namespace app\Views\Comments;
use app\Views\Shared\HelperBase;
use app\Services\Router;
use app\Services\TemplateManager;

use shared\Services\CacheClearer;

/*
	this class has functions used on the home page for logged in users
*/
class CommentsHelper  extends HelperBase{


	function ajax_response(){
		global $templateMgr, $controller, $response, $details, $parts;
		
		if(in_array('debug', $parts)){
			var_dump($response);
		}else{ 

		// print "All comments for ";
		// print $response['arguments']['profile_id'] ."<br>".$this->initial_section."<br>".$this->initial_section_total_count."<br>";
		// print_r($response['list_items']);
		// print_r($this->initial_section_list_items);
		// 	

		//{% include '/shared/comments.html' with {"comments_items":overview.comments_items} %}

			echo  $templateMgr->load('/shared/'.$this->initial_section.'.html', 
					array('comments_items'=> $this->initial_section_list_items,
							'set_num'=>$details->args['set_num'],
							'total_count' => $this->initial_section_total_count,
							'limit' => $controller->set_size(),
							'page_name' => "XXX"
					) 
				);

		}

	}

	/* default response */
	function html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array;
			print_r($response['list_items']);
 
 
	}

	 
	function set_initial_values(){
		global $response, $details, $session_vars;
 
		$this->initial_section = "comments";

		if (isset($response['list_items'])) {
			$this->initial_section_total_count = $response['total_count'];

			$this->initial_section_list_items = $response['list_items'];		
 		}

	}

	 
}

