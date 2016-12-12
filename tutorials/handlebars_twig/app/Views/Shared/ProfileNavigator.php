<?php
namespace app\Views\Shared;
/* 
This class controls next and back navigation for profile pages (stories, talent, etc.)
*/
class ProfileNavigator { 

	
	public static function get_track($profile_id, $default_track) {
	
		return self::get_preferred_track($default_track);

	}

	private static function get_preferred_track($default_track) {
		return isset($_GET['track']) ? $_GET['track']: $default_track;
	}

	/*** FOR MOBILE: GET RELATED PROPOSALS FOR NEXT/ BACK NAVIGATION ***/
	public static function getNextAndPrevProfileId($profile_info, $track, $page = "title") {

		//get array of proposal IDs
		$profile_ids = self::getRelatedProfileIds($profile_info, $track, $page);

		if (empty($profile_ids)) {
			return false;
		}

		//get index of current profile id (page that we're on) in list of profile ids
		$curr_index = array_search($profile_info['url_handle'], $profile_ids);

		
		//determine next and previous indices
		//if you're at the end of array, loop back to beginning
		//if you're at the beginning of array, loop to end
		if ($curr_index == count($profile_ids) -1) { $next_index = 0; } else { $next_index = $curr_index +1; }
		if ($curr_index == 0) { $prev_index = count($profile_ids) -1; } else { $prev_index = $curr_index -1; }

		$next_id = $profile_ids[$next_index];
		$prev_id = $profile_ids[$prev_index];

		//check if there is nowhere to go except here
		if ($profile_info['url_handle'] == $next_id) { $next_id = ""; }
		if ($profile_info['url_handle'] == $prev_id) { $prev_id = ""; }


		$rel_ids = array('next'=>$next_id, 'prev'=>$prev_id);
		
		return $rel_ids;

	}


	private static function getRelatedProfileIds($profile_info, $track, $page) {
		
		global $dbHandler;


		//determine what "track" we are on in terms of navigating from one profile to another
		switch ($track)
		{
			case "featured": //for stories that are featured by admins
			{

				/* CACHING */
				$key_name = "related_profileids_".$page."_".$track;
				$expires = (60 * 5); // 5 minutes

				// get all profiles that are featured proposals
				$sql = "SELECT ti.url_handle FROM featured_stories f
						INNER JOIN titles ti ON ti.title_id = f.title_id 
						WHERE f.date_scheduled <= CURRENT_TIMESTAMP() ORDER BY f.date_scheduled DESC";
				
				break;
			}
			
			default: //
			{
				/* TO DO: figure out how to limit the result set here */
				//$genres_arr = explode(',', $profile_info['genre_id']);
				///$genres = preg_replace('/, /', '-', $profile_info['genre_id']);
				
				//$primary_genre = $genres_arr[0];

				/* CACHING */
				/*$key_name = "related_profileids_".$page."_format_".$profile_info['source_format_id']."_genre_".$genres;
				////print($key_name."<br>");
				$expires = (60 * 5); // 5 minutes

				if ($page == "title") {

					$sql = "SELECT url_handle FROM titles WHERE source_format_id = ".$profile_info['source_format_id']." 
							AND genre_id = '".$profile_info['genre_id']."' ORDER BY date_posted DESC LIMIT 60";
				
				}*/

				return array(); //temporary
				
			}
		
		}	

		$profile_urls = array();

		$result = $dbHandler->get($sql, $key_name, $expires);

		if (is_array($result)){
			foreach($result as $row) {
				$profile_urls[] = $row['url_handle'];
			}
		}
		else {
			error_log(__CLASS__.":".__FUNCTION__."related profile ID query failed: ".$dbHandler->error(true));
			
		}
	
		//print_r($profile_urls);
		//exit;
		
		return $profile_urls;

	}

}
