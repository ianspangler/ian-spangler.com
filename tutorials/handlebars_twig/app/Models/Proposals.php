<?php
namespace app\Models;
use app\Services\Auth\Auth;
use StdCLass;
use Exception;

class Proposals extends BaseModel{

	/*
	
	*/
	protected static $table = 'proposals';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'proposal_id';


	/* total count */
	protected static $total_count = 0;

	/*
		relationships
	*/
	protected static $related = array(
		"has_many" => array(
			),
		"has_one" => array(
			"talent" => array()
			)
	);
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'proposal_';
	protected static $proposal_fields = "";
	protected static $join_tables = "";

	public function __construct($config = null){

		if(is_array($config) && isset($config['set_size'])){
			self::set_size($config['set_size']);			
		}
		//
		parent::__construct();

		/** 
		set up some variables that we will use because a proposal is such a complex thing
		this data is used for the list page as well as the single view page
		*/
		self::$proposal_fields = "p.proposal_id as id, p.talent_id, p.title_id, p.talent_pic_id, p.role_pic_id, p.proposal_type,
				p.proposal_image, 'proposal' as activity_type, null as type, p.date_posted as 'timestamp', p.officially_cast, 
				p.anonymous, p.comment,
				i.identity_id, i.identity_name, 				
				r_media.filename AS media_role_pic, 
				t_media.filename as media_talent_pic, t_media.pic_geom AS talent_pic_geom, r_media.pic_geom AS role_pic_geom, 				
				t.talent_id, t.first_name, t.last_name, t.main_pic AS main_talent_pic, t.url_handle as talent_url_handle,
				r.role_id, r.name AS role_name, r.main_pic AS main_role_pic, r.title_id, r.url_handle as role_url_handle,
				ti.title_id, ti.name AS title_name, ti.main_pic AS main_title_pic, ti.url_handle AS title_url_handle, ti.status_id, ti.genre_id, ti.source_format_id, 
				sf.format_name AS source_format, st.data_name, 
				f.id AS 'featured',
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic ";

		self::$join_tables = "LEFT JOIN talent t ON t.talent_id = p.talent_id
				LEFT JOIN roles r ON r.role_id = p.role_id
				LEFT JOIN identities i ON i.identity_id = p.crew_position_id 
				LEFT JOIN titles ti ON ti.title_id = p.title_id
				LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id
				LEFT JOIN media r_media ON r_media.media_id = p.role_pic_id 
				LEFT JOIN media t_media ON t_media.media_id = p.talent_pic_id
				LEFT JOIN users u ON p.user_id = u.user_id 
				LEFT JOIN statuses st ON st.status_id = ti.status_id 
				LEFT JOIN featured_proposals f ON f.proposal_id = p.proposal_id";
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Proposals();
		}
		return $inst;
	}

	/*

	*/
	public static function get($_id){  
		
		$sql = "SELECT ".self::$proposal_fields."
				FROM ".self::$table." p
				".self::$join_tables."
				WHERE p.proposal_id  = $_id ";


		#error_log("====================================================");

		$result = null;
		
		try{
			$result = self::_handle_db_query($sql, array());
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
		      error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
		      return false;
		}	
		foreach($result as $row){
		// return an array
           return  $row;
		}
		return null; 
		
	}

	public function get_result($_ids, $extras = NULL, $offset = NULL){  
		
		$result = null;


		if ($_ids == "") { return array(); }

		$sql = "SELECT ".self::$proposal_fields."
				FROM ".self::$table." p
				".self::$join_tables."
				WHERE p.proposal_id IN($_ids)";


		$options = array();
		///$keyname = "";
		if (isset($extras)) {	
			$sql .=	" ORDER BY ".self::_util_get_sort_by($extras->filter_proposaltype_id, $extras->sortby, $extras->sort_direction);
			$keyname = self::getListFilterKeyname($extras);
			$options = array('key_name'=>$keyname."_".$offset, 'expires'=>(60 * 5), 'proposal_id'=>"0");
		}


		#error_log("MIGRATION:  $sql");
		#error_log("====================================================");
		//print(str_replace(",","",$_ids));
		


		try{
			$result = self::_handle_db_query($sql, $options);
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
		      error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
		      return false;
		}	
		

		return $result; 
	}

	/** for newsfeed/ activity **/
	public static function get_proposal_detail_by_ids_sql($ids){
	
		return "SELECT p.proposal_id as 'id', p.talent_id, p.title_id, p.talent_pic_id, p.role_pic_id, p.proposal_type,
					p.proposal_image, c.comment, p.officially_cast, 
					i.identity_id, i.identity_name, 
					r_media.filename AS media_role_pic, 
					t_media.filename as media_talent_pic, 
					t.talent_id, t.first_name, t.last_name, t.main_pic AS main_talent_pic, t.url_handle as talent_url_handle,
					r.role_id, r.name AS role_name, r.main_pic AS main_role_pic, r.title_id, r.url_handle as role_url_handle,
					ti.title_id, ti.name AS title_name, ti.main_pic AS main_title_pic, ti.url_handle AS title_url_handle, ti.status_id, 
					sf.format_name AS source_format,
					u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic
					FROM ".self::$table." p
					LEFT JOIN talent t ON t.talent_id = p.talent_id
					LEFT JOIN roles r ON r.role_id = p.role_id
					LEFT JOIN identities i ON i.identity_id = p.crew_position_id 
					LEFT JOIN titles ti ON ti.title_id = p.title_id
					LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id
					LEFT JOIN media r_media ON r_media.media_id = p.role_pic_id 
					LEFT JOIN media t_media ON t_media.media_id = p.talent_pic_id
					LEFT JOIN comments c ON c.proposal_id = p.proposal_id AND c.datetime = p.date_posted
					LEFT JOIN users u ON p.user_id = u.user_id

					WHERE p.proposal_id IN($ids) ";

	}

	public function get_comment_count($_id = 0){
		
		$sql = "SELECT count(p.proposal_id) as 'count' 
		FROM " .self::$table. " p
		LEFT JOIN comments c on c.proposal_id = p.proposal_id
		WHERE p.proposal_id = $_id 
		GROUP BY p.proposal_id";
		
		# print $sql;
		$result = null;
		
		try{
			$result = self::_handle_db_query($sql, array());
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
		      error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
		      return false;
		}
		foreach($result as $row){
		// return an array
           return  $row['count'];
		}

		return 0; 
	}
	
	public function get_proposals_count() {
		
		//return 20;
		return self::$total_count;
	}

	public function get_numlikesdislikes_count($_id){


		$expires = (60 * 1); // 1 min.
		$result = self::get_list(array(
            "key_name"=>self::$cache_key_prefix. "nmlksct_".$_id,
            "expires"=>$expires,
             "what"=>array( // column list
                "num_likes, num_dislikes"
              ),
             "where"=>array(
                array(
                    "name"=>self::$primary_id,
                    "operator"=>"=",
                    "value"=>$_id
                ) 
            )
           ) 
        );

		if (is_array($result)){
			foreach($result as $row){
				return $row;
			}
		}
		return 0;


	}

	public function get_numlikesdislikes_counts($_ids, $extras, $offset) {

		$result = array();
		if (empty($_ids)) { return $result; }

		$expires = (60 * 5); // 5 minutes
		$result = self::get_list(array(
            "key_name"=>self::$cache_key_prefix. "nmlkscts_".self::getListFilterKeyname($extras)."_".$offset,
            "expires"=>$expires,
             "what"=>array( // column list
                "num_likes, num_dislikes"
              ),
             "where"=>array(
                array(
                    "name"=>self::$primary_id,
                    "operator"=>"IN",
                    "value"=>$_ids
                ) 
            ),
            "alias"=>"p",
            "order_clause"=>self::_util_get_sort_by($extras->filter_proposaltype_id, $extras->sortby, $extras->sort_direction)
			)
        );

	

		if (is_array($result)){
			return $result;
		}

		return 0;

	}

	 

	private function _get_featured_ids_sql() {

		$proposal_cols = "talent_id, role_id, title_id, null as comment_talent_id, proposal_type ";

		$sql = "SELECT DISTINCT ".$proposal_cols.", p.user_id, p.proposal_id as id, 'proposal' as activity_type, null as 'type', 
				p.date_posted as timestamp, f.date_scheduled AS sortable_timestamp, p.anonymous 
				FROM proposals p
				INNER JOIN featured_proposals f ON p.proposal_id = f.proposal_id
				WHERE f.date_scheduled <= CURRENT_TIMESTAMP() 
				ORDER BY f.date_scheduled DESC";

		return $sql;
	} 

	public function get_featured_activity_items() {
		
		$sql = self::_get_featured_ids_sql();

		/******CACHING *******/
		
		$key_name = self::$cache_key_prefix."featured_allids";		

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires); 

		 } catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			if(DEBUGGING) print $e->getMessage();
        	throw $e;
			return array();	
    	}

		if (is_array($result)) {
			self::$total_count = count($result);
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return array();	
		}
	}
 
 	private function _get_ids_sql($options = array() ) {

		$orderclause = self::_get_order_clause($options);
		$limitclause = self::_get_limit_clause($options);
		$limitclause = ((trim($limitclause) == "") ? " " : $limitclause );
		$proposal_cols = "DISTINCT talent_id, role_id, title_id, null as comment_talent_id, proposal_type, p.user_id, p.proposal_id as id, 'proposal' as activity_type, null as 'type', 
				p.date_posted as timestamp, p.anonymous ";		
		$whatclause = $proposal_cols;
		if(isset($options['what'])){
			$whatclause = self::_get_what_clause($options);
		}

		$sql = "SELECT ".$whatclause."
				FROM proposals p
				$orderclause $limitclause ";


		return $sql;
	} 

	public function get_activity_items($extras = NULL) {
 		global $item_type_ids;

      	$user_category = $page_user_likes_proposals = "";

		$condition = self::_util_get_condition($extras->filter_category_id, $extras->filter_proposaltype_id, $page_user_likes_proposals, Auth::get_logged_in_user_id(), $user_category, $extras->filter_keyword);		

		if ($extras->filter_proposaltype_id == "casting"){
			$roles_join = "LEFT JOIN roles r ON r.role_id = p.role_id";
			$title_join = "ti.title_id = r.primary_title_id ";
		} else{
			$roles_join = "";
			$title_join = "ti.title_id = p.title_id ";
		}
		
		/*
		This query gets all of the proposal IDs
		*/
		$nocache = ((DEBUGGING)?"SQL_NO_CACHE":"");						
		$sql = "SELECT proposal_id
								FROM proposals p
								LEFT JOIN talent t ON t.talent_id = p.talent_id "
								.$roles_join.
								" LEFT JOIN identities i ON i.identity_id = p.crew_position_id  
								LEFT JOIN titles ti ON ".$title_join."
								WHERE p.proposal_id <> '' AND p.crew_position_id <> '4'" .$condition. "  
								ORDER BY " . self::_util_get_sort_by($extras->filter_proposaltype_id, $extras->sortby, $extras->sort_direction);

		
		#error_log("MIGRATION:  $sql");
		#error_log("**** **** **** **** **** **** **** **** **** ");

		/******CACHING *******/
		if (isset($extras)) {
			$key_name = self::$cache_key_prefix."allids_".self::getListFilterKeyname($extras);
		}
		else {
			$key_name = self::$cache_key_prefix."allidsbynum_likes";	
		}

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires); 

		 } catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			if(DEBUGGING) print $e->getMessage();
        	throw $e;
			return array();	
    	}

		if (is_array($result)) {
			self::$total_count = count($result);
			#print self::$total_count;
			#exit;
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return array();	
		}
 	}	
	
 	private function _util_get_sort_by($proposal_type, $sortby, $sort_direction){ 
		#error_log("MIGRATION:  $proposal_type, $sortby, $sort_direction");

		if ($sortby == "" || $sortby == "num_likes") { $sortby = "p.num_likes"; }
		return $sortby. " " . $sort_direction . ", p.num_dislikes, p.proposal_type='".$proposal_type."' DESC";
	}
	
	private function _util_get_condition($category_id, $proposal_type, $page_user_likes_proposals, $user_id=null, $user_category=null, $keyword=""){

		$condition = "";

		#print "$category_id // $proposal_type // $page_user_likes_proposals".PHP_EOL;

		if ($category_id != "" && $category_id != '0') { // NOT all-proposals
			$category_ids = explode("|", $category_id);
			$role_cat_id = "";
			$title_cat_id = "";
			
			for ($i = 0; $i < count($category_ids); $i++) {
				$first_ids_arr = explode(",", $category_ids[$i]);
				$first_id = $first_ids_arr[0];
				$second_ids_arr = explode(",", $category_ids[$i]);
				$second_id = $second_ids_arr[1];
				$title_cat_id .= $first_id.'|';
				$role_cat_id .= $second_id.'|';
			}
			
			$role_cat_id = rtrim($role_cat_id, '|');
			$title_cat_id = rtrim($title_cat_id, '|'); 
			
			if($proposal_type == "casting"){
				$condition = "AND (r.category_id RLIKE '[[:<:]](".$role_cat_id.")[[:>:]]' OR
									ti.category_id RLIKE '[[:<:]](".$title_cat_id.")[[:>:]]')";
			}else{
				$condition = "AND ti.category_id RLIKE '[[:<:]](".$title_cat_id.")[[:>:]]' ";
			}
		}

		/////
		$title_join = " OR ti.title_id = p.title_id "; 
		if ($proposal_type != "" && $proposal_type != '0'){
			if (strpos($proposal_type, "casting") !== false) {
				$condition .= " AND p.proposal_type = 'casting'";
				$title_join = ""; 
			
			}
			else if (strpos($proposal_type, "crew") !== false) {
				$condition .= " AND p.proposal_type = 'crew'";	
				$title_join = " OR ti.title_id = p.title_id ";
			
			}
			else {
				$condition .= " AND i.identity_id RLIKE '[[:<:]](".$proposal_type.")[[:>:]]'";
				$title_join = ""; 
			}
			
		}
		
		 /////
		if ($user_id != "") {
			if ($user_category == "proposals") { //in proposals section of user profile
			
				if (Auth::get_logged_in_user_id() != $user_id) { 
				//if ($loggedin_user_id != $user_id) { 
				
					//user looking at another user's profile
					$condition .= " AND p.user_id = ".$user_id;
					
					if (Auth::user_is_admin() == false) {
						//if user is not an admin
						$condition .= " AND p.anonymous <> 'Y'";
					}
				}
				else { 
					//user looking at his own profile
					$condition .= " AND p.user_id = ".$user_id;
				}
			}
			else if ($user_category == "likes") { //in likes section of user profile
				//$condition .= " AND li.user_id = ".$user_id." AND p.user_id <> ".$user_id;
				
				$liked_proposals_str = !empty($page_user_likes_proposals) ? implode(",",$page_user_likes_proposals) : "''";
				$condition .= " AND p.proposal_id IN (".$liked_proposals_str.") AND p.user_id <> ".$user_id;
				
			}
		}
		/////


		if ($keyword != "") {
			//print "KEYWORD: $keyword";
			$keyword = strtolower($keyword);
			

			if ($proposal_type == "casting") {

				$condition .= " AND (LOWER(r.name) LIKE '%".$keyword."%' OR
								LOWER(ti.name) LIKE '%".$keyword."%' OR
								LOWER(CONCAT(t.first_name,' ',t.last_name)) LIKE '%".$keyword."%')";
			}
			else {
				$condition .= " AND (LOWER(ti.name) LIKE '%".$keyword."%' OR
								LOWER(CONCAT(t.first_name,' ',t.last_name)) LIKE '%".$keyword."%')";
			}
		}

		return $condition;

	}

	 
	private function getListFilterKeyname($extras) {

		$append_to_keyname = $extras->filter_proposaltype_id.$extras->filter_category_id.$extras->filter_keyword.$extras->sortby.$extras->sort_direction;
		
		return clean_for_url($append_to_keyname);
	}




	/*
		required by interface
		to be used to attach secondary and associated data
	*/
	protected function _has_relationships(){
		//self::$has_many ;
	} 

	/*
	this needs to match the schema
	*/
	protected function _get_filtered_values($pairs){

		$filter = array( );
		$arr = filter_var_array($pairs);//, $filter);
		
		/*
			filter out blank values
		*/
		$arr = array_filter($arr, function ($item) use (&$arr) {
		    if($arr[key($arr)] == ""){ next($arr); return false; }
		    next($arr);
		    return true;
		});

    	return $arr;

	}
   

}
