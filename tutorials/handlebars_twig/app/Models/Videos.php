<?php
namespace app\Models;
use StdCLass;
use Exception;

class Videos extends BaseModel{


	protected static $table = 'videos';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'video_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'video_';


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
			$inst = new Videos();
		}
		return $inst;
	}

	 
	
	public static function get_result($_id, $options = array()){		 
		$videos = array();

		/******CACHING *******/
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 60); // 1 hour
		
		$key_name = self::$cache_key_prefix;
		if($options['key_name']){
			$key_name = self::$cache_key_prefix.$options['key_name']."_".$_id;
		}
		$result = self::get_list(array(
            "key_name"=>$key_name,
            "expires"=>$expires,
             "what"=>array( // column list
                "*"
              ),
             "where"=>array(
                array(
                    "name"=>"title_id",
                    "operator"=>"=",
                    "value"=>$_id
                ) 
            )
           ) 
        );

		if (is_array($result)){
			$videos['youtube_ids'] = array();
			foreach($result as $row){ 
				array_push($videos['youtube_ids'], $row['youtube_id']);
			}	
		} else {
			return $videos;
		}
		
		return $videos;

	}

	public static function get_count($_id){ 

		$key_name = self::$cache_key_prefix."_count_title_".$_id;		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 60); // 1 hour
		$result = self::get_list(array(
            "key_name"=>$key_name,
            "expires"=>$expires,
             "what"=>array( // column list
                "COUNT(video_id) AS total"
              ),
             "where"=>array(
                array(
                    "name"=>"title_id",
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
