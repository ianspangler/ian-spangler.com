<?php
namespace app\Models;
use StdCLass;
use Exception;

class Credits extends BaseModel{

	/*
	
	*/
	protected static $table = 'credits';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'credit_id';
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;

	private static $total_count;
	/*
	*/
	protected static $cache_key_prefix = 'credit_';

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
			$inst = new Credits();
		}
		return $inst;
	}

	/*

	*/
	public function get_result($_mixed, $intent = "", $type = null){		 
	
		$sql = "SELECT cr.credit_id, cr.credit_type,
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	 t.main_pic AS main_talent_pic,
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				mt.main_pic as movie_title_main_pic, mt.year_released, mt.title_id, mt.name as movie_title_name, 
				t_media.filename as media_talent_pic,
				i.identity_name, i.identity_type, i.identity_label				 
				FROM credits cr 
				LEFT JOIN talent t ON cr.talent_id   	= t.talent_id
				LEFT JOIN roles r ON cr.role_id   		= r.role_id
				LEFT JOIN movie_titles mt ON cr.title_id    = mt.title_id
				LEFT JOIN media t_media ON cr.talent_pic_id = t_media.media_id 
				LEFT JOIN identities i ON cr.crew_position_id = i.identity_id 				 

				WHERE credit_id = ".$_mixed;

				#print $sql;
	 	$result = null;
		try{
			$result = self::_handle_db_query($sql, array("key_name"=>static::$table."_getresult") );
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
            return false;
		}	
		return $result; 
		
	}

	public function get_items($credit_type = "casting") {

		$result = self::get_item_ids($credit_type);

		/*print __FUNCTION__.PHP_EOL;
		print_r($result);*/
		return $result; 

	} 

	public function get_featured_items($credit_type = "casting") {

		$result = self::get_featured_item_ids($credit_type);

		/*print __FUNCTION__.PHP_EOL;
		print_r($result);*/
		return $result; 
	}

	/*
		get all the ids for a type, ordered
	*/
	public function get_item_ids($credit_type = "casting"){
		global $item_type_ids;
		$expires = (60 * 10); // 10 minutes
		$result = self::get_list(array(
            "key_name"=>self::$cache_key_prefix.$credit_type."_ids",
            "expires"=>$expires,
             
             "what"=>array(
             	'\''.$item_type_ids['credit'].'\' AS role_id',  
             	'user_id', 
             	'credit_id as id', 
             	'\'credit\' as activity_type', 
             	'\'null\' as type', 
				'date_posted as timestamp',
             	'num_likes',
             	'num_dislikes',
	         	'date_posted as sortable_timestamp'
             ),
             "where"=>array(
                array(
                    "name"=>"credit_type",
                    "operator"=>"=",
                    "value"=>$credit_type
                ) 
            ),
             "order"=>array(
             	array('name'=>'num_likes', 'direction'=>'DESC' ),
             	array('name'=>'num_dislikes', 'direction'=>'ASC' )
             )
           ) 
        );
        						
		if (is_array($result)) {
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
	}
	
		
	public function get_featured_item_ids(){

		global $dbHandler, $item_type_ids;

		$expires = (60 * 10); // 10 minutes
		$key_name = self::$cache_key_prefix."featured_ids";
		///$credit_cols = "talent_id, role_id, title_id, null as comment_talent_id, proposal_type ";

		$sql = "SELECT DISTINCT '".$item_type_ids['credit']."' AS 'role_id', cr.user_id, cr.credit_id as id, 'credit' as activity_type, null as 'type', 
				cr.date_posted as timestamp, cr.num_likes, cr.num_dislikes, f.date_scheduled AS sortable_timestamp
				FROM credits cr
				INNER JOIN featured_credits f ON cr.credit_id = f.credit_id
				WHERE f.date_scheduled <= CURRENT_TIMESTAMP() 
				ORDER BY f.date_scheduled DESC";

		$result = $dbHandler->get($sql, $key_name, $expires);
		
		if (is_array($result)) {
			self::$total_count = count($result);
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}	
	}
  
	public static function get_credit_detail_by_ids_sql($ids){

		return "SELECT cr.credit_id, cr.credit_id as id, cr.credit_type, cr.num_likes, cr.num_dislikes,
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	 t.main_pic AS main_talent_pic,
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				mt.main_pic as movie_title_main_pic, mt.year_released, mt.title_id, mt.name as movie_title_name, 
				t_media.filename as media_talent_pic, pf.format_name,
				i.identity_name, i.identity_type, i.identity_label,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic
			
				FROM credits cr  
				LEFT JOIN talent t ON cr.talent_id   	= t.talent_id
				LEFT JOIN roles r ON cr.role_id   		= r.role_id
				LEFT JOIN movie_titles mt ON cr.title_id    = mt.title_id
				LEFT JOIN media t_media ON cr.talent_pic_id = t_media.media_id 
				LEFT JOIN identities i ON cr.crew_position_id = i.identity_id 
				LEFT JOIN proposed_formats pf ON pf.format_id = mt.format_id
				LEFT JOIN users u ON cr.user_id = u.user_id

				WHERE cr.credit_id IN(".$ids.") ";
				//cr.num_likes - cr.num_dislikes AS popularity
				//ORDER BY popularity DESC";

	}

	public static function get_like_credit_detail_by_ids_sql($ids){

		return "SELECT like_id as 'id',  cr.credit_id , cr.credit_type,
				t.first_name as talent_first_name, t.last_name as talent_last_name, t.url_handle as talent_url_handle, t.talent_id,	 t.main_pic AS main_talent_pic,
				r.name as role_name, r.url_handle as role_url_handle , r.role_id, r.main_pic as main_role_pic,
				mt.main_pic as movie_title_main_pic, mt.year_released, mt.title_id, mt.name as movie_title_name, 
				t_media.filename as media_talent_pic,
				i.identity_name, i.identity_type, i.identity_label,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic 
				FROM likes 
				LEFT JOIN credits cr ON likes.item_id    = cr.credit_id
				LEFT JOIN talent t ON cr.talent_id   	= t.talent_id
				LEFT JOIN roles r ON cr.role_id   		= r.role_id
				LEFT JOIN movie_titles mt ON cr.title_id    = mt.title_id
				LEFT JOIN media t_media ON cr.talent_pic_id = t_media.media_id 
				LEFT JOIN identities i ON cr.crew_position_id = i.identity_id 
				LEFT JOIN users u ON likes.user_id = u.user_id

				WHERE like_id IN(".$ids.") GROUP BY like_id";

	}
	
	public static function get_count($credit_type, $options = array() ){ 
		// $credit_type = acting or filmmaker
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 30); // .5 hour
		$result = self::get_list(array(
            "key_name"=>self::$cache_key_prefix."count_".$credit_type,
            "expires"=>$expires,
             "what"=>array( // column list
                "COUNT(".self::$primary_id.") AS total"
              ),
             "where"=>array(
                array(
                    "name"=>"credit_type",
                    "operator"=>"=",
                    "value"=>$credit_type
                ) 
            )
           ) 
        );

		if (is_array($result)){
			return $result[0]['total'];
		}
		return 0;
	}

	public static function get_featured_count() {
		return self::$total_count;
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
