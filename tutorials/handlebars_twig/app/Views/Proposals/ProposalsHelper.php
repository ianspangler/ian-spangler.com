<?php

namespace app\Views\Proposals;
use app\Views\Shared\HelperBase;
use app\Views\Shared\PostPageNavigator;
use app\Models\Comments;
use app\Services\Router;
use app\Services\Auth\Auth;
use app\Services\TemplateManager;

/*
	this class has functions used on the home page for logged in users
*/
class ProposalsHelper extends HelperBase{

	function set_initial_values(){
		global $response, $details;

		if(isset($response['arguments']['proposal_id'])){

			$this->section = $response['requested_action'];
			$this->item = $response['proposal_item'];
			$this->page_title = $response['page_title'];

		}else{

			$this->section = $response['requested_action'];
			$this->list_items = $response['proposals_list'];
			$this->total_count = $response['list_count'];
			$this->page_title = $response['page_title'];

		}

	}

	function ajax_response(){
		global $templateMgr, $controller, $response, $details, $parts;
				
		if(in_array('debug', $parts)){
			var_dump($response['user']->news);
		}else{  

			$template = '/shared/'.$this->section.'_list_items.html';
			
			// only in mobile
			if($response['arguments']['base'] == '/featured-proposals'){
				$this->section = 'featured-proposals';
				$template = '/proposals/'.$this->section.'_list.html';
			}


			// what to return
			$data = array(
				"count" => $this->total_count,
				"status" => "",
				"htmldata"=>  $templateMgr->load($template, 
					array('list_items'=> $this->list_items,
							'set_num'=>$details->args['set_num'],
							'total_count' => $this->total_count,
							'limit' => $controller->set_size(),
							'page_name' => "XXX",
							'total_count'=>$this->total_count
					) 
				)
			);

			// desktop proposals wants a json wrapper on the html
			if( !Router::device_is_mobile() ){ 	
				
				if(isset($response['qs_vars']->json)){
					header("Content-Type: application/json");
				}
				echo json_encode( $data ); 
			
			}else{

				echo $data['htmldata'] ; 
			}


		}

	}

	/* default response */
	function html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array, $item_type_ids;
		

