<?php
namespace app\Models;
use StdCLass;
use Exception;

class Categories extends BaseModel{


	protected static $table = 'categories';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'category_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'category_';


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
			$inst = new Categories();
		}
		return $inst;
	}

	 
	public static function get_result($_type_id, $intent = "", $type = null, $options = array()){		 
	
		 

		return $rows; 

	}

	public static function get_categories($page) {

		//$sql = "SELECT * FROM categories WHERE page = '".$page."' AND category_name <> 'Other' ORDER BY category_id";
		 
		/******CACHING *******/
		$key_name = "proposals_categories_".$page;		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 10); // 10 minutes 
		
		$result = self::get_list(
			array(
				"key_name"=>$key_name,
				"expires"=>$expires,
				"where"=>array(
					array(
	                    "name"=>"page",
	                    "operator"=>"=",
	                    "value"=>"proposals"
                	),
                	array(
	                    "name"=>"category_name",
	                    "operator"=>"<>",
	                    "value"=>"Other"
                	)
				),
				"order"=>array(
					array("name"=>"category_id",
					"direction"=>"ASC")
				)
			)	
		);

		if (is_array($result)) {

			$categories = array();
			$categories['names'] = array();
			$categories['full_names'] = array();
			$categories['data_names'] = array();
			
			$categories['ids'][0] = '0';
			$categories['names'][0] = "All Sources";
			$categories['full_names'][0] = "Proposals for Film & TV";
			$categories['data_names'][0] = "all-sources";
			
			foreach($result as $row){

				array_push($categories['ids'], $row['map_to_id']); //MAP TO ROLE CATEGORY ID's
				array_push($categories['names'], $row['category_name']);
				array_push($categories['full_names'], $row['full_name']);
				array_push($categories['data_names'], $row['data_name']);
			}	
		}
		else {

			print self::$myDBHandler->error(DEBUGGING);
			return null;
		}
		
		return $categories;
	}
	 

	public static function get_count($_id, $item_type, $options = array() ){ 

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
