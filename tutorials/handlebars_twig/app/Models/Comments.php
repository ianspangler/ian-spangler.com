<?php
namespace app\Models;
use StdCLass;
use Exception;

class Comments extends BaseModel{


	protected static $table = 'comments';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'comment_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'comment_';


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
			$inst = new Comments();
		}
		return $inst;
	}

	/*
	public function get_titles_count() {
		if(self::$total_count == null){
			throw new Exception(__METHOD__." total_count is NULL "); 
			return null;
		}
		return self::$total_count;
	}
	*/
	public static function get_result($_type_id, $intent = "", $type = null, $options = array()){		 
	
		$offsetclause = self::_get_offset_clause($options);
		$limitclause = self::_get_limit_clause($options);

	 	$sql = "SELECT  c.comment, c.datetime, c.anonymous, c.user_id,
						u.username, u.username as 'commenter_name', u.user_id, u.oauth_uid, u.main_pic, u.main_pic as 'user_pic',u.main_pic as 'commenter_pic'
						FROM ".self::$table." c
						LEFT JOIN users u ON u.user_id = c.user_id
						WHERE c.".$type."_id = ".$_type_id." ORDER BY datetime DESC ".$limitclause. $offsetclause;	
		//print $sql;


		/** CACHING **/
		$key_name = self::$cache_key_prefix."_items_".$type."_".$_type_id."_offset_".@$options['offset'];
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 30); // 30 minutes
		
		$rows = array();
		try{
			$result = self::_handle_db_query($sql, array("key_name"=>$key_name, "expires"=>$expires) );
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
            return false;
		}	
		
		while ($row = $result->fetch_assoc()) {
			$row['time_elapsed'] = getTimeElapsed(strtotime($row['datetime']))." ago";
			$row['comment'] = convert_to_link_in_text($row['comment']);
			array_push($rows, $row);
		}

		///print_r($rows);
		return $rows; 

	}

	/* 
		get details for the title object, with related data
	*/
	public static function get_endorsement_detail_by_ids_sql($ids = ""){

		return "SELECT c.".self::$primary_id." as 'id', c.comment, c.proposal_id, 
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	t.main_pic as main_talent_pic,			
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				ti.name as title_name, ti.url_handle as title_url_handle, ti.title_id, ti.main_pic as main_title_pic, ti.status_id, 
				i.identity_name, i.identity_type, i.identity_label, 
				sf.format_name AS source_format,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic

				FROM ".self::$table." c
				LEFT JOIN talent t ON c.talent_id = t.talent_id
				LEFT JOIN roles r ON c.role_id = r.role_id
				LEFT JOIN titles ti ON c.title_id = ti.title_id
				LEFT JOIN identities i ON FIND_IN_SET(i.identity_id, t.identities) 
				LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id
				LEFT JOIN users u ON c.user_id = u.user_id

				WHERE c.".self::$primary_id." IN(". $ids .") 
				AND c.anonymous <> 'Y'
                GROUP BY c.".self::$primary_id." "; 
	}  

	public static function get_comment_detail_by_ids_sql($ids = ""){
		return "SELECT c.".self::$primary_id." as 'id', c.comment, c.proposal_id, p.proposal_type, p.officially_cast, 
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	t.main_pic as main_talent_pic,			
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				ti.name as title_name, ti.url_handle as title_url_handle, ti.title_id, ti.main_pic as main_title_pic, ti.status_id, 
				t_media.filename as media_talent_pic, r_media.filename as media_role_pic,
				i.identity_name, i.identity_type, i.identity_label,
				mt.main_pic as movie_title_main_pic, mt.year_released, mt.title_id as movie_title_id, mt.name as movie_title_name,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic

				FROM ".self::$table." c
				LEFT JOIN proposals p ON c.proposal_id = p.proposal_id
				LEFT JOIN talent t ON p.talent_id = t.talent_id
				LEFT JOIN roles r ON p.role_id = r.role_id
				LEFT JOIN titles ti ON p.title_id = ti.title_id

				LEFT JOIN movie_titles mt ON p.title_id    = mt.title_id
				LEFT JOIN media r_media ON r_media.media_id = p.role_pic_id 
				LEFT JOIN media t_media ON t_media.media_id = p.talent_pic_id
				LEFT JOIN identities i ON p.crew_position_id = i.identity_id 
				LEFT JOIN users u ON c.user_id = u.user_id

				WHERE c.".self::$primary_id." IN(". $ids .") 
				AND c.anonymous <> 'Y'
                GROUP BY c.".self::$primary_id."  "; 
	}

	public static function get_comment_credit_detail_by_ids_sql($ids = ""){
		return "SELECT c.".self::$primary_id." as 'id', c.comment, c.proposal_id,  
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	t.main_pic as main_talent_pic,			
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				ti.name as title_name, ti.url_handle as title_url_handle, ti.title_id, ti.main_pic as main_title_pic,
				t_media.filename as media_talent_pic, r_media.filename as media_role_pic,
				i.identity_name, i.identity_type, i.identity_label,
				cr.credit_id , cr.credit_type,
				mt.main_pic as movie_title_main_pic, mt.year_released, mt.title_id, mt.name as movie_title_name,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic

				FROM ".self::$table." c
				LEFT JOIN credits cr ON c.credit_id = cr.credit_id
				LEFT JOIN talent t ON cr.talent_id = t.talent_id
				LEFT JOIN roles r ON cr.role_id = r.role_id
				LEFT JOIN titles ti ON cr.title_id = ti.title_id
				
				LEFT JOIN movie_titles mt ON cr.title_id    = mt.title_id
				LEFT JOIN media r_media ON r_media.media_id = r.role_id 
				LEFT JOIN media t_media ON t_media.media_id = cr.talent_pic_id
				LEFT JOIN identities i ON cr.crew_position_id = i.identity_id 
				LEFT JOIN users u ON c.user_id = u.user_id

				WHERE c.".self::$primary_id." IN (". $ids .") 
				AND c.anonymous <> 'Y'
                GROUP BY c.".self::$primary_id."  ";
	}

	public static function get_count($_id, $item_type, $options = array()){ 

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 30); // .5 hour
		$result = self::get_list(array(
            "key_name"=>@$options['key_name']."_".$_id,
            "expires"=>$expires,
             "what"=>array( // column list
                "COUNT(".self::$primary_id.") AS total"
              ),
             "where"=>array(
                array(
                    "name"=>$item_type."_id",
                    "operator"=>"=",
                    "value"=>$_id
                ) 
            )
           ) 
        );

		if (is_array($result)){
			return $result[0]['total'];
		}
		return 0;
	}
	
	public static function thread_is_deactivated($item_id, $item_type_id){
		return CommentsThreads::Instance()->is_deactivated($item_id, $item_type_id);			
	}

	/*
		
	*/
	public static function deactivate_thread($_item_id, $_item_type_id, $options = array()) {
		return CommentsThreads::Instance()->deactivate($_item_id, $_item_type_id);			
	}

	public static function activate_thread($_item_id, $_item_type_id, $options = array()) {
		return CommentsThreads::Instance()->activate($_item_id, $_item_type_id);			
	}
 
	public static function get_thread_list(){
		return CommentsThreads::Instance()->get_list(array(
            "key_name"=>'test', 
            "expires"=>1,
             "what"=>array( // column list
                "*"
              ),
             "limit"=>10
           ) 
        ); 
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
