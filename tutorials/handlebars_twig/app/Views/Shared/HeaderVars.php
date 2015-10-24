<?php
	/**
		Used to set a bunch of global JS vars in the header_js.html file
	*/
namespace app\Views\Shared;	
	
	class HeaderVars { 
	
		var $display_name ;
		 var $user_email ;
		 var $error ;			 
		 var $form ;
		 var $form_type ;
		 var $form_mode ;
		 var $form_role_id ;
		 var $form_role_name ;
		 var $form_role_pic ;
		 var $form_proposal_id ;
		 var $form_item_type_id ;
		 var $form_item_user_id ;
		 var $form_item_name ;
		 var $form_sub_title ;					
		 var $like ;
		 var $like_item_type_id ;
		 var $like_item_id ; 
		 var $like_item_name ;
		 var $like_item_talent_id ; 
		 var $like_item_role_id ; 
		 var $like_item_title_id ; 
		 var $dislike ;
		 var $dislike_item_type_id ;
		 var $dislike_item_id ;
		 var $dislike_item_name ;
		 var $commenting ;
		 var $follow ;
		 var $followed_id ;
		 var $followed_name ;
		 var $messaged ;
		 var $messaged_user_id ;
		 var $item_type_ids ;
		 //var $add_character ;

		function set_loggedin_header_vars(){
		
			global $item_type_ids;

			if (@$_SESSION['username'] != "") {
				$this->display_name = $_SESSION['user_fn'];
				if ($_SESSION['user_ln'] != "") {
					$this->display_name .= "  ".$_SESSION['user_ln'][0].".";
				}
			} 
			else {
				$this->display_name = @$_SESSION['email'];
			} 
		
			if (isset($_GET['user'])) {
				include "check_registrants.php";
				$this->user_email = $_GET['user'];
			}
			

			if (isset($_SESSION['error'])) {
				$this->error = $_SESSION['error'];				
				unset($_SESSION['error']);
			}  
			
			$this->form = '';
			$this->form_type = '';
			$this->form_mode = '';
			$this->form_role_id = '';
			$this->form_role_name = '';
			$form_role_pic = '';
			$this->form_proposal_id = '';
			$this->form_item_type_id = '';
			$this->form_item_user_id = '';
			$this->form_item_name = '';
			$this->form_sub_title = '';

			$this->item_type_ids = $item_type_ids;
			
			
			if (isset($_SESSION['form'])) {
				$this->form = $_SESSION['form'];
				unset($_SESSION['form']);
			}
			if (isset($_SESSION['form_type'])) {
				$this->form_type = $_SESSION['form_type'];
				unset($_SESSION['form_type']);
			}
			if (isset($_SESSION['form_mode'])) {
				$this->form_mode = $_GET['form_mode'];
				unset($_SESSION['form_mode']);
			}
			if (isset($_SESSION['form_role_id'])) {
				$this->form_role_id = $_SESSION['form_role_id'];
				unset($_SESSION['form_mode']);
			}
			if (isset($_SESSION['form_role_name'])) {
				$this->form_role_name = $_SESSION['form_role_name'];
				unset($_SESSION['form_role_name']);
			}
			if (isset($_SESSION['form_role_pic'])) {
				$this->form_role_pic = $_SESSION['form_role_pic'];
				unset($_SESSION['form_role_pic']);
			}			
			if (isset($_SESSION['form_proposal_id'])) {
				$this->form_proposal_id = $_SESSION['form_proposal_id'];
				unset($_SESSION['form_proposal_id']);
			}
			if (isset($_SESSION['form_item_type_id'])) {
				$this->form_item_type_id = $_SESSION['form_item_type_id'];
				unset($_SESSION['form_item_type_id']);
			}
			if (isset($_SESSION['form_item_user_id'])) {
				$this->form_item_user_id = $_SESSION['form_item_user_id'];
				unset($_SESSION['form_item_user_id']);
			}
			if (isset($_SESSION['form_item_name'])) {
				$this->form_item_name = $_SESSION['form_item_name'];
				unset($_SESSION['form_item_name']);
			}
			if (isset($_SESSION['form_sub_title'])) {
				$this->form_sub_title = $_SESSION['form_sub_title'];
				unset($_SESSION['form_sub_title']);
			}
			if (isset($_SESSION['commenting'])) {
				$this->commenting = $_SESSION['commenting'];
				unset($_SESSION['commenting']);
			}
			if (isset($_SESSION['follow'])) {
				$this->follow = $_SESSION['follow'];
				$this->followed_id = $_SESSION['followed_id'];
				$this->followed_name = $_SESSION['followed_name'];
				unset($_SESSION['follow']);
				unset($_SESSION['followed_id']);
				unset($_SESSION['followed_name']);
			}
			if (isset($_SESSION['messaged'])) {
				$this->messaged = $_SESSION['messaged'];
				$this->messaged_user_id = $_SESSION['messaged_user_id'];
				unset($_SESSION['messaged']);
				unset($_SESSION['messaged_user_id']);
			}
			

			$this->like = '';
			$this->like_item_type_id = '';
			$this->like_item_id = '';
			$this->like_item_name = '';
			
			if (isset($_SESSION['like'])) {
			
				$this->like = $_SESSION['like'];
				$this->like_item_type_id = $_SESSION['like_item_type_id'];
				$this->like_item_id = $_SESSION['like_item_id'];
				$this->like_item_name = $_SESSION['like_item_name'];
				$this->like_item_talent_id = $_SESSION['like_item_talent_id'];
				$this->like_item_role_id = $_SESSION['like_item_role_id'];
				$this->like_item_title_id = $_SESSION['like_item_title_id'];

				unset($_SESSION['like']);
				unset($_SESSION['like_item_type_id']);
				unset($_SESSION['like_item_id']);
				unset($_SESSION['like_item_name']);
				unset($_SESSION['like_item_talent_id']);
				unset($_SESSION['like_item_role_id']);
				unset($_SESSION['like_item_title_id']);
				
			}
			
			$this->dislike = '';
			$this->dislike_item_type_id = '';
			$this->dislike_item_id = '';
			$this->dislike_item_name = '';
			
			if (isset($_SESSION['dislike'])) {
			
				$this->dislike = $_SESSION['dislike'];
				$this->dislike_item_type_id = $_SESSION['dislike_item_type_id'];
				$this->dislike_item_id = $_SESSION['dislike_item_id'];
				$this->dislike_item_name = $_SESSION['dislike_item_name'];
				unset($_SESSION['dislike']);
				unset($_SESSION['dislike_item_type_id']);
				unset($_SESSION['dislike_item_name']);
				unset($_SESSION['dislike_item_id']);
				
			}

		}


		function set_loggedout_header_vars(){

			global $item_type_ids;

			if (isset($_SESSION['error'])) {
				$this->error = $_SESSION['error'];				
				unset($_SESSION['error']);
			}  

			$this->item_type_ids = $item_type_ids;
			
			
		}

	// moved php->js to header_js.html	

		function get_props_arrays(){ 
			
			$casting_props_arr = array();
			$sql = "SELECT p.talent_id, p.role_id, tm.filename AS t_filename, rm.filename AS r_filename, 
						t.main_pic AS talent_pic, r.main_pic AS role_pic,
						CONCAT(t.first_name,' ',t.last_name) AS tal_name, r.name AS role_name 
						FROM proposals p 
						LEFT JOIN talent t ON t.talent_id = p.talent_id
						LEFT JOIN roles r ON r.role_id = p.role_id
						LEFT JOIN media tm ON tm.media_id = p.talent_pic_id
						LEFT JOIN media rm ON rm.media_id = p.role_pic_id 
						WHERE p.proposal_type = 'casting' AND r.main_pic <> '' AND r.main_pic_geom <> '' 
						ORDER BY p.num_likes DESC, p.proposal_id DESC LIMIT 1";

			$casting_props_arr = $this->get_items($sql, "casting_props_arr");	

			$filmmaking_props_arr = array();
			// filmmaking
			$sql = "SELECT p.talent_id, tm.filename AS t_filename, 
						CONCAT(t.first_name,' ',t.last_name) AS tal_name, ti.name AS title_name, 
						t.main_pic AS talent_pic, ti.main_pic AS title_pic, ti.title_id
						FROM proposals p 
						LEFT JOIN talent t ON t.talent_id = p.talent_id
						LEFT JOIN titles ti ON ti.title_id = p.title_id
						LEFT JOIN media tm ON tm.media_id = p.talent_pic_id
						WHERE p.proposal_type = 'crew'
						ORDER BY p.num_likes DESC, p.proposal_id DESC LIMIT 1";
			
			$filmmaking_props_arr = $this->get_items($sql, "filmmaking_props_arr");	

			// moved this from the moiddle of the index pagr
			$talent_props_arr = array();
			$sql = "SELECT t.talent_id, t.main_pic, CONCAT(t.first_name,' ',t.last_name) AS name
					FROM talent t
					WHERE t.main_pic <> '' AND t.main_pic_geom <> '' AND t.is_actor = 'Y'
					ORDER BY t.num_likes DESC, t.talent_id DESC LIMIT 3";
					
			$talent_props_arr = $this->get_items($sql, "talent_props_arr");	
			 
			//
			// moved this from the moiddle of the index pagr
			$roles_props_arr = array();
			$sql = "SELECT r.role_id, r.main_pic, r.name 
					FROM roles r
					WHERE r.main_pic <> '' AND r.main_pic_geom <> ''
					GROUP BY r.primary_title_id
					ORDER BY r.num_likes DESC, r.role_id DESC LIMIT 3";
						
			$roles_props_arr = $this->get_items($sql, "roles_props_arr");	
			
			//
			// moved this from the moiddle of the index pagr
			$filmmakers_props_arr = array();
			$sql = "SELECT t.talent_id, t.main_pic, CONCAT(t.first_name,' ',t.last_name) AS name 
					FROM talent t
					WHERE t.main_pic <> '' AND t.main_pic_geom <> '' AND t.is_actor <> 'Y'
					ORDER BY t.num_likes DESC, t.talent_id DESC LIMIT 3";
			$filmmakers_props_arr = $this->get_items($sql, "filmmakers_props_arr");		

 			//
			// moved this from the moiddle of the index pagr
			$stories_props_arr = array();
			$sql = "SELECT ti.title_id, ti.main_pic, ti.name 
					FROM titles ti
					WHERE ti.main_pic <> '' AND ti.main_pic_geom <> ''
					ORDER BY ti.num_likes DESC, ti.title_id DESC LIMIT 3";
			$stories_props_arr = $this->get_items($sql, "stories_props_arr");		
			
			//
			// moved this from the middle of the index pagr
			$movies_props_arr = array(); 
			$sql = "SELECT ti.title_id, ti.main_pic, ti.name  
					FROM titles ti
					LEFT JOIN statuses st ON ti.status_id = st.status_id
					WHERE ti.main_pic <> '' AND ti.main_pic_geom <> '' AND st.group_id = 2
					ORDER BY ti.num_likes DESC, ti.title_id DESC LIMIT 3";

			$movies_props_arr = $this->get_items($sql, "movies_props_arr");	


			//
			// moved this from the middle of the index pagr
			$tvshows_props_arr = array(); 
			$sql = "SELECT ti.title_id, ti.main_pic, ti.name 
					FROM titles ti
					LEFT JOIN statuses st ON ti.status_id = st.status_id
					WHERE ti.main_pic <> '' AND ti.main_pic_geom <> '' AND st.group_id = 3
					ORDER BY ti.num_likes DESC, ti.title_id DESC LIMIT 3";

			$tvshows_props_arr = $this->get_items($sql, "tvshows_props_arr");	
							
			return array(
				"movies_props_arr"=> $movies_props_arr,
				"tvshows_props_arr"=> $tvshows_props_arr,
				"stories_props_arr"=> $stories_props_arr,
				"filmmakers_props_arr"=> $filmmakers_props_arr,
				"roles_props_arr"=> $roles_props_arr, 
				"talent_props_arr"=> $talent_props_arr,
				"casting_props"=> $casting_props_arr, 
				"filmmaking_props"=> $filmmaking_props_arr
			);
			
		}


		function get_items($sql, $key_name) {
			
			global $dbHandler;

			//pass expiration time (in seconds) for cache objects to expire 
			$expires = (24 * 60 * 60);
			return $dbHandler->get($sql, $key_name, $expires);
		}

}


	// flush the buffer
	//flush(); 	//???????
	


