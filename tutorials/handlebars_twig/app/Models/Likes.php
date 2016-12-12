<?php
namespace app\Models;
use StdCLass;
use Exception;

class Likes extends BaseModel{

	protected static $table = 'likes';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'like_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'like_';


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
			$inst = new Likes();
		}
		return $inst;
	}

	public function get_likes_count() {
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
		get details for the object, with related data
	*/
	public static function get_like_profile_detail_by_ids_sql($ids = ""){

		return "SELECT like_id as 'id', 
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	 t.main_pic AS main_talent_pic,
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				ti.name as title_name, ti.url_handle as title_url_handle, ti.title_id, ti.main_pic as main_title_pic, ti.status_id, 
				cr.credit_id, cr.credit_type,
				i.identity_name, i.identity_type, i.identity_label, sf.format_name AS source_format,
				rt.name as role_title_name, rt.url_handle as role_title_url_handle, rt.title_id as role_title_id, rt.main_pic as role_title_pic,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic

				FROM ". self::$table ."
				
				LEFT JOIN talent t ON t.talent_id 		= likes.item_id   	
				LEFT JOIN roles r ON r.role_id			= likes.item_id  
				LEFT JOIN titles ti ON ti.title_id 		= likes.item_id   
				LEFT JOIN titles rt ON rt.title_id 		= r.primary_title_id  
				LEFT JOIN credits cr ON cr.credit_id 	= likes.item_id  
				LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id
				LEFT JOIN identities i ON FIND_IN_SET(i.identity_id, t.identities) 
				LEFT JOIN users u ON likes.user_id   	= u.user_id
 
				WHERE like_id IN(".$ids.") GROUP BY like_id";
	}  

	/* 
		get details for the object, with related data
	*/
	public static function get_like_detail_by_ids_sql($ids = ""){

		return "SELECT like_id as 'id', p.proposal_id, p.proposal_type, p.officially_cast, 
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	 t.main_pic AS main_talent_pic,
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				ti.name as title_name, ti.url_handle as title_url_handle, ti.title_id, ti.main_pic as main_title_pic, ti.status_id, 
				t_media.filename as media_talent_pic, r_media.filename as media_role_pic,
				i.identity_name, i.identity_type, i.identity_label 
				, sf.format_name AS source_format,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic
				FROM ". self::$table ."
				LEFT JOIN proposals p ON likes.item_id 		= p.proposal_id
				
				LEFT JOIN talent t ON p.talent_id   = t.talent_id
				LEFT JOIN roles r ON p.role_id   		= r.role_id
				LEFT JOIN titles ti ON p.title_id   = ti.title_id
				LEFT JOIN media t_media ON p.talent_pic_id = t_media.media_id
				LEFT JOIN media r_media ON p.role_pic_id 	= r_media.media_id
				LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id
				LEFT JOIN identities i ON p.crew_position_id = i.identity_id 
				LEFT JOIN users u ON likes.user_id = u.user_id

				WHERE like_id IN(".$ids.") GROUP BY like_id";
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
