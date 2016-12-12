<?php
namespace app\Models;
use StdCLass;
use Exception;

class Sort_options extends BaseModel{


	protected static $table = 'sort_options';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'sort_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'sorto_';


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
			$inst = new Sort_Options();
		}
		return $inst;
	}

	 
	public static function get_result($_type_id, $intent = "", $type = null, $options = array()){		 
	
		return $rows; 

	}

	public static function get_sort_options($page) {

		$sql = "SELECT * FROM sort_options WHERE page = '".$page."' ORDER BY sort_id";
		/******CACHING *******/
		$key_name = "sortoptions_".$page;		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 15); // 15 minutes

		$result = self::get_list(
			array(
				"key_name"=>$key_name,
				"expires"=>$expires,
				"where"=>array(
					array(
	                    "name"=>"page",
	                    "operator"=>"=",
	                    "value"=>$page
                	)
				),
				"order"=>array(
					array("name"=>"sort_id",
					"direction"=>"ASC")
				)
			)	
		);

		if (is_array($result)) {
 			$sort_options = array();
			$sort_options['ids'] = array();
			$sort_options['names'] = array();
			$sort_options['data_names'] = array();
			$sort_options['directions'] = array();
			
			foreach($result as $row){
				array_push($sort_options['ids'], $row['sort_id']);
				array_push($sort_options['names'], $row['sort_name']);
				array_push($sort_options['data_names'], $row['data_name']);
				array_push($sort_options['directions'], $row['sort_direction']);
			}
		}
		else {

			print self::$myDBHandler->error(DEBUGGING);
			return null;
		}
		
		return $sort_options;
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