 		/**
 			single item
 		*/	
 		if(isset($response['arguments']['proposal_id'])){
 			
 			$this->single_html_response($response, $details);

		}else{ 
			/** 
			list page 
			*/
			if($response['arguments']['base'] == '/featured-proposals'){
				$this->section = 'featured-proposals';
				$this->featured_html_response($response, $details);
				exit; 
			}


			/**
				mobile devices shouldnt be accessing this page 
			*/
			if(Router::device_is_mobile()){
				header("Location: /?proposals_redirect");
			}

	 		if (isset($response['extras']->category) || isset($response['extras']->format) || isset($response['extras']->proposaltype)) {
	            $category_fullname = '<em>'.number_format($this->total_count, 0).'</em>&nbsp; Proposals shown';
	        } else {    
	            $category_fullname = "Showing all casting & filmmaker proposals";
	        } 

	        ///print_r($response['extras']->category_datanames);
	       /// exit;

			$templateMgr->render('/proposals/proposals_base.html', 
			array(
				'get'=>$get,			
				'server' => $server_vars,
				'session'=> $session_vars,  
				'props_arrays'=> $props_arrays,
				'header_vars' => $header_vars_array,
				'device_is_mobile' => Router::device_is_mobile(),
				'page_title'=> $response['extras']->page_title,
				'title_str'=> $response['extras']->title_str,
				'loggedin_user_id' => Auth::get_logged_in_user_id(),

				'list_items'=>$this->list_items,
				'list_count_display'=>$this->total_count,
				'limit' => $controller->set_size(),
				'page_name' => $this->section,			
				'page' => $response['extras']->page,

				'proposaltype_id' => $response['extras']->filter_proposaltype_id,
				'proposaltype_names' => $response['extras']->proposaltype_names,
				'proposaltypes'=> $response['extras']->proposaltypes,
				'proposaltype_url_val' => $response['extras']->proposaltype_datanames,

				'category_fullname'=>$category_fullname,
				'category_id' => $response['extras']->filter_category_id,
				'category_names' => $response['extras']->category_names,
				'category_url_val' => $response['extras']->category_datanames,
				'categories' => $response['extras']->categories,

				'format_id' => $response['extras']->filter_format_id,
				'keyword' => $response['extras']->filter_keyword, 
				'list_count' => $response['list_count'],
				'current_filter' => $response['extras']->current_filter, 
				'scrolled_page' => $response['extras']->scrolled_page,
		
				'sort_options'=> $response['extras']->sort_options,
				'sort_direction' => $response['extras']->sort_direction, 
				'sortby' => $response['extras']->sortby,
				'sortby_name' => $response['extras']->sortby_name,
				'sortindex' => $response['extras']->sort_index
			));
		}

 	}

	/* response */
	function single_html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array, $item_type_ids;


		/*
			this is the info object returned from profile
		*/
		$proposal_item = $response['proposal_item'];

		if (empty($proposal_item)) {
			showError(2, "404: Page Not Found"); exit();
		}

		/** from posts/proposal.php */
		$page = $type = "proposal";

		$og_description_message = "Support this proposal or make your own on The IF List.";
 		

		$post_type_id = $item_type_ids[$page];

		$header_str = "Filmmaking";
		if ($proposal_item->info['post_type'] == "casting") { $header_str = "Casting"; } 
		
		$keywords = "Officially Signed On";
		if ($proposal_item->info['post_type'] == "casting") { $keywords = "Officially Cast"; } 

		$comments_status = "open";
		if(Comments::Instance()->thread_is_deactivated($proposal_item->id, $item_type_ids['proposal']) ){
			$comments_status = "closed";
		}
		
		$comment_count = Comments::Instance()->get_count($proposal_item->id, 'proposal'); //global

	 	$referer_path = "";
		if(isset($_SERVER['HTTP_REFERER'])){ 
			$request = parse_url($_SERVER['HTTP_REFERER']);
			$referer_path = $request['path'];
		}

		//get total proposals for logged-in user
		$user_num_proposals = "";
		if (Auth::user_is_logged_in()) {
			$user_num_proposals = get_user_num_proposals();
		}
		
		/* 
			TODO: refactor to put template values in options_array 
		*/
		$track_str = "";
		$next_id = null;
		$prev_id = null;

		$related_items = "";
		if (Router::device_is_mobile() == true) {

			// mobile only: get next and previous proposal to navigate to
			// track can be either 'talent' or 'story' for instance
			// make 'story' the default track to follow
			
			$track = PostPageNavigator::get_track($proposal_item->info['title_id'], "story");

			if ($track !== false) {
				$track_str = (isset($track) ? '?track='.$track : "");

				// array of 2 IDs (next and previous)
				$rel_ids_arr = PostPageNavigator::getNextAndPrevPostId($proposal_item->info, $track, $page);
				$next_id = $rel_ids_arr['next'];
				$prev_id = $rel_ids_arr['prev'];
			}
			
		}
		else {
			//desktop only: get related actor/ filmmaker proposals
			require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/get_proposal_detail.php';
			$related_items = getRelatedItems(
				$proposal_item->id,
				$proposal_item->info['post_type'], 
				$proposal_item->info['role_id'], 
				$proposal_item->info['role_name'], 
				$proposal_item->info['title_id'], 
				$proposal_item->info['source_name'], 
				$proposal_item->info['title_genre'], 
				$proposal_item->info['title_format'], 
				$proposal_item->info['talent_id'], 
				$proposal_item->info['position_name']
			); 
			
		}

			/** /end from posts/proposal.php */
			$templateMgr->render('/posts/postpage_base.html', 
			array(
				'get'=>$get,
				'server' => $server_vars,
				'session'=> $session_vars,
				'header_vars' => $header_vars_array,
				'referer' => $referer_path,
				'device_is_mobile' => Router::device_is_mobile(),

				'page' => $page, 
				'page_title' => $proposal_item->title, 
				'talent_url' => '/talent/' . $proposal_item->talent_url_handle, 
				'role_url' => '/roles/' . $proposal_item->role_url_handle,
				'title' => $proposal_item->title,
				'postpage_info' => $proposal_item->info,  
				'postpage_id' => $proposal_item->id,
				'post_type_id' => $post_type_id,
				'rev' =>  date('Ymdh'), 
				'og_description_message' => $og_description_message,  
				'keywords' => $keywords,  
				'officially_cast' => $proposal_item->info['officially_cast'],
				'header_str' => $header_str,
				'comments_status' => $comments_status, 
	  			'related_items' => $related_items,
	  			'comment_count' => $comment_count,
				'comments_items' => $proposal_item->comments_items,
				'next_id' => $next_id,
				'prev_id' => $prev_id,
				'track_str' => $track_str,
				'header_vars' => $header_vars_array,
				'supporters_fan_results' => $proposal_item->supporters_fan_results,
				'supporters_fans' => $proposal_item->supporters_fans, //50 fans
				'supporters_row_fans' => $proposal_item->supporters_row_fans, //10 fans
				'featured' => $proposal_item->info['featured'],
				'user_num_proposals' => $user_num_proposals,

				# used in header
				'props_arrays'=> $props_arrays, 	 
			));
	}

	/*  response */
	function featured_html_response($response, $details){
		global $templateMgr, $controller, $get, $server_vars, $session_vars, $props_arrays, $header_vars_array;

		$templateMgr->render('/proposals/'.$this->section.'_base.html', 
		array(
			'get'=>$get,			
			'server' => $server_vars,
			'session'=> $session_vars,  
			'props_arrays'=> $props_arrays,
			'header_vars' => $header_vars_array,
			'device_is_mobile' => Router::device_is_mobile(),
			'list_items'=> $this->list_items,
			'total_count'=>$this->total_count,
			'limit' => $controller->set_size(),
			'page_title' => $this->page_title,
			'page_name' => $this->section,
			'post_script' => '/'.$this->section.'/listing/'
		));

	}



	
 
}

