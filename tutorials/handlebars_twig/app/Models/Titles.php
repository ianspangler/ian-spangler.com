<?php
namespace app\Models;
use StdCLass;
use Exception;

class Titles extends BaseModel{


	protected static $table = 'titles';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'title_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'title_';


	public function __construct($config = null){

		if(is_array($config) && isset($config['set_size'])){
			self::set_size($config['set_size']);			
		}
		//
		parent::__construct();
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Titles();
		}
		return $inst;
	}

	public function get_titles_count() {
		if(self::$total_count == null){
			throw new Exception(__METHOD__." total_count is NULL "); 
			return null;
		}
		return self::$total_count;
	}


	
	public function get_result($_mixed, $intent = "", $type = null){		 
	
	 	return false;

	}

	/* 
		get details for the title object, with related data
	*/

	public static function get_item_detail_by_ids_sql($ids = ""){

		return "SELECT ti.title_id as 'id', ti.name, ti.source_format_id, ti.url_handle, 
				ti.genre_id, st.group_id, ti.status_id, 
				ti.plot_summary, ti.author, ti.publisher, ti.main_pic, ti.main_pic_geom, ti.date_posted,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic,    
				GROUP_CONCAT(DISTINCT sf.format_name SEPARATOR ', ') AS source_formats, 
				GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', ') AS genres, 
				GROUP_CONCAT(DISTINCT g.genre_id SEPARATOR ', ') AS genre_ids,
				ti.title_image, ti.num_likes, ti.show_buylink
				
				FROM ". self::$table ." ti  
				LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id 
				LEFT JOIN genres g ON FIND_IN_SET(g.genre_id, ti.genre_id) 
				LEFT JOIN statuses st ON st.status_id = ti.status_id 
				LEFT JOIN users u ON ti.user_id = u.user_id 

				WHERE ti.title_id IN (".$ids.") GROUP BY ti.title_id";
	}


	private function _get_featured_ids_sql() {

		$sql = "SELECT '' AS 'role_id', ti.user_id, ti.title_id as id, 'title' as activity_type, null as 'type', 
				ti.date_posted as timestamp, f.date_scheduled AS sortable_timestamp 
				FROM titles ti
				INNER JOIN featured_stories f ON ti.title_id = f.title_id
				WHERE f.date_scheduled <= CURRENT_TIMESTAMP() 
				ORDER BY f.date_scheduled DESC";

				
		return $sql;
	} 


	//for regular stories page
	public function get_all_story_items() {

	}

	//for featured stories page
	public function get_featured_story_items() {
		
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
  	
  	public static function get_num_likes($_profile_id){
  		
  		//get supporters count
		$sql = "SELECT num_likes FROM titles WHERE title_id = ".$_profile_id." LIMIT 1";

		$key_name = "num_supporters_3_".$_profile_id;		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 30); // 30 minutes
		//$result = $dbHandler->get($sql, $key_name, $expires);

		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires); 

		 } catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			if(DEBUGGING) print $e->getMessage();
        	throw $e;
			return array();	
    	}

		if (is_array($result)) {
			foreach($result as $row) {	
				return $row['num_likes'];
			}
			return 0;	
		}else{
			showError(0, $dbHandler->error(DEBUGGING));
			exit;
		}

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
