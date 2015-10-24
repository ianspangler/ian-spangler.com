<?php
/*
	This class is used by Activity and other classes to re-map data sets per particular item types
	usage: $activity_item = ItemBuilder::_build_support_activity_item($activity_item);
*/
namespace app\Services;
use app\Services\Auth\Auth;

class ItemBuilder {

	private static $liked_ids = null;
	private static $disliked_ids = null;
	private static $context_obj = null;

	public function __construct($_context){
		self::$context_obj = $_context;
		# print " 1 GOT Context ".get_class($_context).PHP_EOL;
		//parent::__construct();
			
	}

	public static function Instance($_context){
		self::$context_obj = $_context;
		
		static $inst = null;
		if ($inst === null) {
			$inst = new ItemBuilder($_context);
		}
		return $inst;
	}

	public static function init($activities, $liked_ids, $disliked_ids){
		
		$activities = self::_get_activity_sub_type( $activities );

		return $activities;
	}

	/* 
		determine sub type by looking at the other columns
	*/
	private static function _get_activity_sub_type( $activities ){
		/* 
		migrated from activity 5/6
		*/
		
		foreach($activities as $activity_item){ 

			// set some defaults
			// call global			
			$activity_item->time_elapsed = getTimeElapsed(strtotime($activity_item->timestamp)) ;
			$activity_item->subtitle = ' FOO ' ;
			$activity_item->item_type_id = $activity_item->role_id; 

			if($activity_item->activity_type == 'like'){				
				$activity_item = self::_build_support_activity_item($activity_item);
			}else
			if($activity_item->activity_type == 'like_credit'){				
				$activity_item = self::_build_support_credit_activity_item($activity_item);
			}else
			
			if($activity_item->activity_type == 'like_profile'){				
				$activity_item = self::_build_support_profile_activity_item($activity_item);
			}else

			if($activity_item->activity_type == 'credit'){
				$activity_item = self::_build_credit_activity_item($activity_item);
			}else
			if($activity_item->activity_type == 'title'){
				$activity_item = self::_build_title_activity_item($activity_item);
			}else
			/*
				if($activity_item->activity_type == 'role'){
					$activity_item = self::_build_role_activity_item($activity_item);
				}
			*/
			if($activity_item->activity_type == 'proposal'){				
				$activity_item = self::_build_proposal_activity_item($activity_item);
			}else
			if($activity_item->activity_type == 'comment'){
				$activity_item = self::_build_comment_activity_item($activity_item);			      			      
			}else
			if($activity_item->activity_type == 'comment_credit'){
				$activity_item = self::_build_comment_credit_activity_item($activity_item);			      			      
			}else	
			if($activity_item->activity_type == 'endorsement'){
				$activity_item = self::_build_endorsement_activity_item($activity_item);			      			      
			}else{
				if(DEBUGGING) print __METHOD__." :: DEBUGGING: Missing Type: ".print_r($activity_item, true);
			}	

		} 

		return $activities;
	}	

