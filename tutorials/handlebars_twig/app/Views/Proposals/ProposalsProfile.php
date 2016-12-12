<?php

namespace app\Views\Proposals;
use app\Views\Shared\AbstractProfile;
use app\Services\Auth\Auth;
use app\Services\Router;

use app\Models\Proposals;
use app\Models\Activity;
use app\Models\Categories;
use app\Models\Comments;
use app\Models\Sort_Options;

use StdClass;
use Exception;

class ProposalsProfile extends AbstractProfile {

	private static $proposals_model = null;

	protected static $cache_key_prefix = 'pl_';

	protected static $user_liked_items = null;
 	protected static $user_disliked_items = null;

	public function __construct($set_size = 30, $set_num = 1){

		self::set_size($set_size);
		self::set_num($set_num);

		/*
			create the models that will be needed for this profile
		*/
		$config = array(
			'set_size'=>self::set_size()
		);

		//create instance of proposals model
		self::$proposals_model = new Proposals();
		
		parent::__construct();
			
	}

	/** 
		For single proposal page
	*/
	public function get_one($_id) {
		//print __FUNCTION__;
		$result = self::$proposals_model->get($_id);
		
		if(!isset($result)){ return null; }

		$item = self::array_to_object( $result ); 
		
		if (true == Router::device_is_mobile()) { //mobile
		 	$item->comments_items = null;
		}else {
			$item = self::_get_comments_for_proposal($item);
		}

		// print "ITEM ".PHP_EOL;
		// print_r($item);
		
		$item->supporters_row_fan_results = array();
		$item->supporters_row_fans =  array();
		
		//if(true == Router::device_is_mobile()){ //mobile supporters 
			$item = self::_get_fans_for_proposal($item);
		//}

		// print_r($item);
		// exit;

		$item = self::_get_info_for_proposal($item);

		$item = self::_get_page_title($item);
		return $item;

	}	

	public function get_list_count() {
		return self::$proposals_model->get_proposals_count();
	}

	public function get_all_proposals($set_num = 1, $filters = null) {
 

		$ids_list = array();		
		$f_items = self::$proposals_model->get_activity_items($filters);

		$offset = 0;
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		} 
		
		// get just the 1st set
		$sliced_items = array_slice($f_items, $offset, self::set_size());

		// get their ids
		foreach($sliced_items as $item){
			array_push($ids_list, $item['proposal_id'] );
		}
		

		// get the details of the 30
		$type = (isset($filters->filter_proposaltype_id) ? $filters->filter_proposaltype_id : "casting"); //'casting';
		$f_items = self::$proposals_model->get_result(implode(",",$ids_list), $filters, $offset);

		
		$ld_items = self::$proposals_model->get_numlikesdislikes_counts($ids_list, $filters, $offset);
		

		$items = array();
		foreach($f_items as $item){
			array_push($items, self::array_to_object( $item ));
		}	


		$count = 0;
		foreach($ld_items as $item){
			
			$items[$count]->num_likes = $item['num_likes'];
			$items[$count]->num_dislikes = $item['num_dislikes'];
			$count++;
		}	

		//exit;
		//print_r($items);
		//exit;	

