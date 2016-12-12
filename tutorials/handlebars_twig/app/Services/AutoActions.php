<?php
namespace app\Services;
use app\Models\LikeUnlike;
use app\Models\DislikeUndislike;
use app\Models\Followers;
use app\Services\Auth\Auth;

class AutoActions {
	
	public static function runAutoActionsFromHeaderVars() {

		//check if not logged in 
		if(!Auth::user_is_logged_in()){return;}//if (@$_SESSION['id'] == "") { return; }

		self::runFollowAction();
		self::runLikeAction();
		self::runDislikeAction();
		
	}

	//Following
	private static function runFollowAction() {
		global $HeaderVars;

		if (isset($HeaderVars->follow) && $HeaderVars->follow == 'Y') {

			$follower_id = Auth::get_logged_in_user_id();//$_SESSION['id'];
			$followed_id = $HeaderVars->followed_id;

			//take action
			$followers_model = new Followers();
			$followers_model->post_follow($follower_id, $followed_id, getNow());
			
		}
	}

	//Like
	private static function runLikeAction() {
		global $HeaderVars, $item_type_ids;

		if (isset($HeaderVars->like) && $HeaderVars->like == 'Y') {
			$user_id = Auth::get_logged_in_user_id();//$_SESSION['id'];
			$item_id = $HeaderVars->like_item_id;
			$item_name = $HeaderVars->like_item_name;
			$item_type_id = $HeaderVars->like_item_type_id;
			$item_type = array_search($item_type_id, $item_type_ids);
			$talent_id = $HeaderVars->like_item_talent_id;
			$role_id = $HeaderVars->like_item_role_id;
			$title_id = $HeaderVars->like_item_title_id;
			$datetime = getNow();

			//take action
			$like_unlike = new LikeUnlike();
			$like_unlike->post_like($user_id, $item_id, $item_type, $talent_id, $role_id, $title_id, $datetime);
		
		}
	}

	//Dislike
	private static function runDislikeAction() {
		global $HeaderVars, $item_type_ids;

		if (isset($HeaderVars->dislike) && $HeaderVars->dislike == 'Y') {
			$user_id = Auth::get_logged_in_user_id();//$_SESSION['id'];
			$item_id = $HeaderVars->dislike_item_id;
			$item_name = $HeaderVars->dislike_item_name;
			$item_type_id = $HeaderVars->dislike_item_type_id;
			$item_type = array_search ($item_type_id, $item_type_ids);
			$datetime = getNow();

			//take action
			$dislike_undislike = new DislikeUndislike();
			$dislike_undislike->post_dislike($user_id, $item_id, $item_type, $datetime);
		}
	}


}