	/************* DATA BUILDER FUNCTIONS ************/
	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_proposal_activity_item($activity_item){

		if(!isset($activity_item->more)){
	    	if(DEBUGGING) print "MORE NOT SET for ".$activity_item->id;
	    	return $activity_item;
	    }
	    $activity_item->item_type_id = 1;

	    $activity_item->more->comment = stripslashes($activity_item->more->comment);

	    $activity_item->more->profile_name = $activity_item->more->title_name; 

    	// talent_pic_url ( derived from main_pic in talent or media filename )    	
    	$activity_item->talent_pic_url = get_talent_pic_url(
			array( $activity_item->more->media_talent_pic, $activity_item->more->main_talent_pic), 
			array( $activity_item->more->talent_id ) 
		); 

    	// role_pic_url ( derived from main_pic in roles or media filename )
	    $activity_item->role_pic_url = get_role_pic_url(
				array( $activity_item->more->media_role_pic , $activity_item->more->main_role_pic, $activity_item->more->main_title_pic ), 
				array( $activity_item->more->role_id, $activity_item->more->title_id) 
			); 
		
		// profile_pic_url ( title pic or talent_pic ) 
	    $activity_item->profile_pic_url = get_profile_pic_url(
   			array( $activity_item->more->main_role_pic, $activity_item->more->main_talent_pic, $activity_item->more->main_title_pic ),
   			array( $activity_item->more->role_id, $activity_item->more->talent_id, $activity_item->more->title_id )
	   	); 
		
		$activity_item->talent_name = $activity_item->more->first_name ." ". $activity_item->more->last_name;
		
		// subtitle ( source_format or talent identities , comma delimeted )
		$activity_item->subtitle  = get_subtitle_for_profile($activity_item->item_type_id, $activity_item->more->source_format, $activity_item->more->identity_name, $activity_item->more->title_name);
		
		// profile_url ( title or talent name )
		$activity_item->more->profile_url  = get_url_for_profile(
			array( $activity_item->more->title_url_handle, $activity_item->more->talent_url_handle
			)
		);

		$activity_item->more->proposal_url = get_url_for_proposal($activity_item->more->id);
		$activity_item->action_id = $activity_item->more->id;

		// profile_id		"
		//**$activity_item->profile_id  = get_id_for_profile($activity_item->more->title_id, $activity_item->more->talent_id);
     	
     	// profile_name 	"
		$activity_item->profile_name  = get_name_for_profile($activity_item->more->title_name, $activity_item->talent_name);

		// title status
		$activity_item->more->title_status_id = @$activity_item->more->status_id;		

		if($activity_item->more->identity_name != ""){
			$activity_item->subject = 'an actor for';
			$activity_item->subject = 'a '.$activity_item->more->identity_name.' for';
		}

		if($activity_item->more->talent_id != ""){ // casting/filmmaker proposal
			if(($activity_item->role_id != "") || ($activity_item->proposal_type == "crew") ){ 
				$activity_item->type = 'proposal';
			}
		}
		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;

	}

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_comment_credit_activity_item($activity_item){
		$activity_item->type = 'comment_credit';
	    
	    if(!isset($activity_item->more)){
	    	return $activity_item;
	    }
	    
	    $activity_item->more->comment = stripslashes($activity_item->more->comment);

	    // credit talent pic
 		$credit_pic = $activity_item->more->main_talent_pic;
 		if($activity_item->more->media_talent_pic != ""){
	 		$credit_pic = $activity_item->more->media_talent_pic;
 		}
		$activity_item->talent_pic_url = get_profile_pic_url(
   			array( null, $credit_pic , null, null ),
   			array( null, $activity_item->more->talent_id, null, null )
	   	);
		
		// credityear  
	   	$activity_item->more->credit_year = $activity_item->more->year_released;
		
		// credit type  
	   	//$activity_item->more->credit_type
		
	   	// role pic
		$activity_item->role_pic_url = get_profile_pic_url( // movietitle
   			array( null, null, null, null, $activity_item->more->movie_title_main_pic ),
   			array( null, null, null, null, $activity_item->more->title_id )
	   	);

	    $activity_item->talent_name = $activity_item->more->talent_first_name ." ". $activity_item->more->talent_last_name;
		// movie name
		$activity_item->more->profile_name = $activity_item->more->movie_title_name;

		// credit link url
		$activity_item->more->credit_url = get_url_for_credit($activity_item->more->credit_id);

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;

	}	

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_comment_activity_item($activity_item){
		$activity_item->type = 'comment';
	    
	    if(!isset($activity_item->more)){
	    	return $activity_item;
	    } 

	    $activity_item->more->comment = stripslashes($activity_item->more->comment);
	    // talent_pic_url ( derived from main_pic in talent or media filename )  
		$activity_item->talent_pic_url = get_talent_pic_url(
			array( $activity_item->more->media_talent_pic, $activity_item->more->main_talent_pic), 
			array( $activity_item->more->talent_id) 
		); 

		// role_pic_url ( derived from main_pic in roles or media filename )
	    $activity_item->role_pic_url = get_role_pic_url(
			array( $activity_item->more->media_role_pic, $activity_item->more->main_role_pic, $activity_item->more->main_title_pic ), 
			array( $activity_item->more->role_id, $activity_item->more->title_id) 
		); 

	   	// subtitle  
	   	$activity_item->talent_name = $activity_item->more->talent_first_name ." ". $activity_item->more->talent_last_name;
	   	$activity_item->role_name = $activity_item->more->role_name;
		
		// for casting props
		$activity_item->more->profile_name = $activity_item->more->title_name;
		// link url	   	
	   	$activity_item->more->profile_url = get_url_for_profile(
	   		array($activity_item->more->title_url_handle, $activity_item->more->talent_url_handle
	   		)
	   	);
	   	$activity_item->more->proposal_url = get_url_for_proposal($activity_item->more->proposal_id);
	   	$activity_item->action_id = $activity_item->more->proposal_id;

	   	// identity
	   	if($activity_item->more->proposal_type != 'casting'){
	   		$activity_item->more->role_name = $activity_item->more->identity_name;
	    }
	   	
		// title status
		$activity_item->more->title_status_id = @$activity_item->more->status_id;		

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;

	}

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_endorsement_activity_item($activity_item){
		
		$activity_item->type = 'endorsement';

	    if(!isset($activity_item->more)){
	    	return $activity_item;
	    } 

	    $activity_item->more->comment = stripslashes($activity_item->more->comment);

		// profile_pic_url ( title pic or talent_pic ) 
	    $activity_item->profile_pic_url = get_profile_pic_url(
   			array( $activity_item->more->main_role_pic, $activity_item->more->main_talent_pic, $activity_item->more->main_title_pic ),
   			array( $activity_item->more->role_id, $activity_item->more->talent_id, $activity_item->more->title_id )
	   	); 

	   	// subtitle  
	   	$activity_item->talent_name = $activity_item->more->talent_first_name ." ". $activity_item->more->talent_last_name;
	   	$activity_item->role_name = $activity_item->more->role_name;
		
		$typeid = "";
		if($activity_item->title_id != 0){
			$typeid = 3;
		}else if($activity_item->role_id != 0){
			$typeid = 4;
		}else if($activity_item->comment_talent_id != 0){
			$typeid = 5;
		}	

	   	$activity_item->subtitle = get_subtitle_for_profile($typeid , @$activity_item->more->source_format, $activity_item->more->identity_name, $activity_item->more->title_name);

		// for casting props
		$activity_item->more->profile_name = get_name_for_profile($activity_item->more->title_name, $activity_item->talent_name, $activity_item->role_name);
		
		//identity
		/*if($activity_item->comment_talent_id == 2504) {
			print " identity_type = ". $activity_item->more->identity_type ."\n";
		}*/
		//$activity_item->subtitle = "my activity is ".$activity_item->type ; 
	   	
	   	$activity_item->more->profile_url = get_url_for_profile(
	   		array( $activity_item->more->title_url_handle, $activity_item->more->talent_url_handle
	   		)
	   	);
	   	$activity_item->more->proposal_url = get_url_for_proposal($activity_item->more->proposal_id);
	   	$activity_item->action_id = $activity_item->more->proposal_id;

		// title status
		$activity_item->more->title_status_id = @$activity_item->more->status_id;		

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;

	}

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_credit_activity_item($activity_item){
		$activity_item->type = 'credit';
		$item_type_id = $activity_item->role_id; // becuase this is the name of the column in the UNION Query
		if(!isset($activity_item->more)){
	    	return $activity_item;
	    }
	    //print_r($activity_item);
 		// credit talent pic
 		$credit_pic = $activity_item->more->main_talent_pic;
 		if($activity_item->more->media_talent_pic != ""){
	 		$credit_pic = $activity_item->more->media_talent_pic;
 		}
		$activity_item->talent_pic_url = get_profile_pic_url(
   			array( null, $credit_pic , null, null ),
   			array( null, $activity_item->more->talent_id, null, null ),
   			'MD'
	   	);
		// role pic
		$activity_item->role_pic_url = get_profile_pic_url( // movietitle
   			array( null, null, null, null, $activity_item->more->movie_title_main_pic ),
   			array( null, null, null, null, $activity_item->more->title_id ),
   			'MD'
	   	);
	   	// credityear  
	   	$activity_item->more->credit_year = $activity_item->more->year_released;
		
		// credit type  
		
		// talent name
		$activity_item->talent_name = $activity_item->more->talent_first_name . " ".$activity_item->more->talent_last_name;
		// credit link url
		$activity_item->more->profile_url = get_url_for_credit($activity_item->more->credit_id);
		// movie name
		$activity_item->more->movie_name = $activity_item->more->movie_title_name;

		$activity_item->action_id = $activity_item->more->credit_id;

		// user data
		# $activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;
	}    

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_support_credit_activity_item($activity_item){
		$activity_item->type = 'support_credit';
		$item_type_id = $activity_item->role_id; // becuase this is the name of the column in the UNION Query
		if(!isset($activity_item->more)){
	    	return $activity_item;
	    }

 		// credit talent pic
 		$credit_pic = $activity_item->more->main_talent_pic;
 		if($activity_item->more->media_talent_pic != ""){
	 		$credit_pic = $activity_item->more->media_talent_pic;
 		}
		$activity_item->talent_pic_url = get_profile_pic_url(
   			array( null, $credit_pic , null, null ),
   			array( null, $activity_item->more->talent_id, null, null )
	   	);
		// role pic
		$activity_item->role_pic_url = get_profile_pic_url( // movietitle
   			array( null, null, null, null, $activity_item->more->movie_title_main_pic ),
   			array( null, null, null, null, $activity_item->more->title_id )
	   	);
	   	// credityear  
	   	$activity_item->more->credit_year = $activity_item->more->year_released;
		
		// credit type  
	   	//$activity_item->more->credit_type
		
		// talent name
		$activity_item->talent_name = $activity_item->more->talent_first_name . " ".$activity_item->more->talent_last_name;
		// credit link url
		$activity_item->more->credit_url = get_url_for_credit($activity_item->more->credit_id);
		// movie name
		$activity_item->more->profile_name = $activity_item->more->movie_title_name;

		$activity_item->action_id = $activity_item->more->credit_id;

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;
	}    

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_title_activity_item($activity_item){
		$activity_item->type = 'proposal_title';
		if(!isset($activity_item->more)){
	    	return $activity_item;
	    }
	    $activity_item->item_type_id = 3;
	    
	    $activity_item->profile_name = $activity_item->more->name; 
	    $activity_item->more->profile_name = $activity_item->more->name; 
	    // profile_pic_url ( title pic or talent_pic ) 		
		$activity_item->profile_pic_url = get_profile_pic_url(
			array(null, null, $activity_item->more->main_pic), 
			array(null, null, $activity_item->more->id)
		);

	    // subtitle ( source_format or talent identities , comma delimeted )
		$activity_item->subtitle  = $activity_item->more->source_formats;
		$activity_item->more->profile_url  = get_url_for_profile(
			array( $activity_item->more->url_handle,""
			)
		);
		$activity_item->more->plot_summary = convert_to_link_in_text($activity_item->more->plot_summary);


		$activity_item->action_id = $activity_item->id;

		//status id
		$activity_item->more->title_status_id = @$activity_item->more->status_id;		


		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);
			