		if(Auth::user_is_logged_in()){
			self::_get_likesdislikes_for_proposals(Auth::get_logged_in_user_id(), $ids_list);
		}
		/* 
			add numbering, 
			type, 
			time format, 
			source format, 
			comment count, 
			likes 
		*/
		$counter = $offset+1;
		foreach($items as $item){
			// add type 
			$item->list_type = 'proposals';

			$item->timestamp = getTimeElapsed( strtotime($item->timestamp) ) .' ago';

			$item->source_format = strtolower( $item->source_format );

			if(Auth::user_is_logged_in()){
				$item = self::_set_likeddisliked_for_proposal( $item );
			}

			// for use on list pages
			$item->comment_count = self::$proposals_model->get_comment_count($item->id);
			if($counter < 100){
				$item->item_number = $counter;
				$counter++;
			}else{
				$item->item_number = "";
			}	
		}
		return $items;
		 
	}
 	
 	private static function _get_likesdislikes_for_proposals($_user_id, $item_ids){

 		self::$user_liked_items = array();
		self::$user_disliked_items = array();

		$liked = get_user_likes($type_id = 1, (int)$_user_id, $item_ids);
 		$disliked = get_user_dislikes($type_id = 1, (int)$_user_id, $item_ids);

 		if(is_array($liked)){ self::$user_liked_items = $liked; }
 		if(is_array($disliked)){ self::$user_disliked_items = $disliked; }
 	}

 	private static function _set_likeddisliked_for_proposal($item){

 		$item->liked = 'N';
		$item->disliked = 'N';
	 		
		// check the user likes array that was populated above
		if (in_array($item->id, array_values(self::$user_liked_items))) {
			//user has liked this proposal
			$item->liked = 'Y';			
		} 		

		// check the user likes array that was populated above
		if(in_array($item->id, array_values(self::$user_disliked_items))){
			#print "user likes id ".$id."\n<br>";
			//user has liked this proposal
			$item->disliked = 'Y';
		}

		
		return $item;

 	}

	/** 
		For Featured Proposals page: this method calls Activity model to 
		instantiate activity-type items
	*/
	public function get_featured($set_num = 1) {


		$offset = 0;
		$prop_items = self::$proposals_model->get_featured_activity_items();
		
		$proposals_items = array();
		foreach($prop_items as $item){
			array_push($proposals_items, self::array_to_object( $item ));
		}
				
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}

		$activity_model = new Activity();

		$sliced_proposals = array_slice($proposals_items, $offset, self::set_size());
		self::set_num($set_num);
		$activity_model->set_num($set_num);

		$proposal_activity_items = $activity_model->get_activity_sub_type(
			$activity_model->get_activity_details(
				$sliced_proposals,
				0,
				array('key_name'=>'feat_prop')
			)
		);

		return $proposal_activity_items;
	
	}

	public function get_extras($get){
		global $dbHandler;

        $extras = new StdClass();
      
        //SET PAGE TYPE
        $extras->page = ((isset($get['type'])) ? $dbHandler->real_escape_string($get['type']) : "casting" );
        //GET KEYWORD FROM URL
        $extras->filter_keyword = ( (isset($get['keyword'])) ? $dbHandler->real_escape_string($get['keyword']) : "" );
        //check for current category from URL
        $extras->format = ((isset($get['format'])) ? $dbHandler->real_escape_string($get['format']) : null);
        //GET CURRENT FILTER
        $extras->current_filter = ( (isset($get['current_filter'])) ? $get['current_filter'] : "" );         
        //GET CURRENT SCROLLED PAGE
        $extras->scrolled_page = ( (isset($get['page'])) ? $get['page'] : "" );
        $extras->filter_format_id = "";
        
        //SET PAGE TITLE
        list($extras->page_title, $extras->title_str) = self::_get_list_page_title($extras->page);

        // GET CATEGORIES
        list($extras->filter_category_id, $extras->category_names, $extras->category_datanames, $extras->categories, $extras->category) = self::_build_categories( Categories::get_categories( 'proposals'), $get ); 
        
        // GET PROPOSAL TYPES
        list($extras->filter_proposaltype_id, $extras->proposaltype_names, $extras->proposaltype_datanames, $extras->proposaltypes, $extras->proposaltype ) = self::_get_proposal_types($extras->page, $get);
        
        // GET SORT OPTIONS
        list($extras->sort_options, $extras->sort_index, $extras->sortby_name, $extras->sort_direction, $extras->sortby ) = self::_get_sort_details( Sort_Options::get_sort_options("proposals"), $get );        
                
        

        return $extras;
	}


	////////////////////////////
	private static function _get_fans_for_proposal($_item){
		include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/get_fans.php';

		$_item->supporters_fan_results = get_fans("proposal", $_item->id, @$_SESSION['friends_ids'], 50, 0);
		$_item->supporters_fans = $_item->supporters_fan_results['fans_array'];
		
		$_item->supporters_row_fans = array_slice($_item->supporters_fans, 0, 10); 
		
		return $_item;
	}

	private static function _get_comments_for_proposal($_item){


		global $item_type_ids;
		$_item->comments_items = Comments::Instance()->get_result($_item->id, "", array_search('1', $item_type_ids) );
		return $_item;
	}

	private static function _get_info_for_proposal($_item){

		$_item->info = array();
		
		//$_item->info['posted_by_str'] = $posted_by_str;
		//$_item->info['oauthid'] = $row['oauth_uid']; 
		$_item->info['post_id'] = $_item->id;
		$_item->info['post_type'] = $_item->proposal_type;// $row['proposal_type'];
	 
		$_item->info['title_format'] = $_item->source_format_id;//$row['source_format_id'];
		$_item->info['format_name'] = $_item->source_format;
		$_item->info['title_genre'] = $_item->genre_id;//$row['genre_id'];
	
		$_item->info['talent_id'] = $_item->talent_id;//$row['talent_id'];
		$_item->info['role_id'] = (($_item->proposal_type == "casting") ? $_item->role_id : "" );
		
		$_item->info['status_id'] = $_item->status_id;
		$_item->info['title_id'] = $_item->title_id;//['source_title_id'];
		$_item->info['role_name'] = $_item->role_name;
	
		$_item->info['position_name'] = ucfirst($_item->identity_name);
		$_item->info['talent_name'] = $_item->first_name ." ". $_item->last_name;
		$_item->info['talent_url_handle'] = $_item->talent_url_handle;
		//$_item->info['talent_pic'] = $_item->talent_pic;

		$_item->info['media_talent_pic'] = $_item->media_talent_pic;
		$_item->info['main_talent_pic'] = $_item->main_talent_pic;

		// $_item->info['talent_pic_sm'] = $talent_pic_url_sm;
		//$_item->info['role_pic'] = $_item->role_pic;//$role_pic_url;
		
		$_item->info['media_role_pic'] = $_item->media_role_pic;
		$_item->info['main_role_pic'] = $_item->main_role_pic;

		$_item->info['main_title_pic'] = $_item->main_title_pic;

		// $_item->info['role_pic_sm'] = $role_pic_url_sm;
		$_item->info['role_url'] = $_item->role_url_handle; //'/roles/'.$role_url;
		$_item->info['source_name'] = str_replace('"', '&quot;', $_item->title_name);
		$_item->info['source_link'] = $_item->title_url_handle;//$source_link;
		$_item->info['poster_id'] = $_item->user_id;// @$poster_id;
		$_item->info['poster_name'] = $_item->user_first_name ." ".$_item->user_last_name ;//$poster_name;
	
		$_item->info['date_time'] = getTimeElapsed( strtotime($_item->timestamp) ).' ago';;//$date_time;
	
		$_item->info['liked'] = "";
		$_item->info['disliked'] = "";
		
		if(Auth::user_is_logged_in()){

			self::_get_likesdislikes_for_proposals(Auth::get_logged_in_user_id(), array($_item->id));
			$_item = self::_set_likeddisliked_for_proposal( $_item );		
			$_item->info['liked'] = $_item->liked;
			$_item->info['disliked'] = $_item->disliked;

		}
		$result = self::$proposals_model->get_numlikesdislikes_count($_item->id);

		$_item->info['num_likes'] = $result['num_likes'];//self::$proposals_model->get_numlikes_count($_item->id);
		$_item->info['num_dislikes'] = $result['num_dislikes'];//self::$proposals_model->get_numdislikes_count($_item->id);

		$_item->info['featured'] = $_item->featured;
	
		$_item->info['officially_cast'] = $_item->officially_cast;
		$_item->info['post_image'] = $_item->proposal_image;
		$_item->info['anonymous'] = $_item->anonymous;
		 
		return $_item;
	}

	private function _get_sort_details($sort_options, $get){
             
            if (isset($get['sortby'])) {
                if (in_array($get['sortby'], $sort_options['data_names'])) {
                    $sortby = $get['sortby'];
                }
                else {
                    $sortby = $sort_options['data_names'][0];
                }
            }
            else {
                $sortby = $sort_options['data_names'][0];
            }
            
            $sort_index = array_search($sortby, $sort_options['data_names']);
            $sortby_name = $sort_options['names'][$sort_index];
            $sort_direction = $sort_options['directions'][$sort_index];
            
            return array($sort_options, $sort_index, $sortby_name, $sort_direction, $sortby );

    }
    
    private function _get_proposal_types($page, $get){ 

        $proposaltypes = array();
        //if ($page == "filmmaking") {
        $proposaltypes["filmmaking"] = array(
        	'names' => array('All Types', 'Directing', 'Writing', 'Composing'),
            'data_names' => array('crew', 'directing', 'writing', 'composing'),
            'ids' => array('crew', '3', '5', '6')
        );
        //} else {
        $proposaltypes["casting"] = array(
            'names' => array('Casting'),
            'data_names' => array('casting'),
            'ids' => array('casting')
        );    
        //}

  		#print "page = $page ".PHP_EOL;
  		$current_type = $proposaltypes[$page];

        //check for current proposal type from URL
        if (isset($get['proposaltype_id'])) { 
           $proposal_type = $get['proposaltype_id'];
        }
        else { 
            $proposal_type = $current_type['data_names'][0];
        }

       
        $sel_types_arr = explode(" ", $proposal_type);

        //print_r( $sel_types_arr) ;
       // exit;
        
		#print "proposal_type = $proposal_type ".PHP_EOL;

        $proposaltype_id = "";
        $proposaltype_names = "";
        $proposaltype_datanames = "";
        
        for ($n = 0; $n < count($sel_types_arr); $n++) {
        	if( (int)($proposal_type) > 1 ){
        		
        		$proposaltype_index = array_search($sel_types_arr[$n], $current_type['ids']);
            	#print "A searching for ".$sel_types_arr[$n] ." in ".print_r($current_type['ids'],true).PHP_EOL;

        	}else{

            	$proposaltype_index = array_search($sel_types_arr[$n], $current_type['data_names']);
            	#print "B searching for ".$sel_types_arr[$n] ." in ".print_r($current_type['data_names'],true).PHP_EOL;

        	}
            #print "index = $proposaltype_index ".PHP_EOL;

            $proposaltype_id .= $current_type['ids'][$proposaltype_index]."|";
            $proposaltype_names .= $current_type['names'][$proposaltype_index].", ";
            $proposaltype_datanames .= $current_type['data_names'][$proposaltype_index]."+";

        }
        
        $proposaltype_id = rtrim($proposaltype_id, "|");
        $proposaltype_names = rtrim($proposaltype_names, ", ");
        $proposaltype_datanames = rtrim($proposaltype_datanames, "+");

		#print "GOT $proposaltype_id, $proposaltype_names, $proposal_type ".PHP_EOL;

        return array($proposaltype_id, $proposaltype_names, $proposaltype_datanames, $current_type, $proposal_type );
    }

    private static function _get_page_title($_item){
    	
    	$title = $_item->info['talent_name']. " as ";
		
		if ($_item->info['post_type'] == "casting") {
			if ($_item->info['source_name'] != "") { $append_source = " in ".$_item->info['source_name']; } else { $append_source = ""; }
			$title .= $_item->info['role_name'] . $append_source;
		} else {
			$title .= $_item->info['position_name']." of ".$_item->info['source_name'];
		} 
		
		$_item->title = $title;

    	return $_item;
	}
    
    private function _get_list_page_title($page){
        $page_title = "";
        $title_str = "";
        
        if ($page == "filmmaking") {
            $page_title = "Filmmaker Proposals";
            $title_str = "Filmmakers Proposed for Your Favorite Stories";
            
        } else {
            $page_title = ucfirst($page)." Proposals";
            $title_str = "Casting Ideas for Film & TV";
        }
        return array($page_title, $title_str);
    }


    private function _build_categories($categories, $get){
        global $dbHandler;
        
		//print_r($categories);

        $category = null;
        $category_id = "";
        $category_names = "";
        $category_datanames = "";
        $category_fullname = "";
	    $sel_categories_arr = array();

        if(isset($get['json'])){ // ajax request
	        if (isset($get['category_id'])) {
	        	$category_id = $dbHandler->real_escape_string($get['category_id']);
            	$ids = explode("|", $category_id);

				for ($n = 0; $n < count($ids); $n++) {
           
		        	//$id = explode(",", $ids[$n])[0];
					#print "id:".$id ."::".PHP_EOL;
           
		            $cat_index = array_search($ids[$n], $categories['ids']);
		   			#print "cat_index: ".$cat_index .PHP_EOL;
                    #$category_id .= $categories['ids'][$cat_index]."|";
		            $category_names .= $categories['names'][$cat_index].", ";
		            $category_datanames .= $categories['data_names'][$cat_index]."+";
		            $category_fullname .= $categories['full_names'][$cat_index];
	        	}

        	}
        
        }else {

	        if (isset($get['category'])) { // full page request
	            $category = $dbHandler->real_escape_string($get['category']);
	        }
	        else {
	            $category = $categories['data_names'][0];
	        }
	        $sel_categories_arr = explode(" ", $category);

	        for ($n = 0; $n < count($sel_categories_arr); $n++) {
	            $cat_index = array_search($sel_categories_arr[$n], $categories['data_names']);
	            $category_id .= $categories['ids'][$cat_index]."|";
	            $category_names .= $categories['names'][$cat_index].", ";
	            $category_datanames .= $categories['data_names'][$cat_index]."+";
	            $category_fullname .= $categories['full_names'][$cat_index];
        	}
        
        }

		// print " category_names = ";  
	  //       print_r($category_names);
	  //       print PHP_EOL;

			// print " category_fullname = ";  
	  //       print_r($category_fullname);
	  //       print PHP_EOL;

	  //       print " sel_categories_arr = ";  
	  //       print_r($sel_categories_arr);
	  //       print PHP_EOL;

        

        $category_id = rtrim($category_id, "|");
        $category_names = rtrim($category_names, ", "); 
        $category_datanames = rtrim($category_datanames, "+");

        return array($category_id, $category_names, $category_datanames, $categories, $category);
    }


}