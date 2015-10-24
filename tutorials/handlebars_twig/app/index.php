<?php
	
	//index.php
	require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/app_global.php';  	
	
	use app\Views\Shared\HeaderVars;
    use app\Services\AutoActions;
    use app\Services\Router;
    use app\Services\Auth\Auth;
    use app\Models\Notifications;
	use app\Models\Messages;
	use app\Services\UserOnline;


    /** Set some variables for the logged-in user **/
    if(Auth::user_is_logged_in()){ //if(isset($_SESSION['id'])){ 
	    ///
	    //  @TODO
	    //	ONLLY SET THESE IF THEY ARE NOT SET
	    //
	    if(!isset($_SESSION['pic_dark'])){
		    	$_SESSION['pic_dark'] = get_user_profile_image_dark(@Auth::get_logged_in_user_id(), @$_SESSION['main_pic'], @$_SESSION['oauth_id'], array('default_pic'=>"/images/User_Silhouette_mobile_fin_wb.jpg")
		    );
	    }	

	    if(!isset($_SESSION['pic'])){
		    $_SESSION['pic'] = get_user_profile_image(@Auth::get_logged_in_user_id(), @$_SESSION['main_pic'], @$_SESSION['oauth_id'], array('default_pic'=>"/images/inverse_person_silhouette_small.jpg")
		    );
		} 
		
	    // set global notifications count on every page load
	    $notifications_model = new Notifications();
	    $_SESSION['notifications_count'] = $notifications_model->get_unread_message_count((int)Auth::get_logged_in_user_id());
		
		// set global chat message count on evey page load
	    $_SESSION['unread_messages_count'] = Messages::get_unread_count((int)Auth::get_logged_in_user_id());
	    
	    
	    // update user_session 'last_active' on each page load
	    //if(Router::device_is_mobile() == true){
	    	UserOnline::update_user_active((int)Auth::get_logged_in_user_id());
		//}  
		
		
	}

	/*	1014
		Since this file is used by the entire site, we can implement the logic to redirect mobile and non-mobile devices.
		Actual detection and setting of device type occurs in detect_browser.php.
		Ultimately this logic should be moved into app/index.php
	*/		

	//skip over this form when logged in?
	#app\Services\Router::skip_login_page();

	/* 
		if mobile device and not on mobile page, redirect to ??
		if desktop device and not on desktop page, redirect to ??
	*/
	Router::redirect_to_device_page();

   
	/*
		sets a bunch of JS vars and sets a bunch of home page values
	*/

	/**  Global stuff 	**/ 
	$HeaderVars = new HeaderVars();

	$form = "";

	if (Auth::user_is_logged_in()) { //only set the header vars and run auto-actions if user is logged-in
		
		$HeaderVars->set_loggedin_header_vars();


		/*** RUN auto-actions ***/
		AutoActions::runAutoActionsFromHeaderVars();


		//check for proposal overlay -- we could probably move this somewhere else...
	 	if (isset($_REQUEST['overlay']) && $_REQUEST['overlay'] != "") {
	 		
	 		if ($_REQUEST['overlay'] == "proposal") {
				$form = "proposal-overlay";
			}
		}
		else {
			$form = $HeaderVars->form;
			
		}

	}
	else { //not logged in
		$HeaderVars->set_loggedout_header_vars();

	}

	

	//check and store if we are on a mobile device or not
	$device_is_mobile = (Router::device_is_mobile())? 1 : 0 ;

	
	// used in header js. this can be appended to if needed.
	$header_vars_array = array( 
		 'display_name' => $HeaderVars->display_name,
		 'user_email' => $HeaderVars->user_email,
		 'error' => $HeaderVars->error,			 
		 'form' => $form,
		 'form_type' => $HeaderVars->form_type,
		 'form_mode' => $HeaderVars->form_mode,
		 'form_role_id' => $HeaderVars->form_role_id,
		 'form_role_name' => $HeaderVars->form_role_name,
		 'form_role_pic' => $HeaderVars->form_role_pic,
		 'form_proposal_id' => $HeaderVars->form_proposal_id,
		 'form_item_type_id' => $HeaderVars->form_item_type_id,
		 'form_item_user_id' => $HeaderVars->form_item_user_id,	
		 'form_item_name' => $HeaderVars->form_item_name,
		 'form_sub_title' => $HeaderVars->form_sub_title,				
		 'like' => $HeaderVars->like,
		 'like_item_type_id' => $HeaderVars->like_item_type_id,
		 'like_item_id' => $HeaderVars->like_item_id,
		 'like_item_name' => $HeaderVars->like_item_name, 
		 'dislike' => $HeaderVars->dislike,
		 'dislike_item_type_id' => $HeaderVars->dislike_item_type_id,
		 'dislike_item_id' => $HeaderVars->dislike_item_id,
		 'dislike_item_name' => $HeaderVars->dislike_item_name, 
		 'follow' => $HeaderVars->follow,
		 'followed_id' => $HeaderVars->followed_id,
		 'followed_name' => $HeaderVars->followed_name,
		 'messaged' => $HeaderVars->messaged,
		 'messaged_user_id' => $HeaderVars->messaged_user_id,
		 //'commenting' => $HeaderVars->commenting,
		 //'add_character' => $HeaderVars->add_character,	 
		 'device_is_mobile' => $device_is_mobile,
		 'item_type_ids' => $HeaderVars->item_type_ids

	);	  
	
	$props_arrays = array();
	if(Router::device_is_mobile() == false){
		/* 
			TODO: only call this on pages that need it. Example: login-form.php does not need it

			I am not sure if this is the best solution, becuase it puts the kind of functionality we have in Router, out here.
			On the other hand it is a very flexible way of implementing logic based on what page you are on.

			Just pass in an array of one or more items you want to key off ofm and call
				Router::is_file_in_url($urls, Router::get_current_file()) to pull the file name ( like includes/loadproposals.php)
				or
				Router::is_file_in_url($urls, $_SERVER['REQUEST_URI']) to pull the file name ( like terms_of_service )

			use the result to do or not do something	

		*/ 
			$urls = array(
				'addNewProfile.php',
				'addNewMiniProfile.php',
				'get_featured_item_detail.php',
				'get_all_titles.php',	
				'join-form.php',
				'login-form.php',
				'loadproposals_for_title.php',
				'newproposal-form.php',
				'proposal-overlay.php',	
				'prepare_share_images.php',
				'privacy',
				'signup-form.php',
				'terms_of_service',
				'tweet_form.php',
			);


		if( false == Router::is_file_in_url($urls, $_SERVER['REQUEST_URI']) ){
			
			// used in menu browse html
			$props_arrays = $HeaderVars->get_props_arrays();
		}	
	}



	// used in Templates. Passed in as part of options into template manager
	$session_vars = $_SESSION; 
	$server_vars = $_SERVER; 
	$get = $_GET;
	$post = $_POST;


	/** 
	 / end Global stuff
	**/
