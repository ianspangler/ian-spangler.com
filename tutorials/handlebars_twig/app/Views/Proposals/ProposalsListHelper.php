<?php

namespace app\Views\Proposals;
use app\Views\Shared\HelperBase;
use app\Services\Router;
use app\Services\TemplateManager;

/*
	this class has functions used on the home page for logged in users
*/
class ProposalsListHelper extends HelperBase{

	

	function set_initial_values(){
		global $response, $details;

		$this->section = $response['requested_action'];
		$this->list_items = $response['proposals_list'];
		$this->total_count = $response['proposals_list_count'];
		$this->page_title = $response['page_title'];

	}

	function ajax_response(){
		global $templateMgr, $controller, $response, $details, $parts;
		
		if(in_array('debug', $parts)){
			////var_dump($response['user']->news);
		}else{ 
			
			echo  $templateMgr->load('/proposals/'.$this->section.'_list.html', 
					array('list_items'=> $this->list_items,
							'set_num'=>$details->args['set_num'],
							'total_count' => $this->total_count,
							'limit' => $controller->set_size(),
							'page_name' => $this->section,
					) 
				);

		}

	}

	/* default response */
	function html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array;

		$templateMgr->render('/proposals/'.$this->section.'_base.html', 
		array(
			'get'=>$get,			
			'server' => $server_vars,
			'session'=> $session_vars,  
			'props_arrays'=> $props_arrays,
			'header_vars' => $header_vars_array,
			'device_is_mobile' => Router::device_is_mobile(),
			'list_items'=>$this->list_items,
			'total_count'=>$this->total_count,
			'limit' => $controller->set_size(),
			'page_title' => $this->page_title,
			'page_name' => $this->section,
			'post_script' => '/'.$this->section.'/'
		));

 
	}
 
}

