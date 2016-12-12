<?php
namespace app\Models;
use StdCLass;
use Exception;

class Genres extends BaseModel{


	protected static $table = 'genres';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'genre_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'genre_';


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
			$inst = new Genres();
		}
		return $inst;
	}

	 
	
	public function get_result($include_all = false){

		$genres = array();
		/******CACHING *******/
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 15); // 15 minutes
		
		$result = self::get_list(array(
            "key_name"=>self::$cache_key_prefix,
            "expires"=>$expires,
            
             "what"=>array( // column list
                "*"
              ),
              "order"=>array( 
              	array('name'=>'menu_order', 'direction'=>'ASC' )
               )     
            ) 
        );

		//$sql = "SELECT * FROM genres ORDER BY menu_order";
		
		#$result = $dbHandler->get($sql, $key_name, $expires);

		if (is_array($result)) {
	
			$genres = array();
			$genres['ids'] = array();
			$genres['names'] = array();
			$genres['data_names'] = array();
			
			if ($include_all) {
				$genres['ids'][0] = "";
				$genres['names'][0] = "All Genres";
				$genres['data_names'][0] = "all-genres";
			}
			
			foreach($result as $row){
				array_push($genres['ids'], $row['genre_id']);
				array_push($genres['names'], $row['genre_name']);
				array_push($genres['data_names'], $row['data_name']);
			}	
		}
		else {
			return $genres;
		}
		
		return $genres;

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