		return $activity_item;
	
	}

	/*public static function _build_role_activity_item($activity_item){
		$activity_item->type = 'proposal_role';
		if(!isset($activity_item->more)){
	    	return $activity_item;
	    }
	    $activity_item->item_type_id = 4;
	    
	    $activity_item->profile_name = $activity_item->more->name; 
	    $activity_item->more->profile_name = $activity_item->more->name; 
	    // profile_pic_url ( title pic or talent_pic ) 		
		$activity_item->profile_pic_url = get_profile_pic_url(
			array($activity_item->more->main_pic, null, null ), 
			array($activity_item->more->id, null, null)
		);

	    // subtitle ( source_format or talent identities , comma delimeted )
		#$activity_item->subtitle  = $activity_item->more->source_formats;
		$activity_item->more->profile_url  = get_url_for_profile(
			array( null, null, $activity_item->more->url_handle
			)
		);

		$activity_item->action_id = $activity_item->id;

		$activity_item->subtitle = ' need the title here ' ;

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);
			
		return $activity_item;
	
	}*/
	
	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_support_activity_item($activity_item){
	 	global $item_type_ids;
		$item_type_id = $activity_item->role_id; // becuase this is the name of the column in the UNION Query

		// if title, proposal 
		// not talent role
		$activity_item->type = 'support_'.array_search($item_type_id, $item_type_ids);

	    if(!isset($activity_item->more)){
	    	print "MORE NOT SET for ".$activity_item->id;
	    	return $activity_item;
	    }

	    $activity_item->profile_name = 		$activity_item->more->title_name; 
	    $activity_item->more->profile_name = $activity_item->more->title_name; 
    	// talent_pic_url ( derived from main_pic in talent or media filename )
	    $activity_item->talent_pic_url = get_talent_pic_url(
			array( $activity_item->more->media_talent_pic, $activity_item->more->main_talent_pic ), 
			array( $activity_item->more->talent_id ) 
		); 
 
    	// role_pic_url ( derived from main_pic in roles or media filename )
		$activity_item->role_pic_url = get_role_pic_url(
			array( $activity_item->more->media_role_pic, $activity_item->more->main_role_pic, $activity_item->more->main_title_pic ), 
			array( $activity_item->more->role_id, $activity_item->more->title_id) 
		); 
		
		// profile_pic_url ( title pic or talent_pic ) 		
		$activity_item->profile_pic_url = "";
		
		if($item_type_id == 5){ // talent
			$activity_item->profile_pic_url = get_profile_pic_url(
   				array( null, null, $activity_item->more->main_talent_pic ),
   				array( null, null, $activity_item->more->talent_id )
	   		); 
			
		}else if($item_type_id == 4){ //role
			$activity_item->profile_pic_url = get_profile_pic_url(
   				array( $activity_item->more->main_role_pic, $activity_item->more->main_title_pic ),
   				array( null, null, $activity_item->more->role_id, $activity_item->more->title_id )
	   		); 
		}else{
			$activity_item->profile_pic_url = "";
		}
	   	
	   	$activity_item->talent_name = $activity_item->more->talent_first_name ." ". $activity_item->more->talent_last_name;
		// subtitle ( source_format or talent identities , comma delimeted )
		$activity_item->subtitle  = get_subtitle_for_profile(
				$activity_item->item_type_id, 
				@$activity_item->more->source_format, 
				$activity_item->more->identity_name, 
				$activity_item->more->title_name);

		// profile_url ( title or talent name )
		$activity_item->more->profile_url  = get_url_for_profile(
			array($activity_item->more->title_url_handle, $activity_item->more->talent_url_handle, $activity_item->more->role_url_handle)
			);
		$activity_item->more->proposal_url = get_url_for_proposal($activity_item->more->proposal_id);
		
		if($item_type_id == 1){ 
			$activity_item->action_id = $activity_item->more->proposal_id;
		}else if(array_search($item_type_id , array(3,4,5) )){
			// profile_id		
			$activity_item->action_id  = get_id_for_profile($activity_item->more->title_id, $activity_item->more->talent_id);

		}	

     	
     	// profile_name 	
		$activity_item->profile_name  = get_name_for_profile($activity_item->more->title_name, $activity_item->talent_name);
     	 
		if($activity_item->more->identity_name !=""){
			$activity_item->more->role_name =  $activity_item->more->identity_name;
		}

		// title status
		$activity_item->more->title_status_id = @$activity_item->more->status_id;		

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);

		return $activity_item;

	}

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _build_support_profile_activity_item($activity_item){
		global $item_type_ids;
		$item_type_id = $activity_item->role_id;
	
		if(in_array($item_type_id, array( 1,2,3,4 ) )){
			$activity_item->type = 'support_'.array_search($item_type_id, $item_type_ids);
		}else{
		    $activity_item->type = 'support_title';

		}

		if(!isset($activity_item->more)){
	    	#print "MORE NOT SET for ".$activity_item->id;
	    	return $activity_item;
	    }
			// talent, title, or a role
	    if($item_type_id == 3){ //title
		    $activity_item->more->profile_name = $activity_item->more->title_name; 

		    $activity_item->profile_pic_url = get_profile_pic_url(
				array( null, null, $activity_item->more->main_title_pic ), 
				array( null, null, $activity_item->more->title_id) 
   			); 
   			$activity_item->more->profile_url = get_url_for_profile(
   				array( $activity_item->more->title_url_handle, $activity_item->more->talent_url_handle
   				)
   			);

	    }else if($item_type_id == 4){
		    $activity_item->more->profile_name = $activity_item->more->role_name; 	    

		    $activity_item->profile_pic_url = get_profile_pic_url(
				array( $activity_item->more->main_role_pic, $activity_item->more->main_talent_pic, $activity_item->more->role_title_pic ), 
				array( $activity_item->more->role_id, $activity_item->more->talent_id, $activity_item->more->role_title_id) 
   			); 
   			$activity_item->more->profile_url = get_url_for_profile(
   				array( $activity_item->more->role_title_url_handle, $activity_item->more->talent_url_handle
   					)
   				);

	    }else if($item_type_id == 5){//talent	    
		    $activity_item->more->profile_name = $activity_item->more->talent_first_name ." ".$activity_item->more->talent_last_name; 

		    $activity_item->profile_pic_url = get_profile_pic_url(
				array( null, $activity_item->more->main_talent_pic, $activity_item->more->main_title_pic ), 
				array( null, $activity_item->more->talent_id, $activity_item->more->title_id) 
   			); 
   			$activity_item->more->profile_url = get_url_for_profile(
   				array( "", $activity_item->more->talent_url_handle
   				)
   			);

	    }
	    
    	// talent_pic_url ( derived from main_pic in talent or media filename )
	    $activity_item->talent_pic_url = ""; 
		 
		// subtitle ( source_format or talent identities , comma delimeted )
		$activity_item->subtitle  = get_subtitle_for_profile($activity_item->item_type_id, @$activity_item->more->source_format, $activity_item->more->identity_name, $activity_item->more->role_title_name);
			
		// title status
		$activity_item->more->title_status_id = @$activity_item->more->status_id;	

		// user data
		$activity_item = self::_attach_active_user_data_for_item($activity_item);
		$activity_item = self::_attach_current_user_data_for_item($activity_item);


		$activity_item->action_id = $activity_item->talent_id;

		return $activity_item;
	}

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _attach_current_user_data_for_item($activity_item){
		
		if(is_array(self::$liked_ids)){

			/*
				print "\n Looking at item :".PHP_EOL;
				print_r($activity_item->more->id); ;
				print "Likable id = ".@$activity_item->likeable_item. PHP_EOL;
			*/
	        if(isset($activity_item->likeable_item) && in_array($activity_item->likeable_item, self::$liked_ids)){
	        	#print "===== YES ".@$activity_item->likeable_item  .PHP_EOL;
		        $activity_item->more->user_liked = "Y";
	        }else{
		    	#print "===== NO ".@$activity_item->likeable_item  .PHP_EOL;
		    	#print "Not in array ".PHP_EOL;
		    	#print_r(self::$liked_ids);

		        $activity_item->more->user_liked = "N";
	        }

		}
		

		if(is_array( self::$disliked_ids)){
			//user_disliked: 
	        if(isset($activity_item->likeable_item) && in_array($activity_item->likeable_item, self::$disliked_ids)){
	       		#print "===== YES(D) ".$activity_item->likeable_item  .PHP_EOL;
		    	$activity_item->more->user_disliked = "Y";
			}else{
		        $activity_item->more->user_disliked = "N";
	        }
    	}

		return $activity_item;
	}	

	/*
		takes an activity_item (a stdclass) 
		returns an activity_item (a stdclass) 
	*/
	public static function _attach_active_user_data_for_item($activity_item){

        // user_id: item.user_id,
		// user_name: 
		$activity_item->more->user_full_name = $activity_item->more->user_first_name . " " . $activity_item->more->user_last_name;
        // user_img: 
        $activity_item->more->user_img = get_user_profile_image($activity_item->user_id, $activity_item->more->user_pic, $activity_item->more->oauth_uid);
 		
		return $activity_item;
	}
	/************* END DATA BUILDER FUNCTIONS ************/

	public static function set_liked_ids($ids){
		self::$liked_ids = $ids;
	}
	public static function set_disliked_ids($ids){
		self::$disliked_ids = $ids;
	}

	public static function get_liked_ids($item_ids = array()){
		if(count($item_ids) < 1 ){ return array(); }

		if(!isset(self::$liked_ids)){
			$_user_id = self::_get_user_who_liked();
		
			if( (int)$_user_id > 0 ){
				try{
					// call global
					self::$liked_ids = get_user_likes($type_id = null, (int)$_user_id , $item_ids);
				}
            	catch (Exception $e) {
             		//if(DEBUGGING) print $e->getMessage();
              		error_log( __CLASS__."::".__FUNCTION__ ." ". $e->getMessage());
         		} 
			}else{
				//
			}
		}else{
			//
		}
		
		return self::$liked_ids;
	}
	/*
	similar to whats in Activity
    gets the right user_id based in the context
	*/
	private static function _get_user_who_liked(){
		// how do we know if we are on profile or home?
		if(preg_match("/news/i", get_class(self::$context_obj) ) ){ 
			return (int) self::$context_obj->get_current_user_id();// $current_user_id;
		}else{ // Activity
			return (int)Auth::get_logged_in_user_id();
		}

	}

	public static function get_disliked_ids($item_ids = array()){
		if(count($item_ids) < 1 ){
			return array();
		}

		if(!isset(self::$disliked_ids)){
			// call global
			self::$disliked_ids = get_user_dislikes($type_id = null, self::_get_user_who_liked(), $item_ids);
		}
		return self::$disliked_ids;
	}

}
