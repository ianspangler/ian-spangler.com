<?php
namespace app\Views\Shared;
/* 
	This class controls next and back navigation for post pages (proposals and credits)
*/
class PostPageNavigator { 

	//2015 Oscar nominees (maybe we can move this somewhere else..)
	private static $oscars_ids = array(3843,3841,3842,3688,4431,4334,3839,1520,3693,4354,4364,4432,4374,4381,4402,4394,4410,4433,1933);
	
	public static function get_track($title_id, $default_track) {
	
		$preferred_track = self::get_preferred_track($default_track);

		//if post doesn't have a title associated with it, use the talent track
		if ($preferred_track != "featured" && $title_id == "") {
			return "talent";
		}
		else {
			return $preferred_track;
		}

	}

	private static function get_preferred_track($default_track) {
		return isset($_GET['track']) ? $_GET['track']: $default_track;
	}

	/*** FOR MOBILE: GET RELATED PROPOSALS FOR NEXT/ BACK NAVIGATION ***/
	public static function getNextAndPrevPostId($post_info, $track, $page = "proposal") {

		//get array of proposal IDs
		$post_ids = self::getRelatedPostIds($post_info, $track, $page);
		if (empty($post_ids)) {
			return false;
		}

		
		//get index of current post id (page that we're on) in list of post ids
		$curr_index = array_search($post_info['post_id'], $post_ids);
		
		//determine next and previous indices
		//if you're at the end of array, loop back to beginning
		//if you're at the beginning of array, loop to end
		if ($curr_index == count($post_ids) -1) { $next_index = 0; } else { $next_index = $curr_index +1; }
		if ($curr_index == 0) { $prev_index = count($post_ids) -1; } else { $prev_index = $curr_index -1; }

		$next_id = $post_ids[$next_index];
		$prev_id = $post_ids[$prev_index];

		//check if there is nowhere to go except here
		if ($post_info['post_id'] == $next_id) { $next_id = ""; }
		if ($post_info['post_id'] == $prev_id) { $prev_id = ""; }

		$rel_ids = array('next'=>$next_id, 'prev'=>$prev_id);
		return $rel_ids;

	}

	public static function get_oscars_ids() {
		return self::$oscars_ids;
	}


	private static function getRelatedPostIds($post_info, $track, $page) {
		
		global $dbHandler;

		//determine what "track" we are on in terms of navigating from one post to another
		switch ($track)
		{
			case "featured": //for proposals that are featured by admins
			{
				
				/* CACHING */
				$key_name = "related_postids_".$page."_".$track;
				$expires = (60 * 5); // 5 minutes

				// get all posts that are featured proposals
				$sql = "SELECT ".$page."_id FROM featured_".$page."s WHERE date_scheduled <= CURRENT_TIMESTAMP() ORDER BY date_scheduled DESC";
				
				break;
			}
			case "talent": //for actor or filmmaker
			{
				/* CACHING */
				$key_name = "related_postids_".$page."_".$post_info['talent_id']."_".$track;
				$expires = (60 * 5); // 5 minutes

				// get all posts for current talent (actor/ filmmaker)
				if ($page == "proposal") {
					$sql = "SELECT ".$page."_id FROM ".$page."s WHERE talent_id = ".$post_info['talent_id']." 
							ORDER BY officially_cast DESC, num_likes DESC, date_posted DESC";
				}
				else if ($page == "credit") {
					$sql = "SELECT credit_id FROM credits cr INNER JOIN movie_titles mt ON mt.title_id = cr.title_id 
							WHERE cr.talent_id = ".$post_info['talent_id']." 
							ORDER BY mt.year_released DESC, cr.num_likes DESC";
				}

				
				break;
			}
			case "oscars": /* special, just for Oscar nominees */
			{
				//return all posts that are oscars posts
				return self::get_oscars_ids();//self::$oscars_ids;
				break;
			}
			default: //story or movie title
			{
				/* TO DO: figure out how to limit the result set here */
				// get all posts for current title
	
				/* CACHING */
				$key_name = "related_postids_".$page."_".$post_info['title_id']."_".$track;
				$expires = (60 * 5); // 5 minutes

				if ($page == "proposal") { $order_by_clause = "officially_cast DESC, role_id ASC, crew_position_id ASC, num_likes DESC"; }
				else if ($page == "credit") { $order_by_clause = "role_id ASC, crew_position_id ASC, num_likes DESC"; }
				
				$sql = "SELECT ".$page."_id FROM ".$page."s WHERE title_id = ".$post_info['title_id']." 
						ORDER BY ".$page."_type = 'casting' DESC, ".$order_by_clause;

						/*OR role_id IN (SELECT role_id FROM roles WHERE primary_title_id = ".$post_info['title_id'].") */

				//print($sql);
				//exit;
				
			}
		
		}	

		$post_ids = array();

		$result = $dbHandler->get($sql, $key_name, $expires);

		if (is_array($result)){
			foreach($result as $row) {
				$post_ids[] = $row[$page.'_id'];
			}
		}
		else {
			error_log(__CLASS__.":".__FUNCTION__."related post ID query failed: ".$dbHandler->error(true));
			
		}
	
		return $post_ids;

	}

}
