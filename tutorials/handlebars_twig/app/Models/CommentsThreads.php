<?php
namespace app\Models;
use StdCLass;
use Exception;


class CommentsThreads extends BaseModel{


	protected static $table = 'comments_threads';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'comments_threads_id';

	/* total count */
	protected static $total_count = null;

	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'comments_threads_';


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
			$inst = new CommentsThreads();
		}
		return $inst;
	}
	
	public static function get_result($_item_id, $_item_type_id, $options = array()){		 
	 	
 		$where = array(
            array(
                "name"=>"item_type_id",
                "operator"=>"=",
                "value"=>$_item_type_id
            ), 
            array(
                "name"=>"item_id",
                "operator"=>"=",
                "value"=>$_item_id
            ) 
         );   

	 	if(isset($options['where'])){
	 		$where = array_merge( $where, $options['where'] );
	 	} 

	 	return self::get_list(array(
            "key_name"=>@$options['key_name'],
            "expires"=>1,
             "what"=>array( // column list
                self::$primary_id
              ),
             "where"=>$where
           ) 
        );
	}

	/* 
		get details for the object, with related data
	*/	 

	public static function get_count($_item_id, $_item_type_id, $options = array() ){ 
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 30); // .5 hour
		$result = self::get_list(array(
            "key_name"=>$options['key_name'],
            "expires"=>$expires,
             "what"=>array( // column list
                "COUNT(".self::$primary_id.") AS total"
              ),
             "where"=>array(
                array(
                    "name"=>"item_type_id",
                    "operator"=>"=",
                    "value"=>$_item_type_id
                ), 
                array(
                    "name"=>"item_id",
                    "operator"=>"=",
                    "value"=>$_item_id
                ) 
            )
           ) 
        );

		if (is_array($result)){
			return $result[0]['total'];
		}
		return 0;
	}
	
	public static function is_deactivated($_item_id, $_item_type_id){
        // means the item is deactivated
	 	$options = array(
	 		"where"=>array(
	 			array(  
	            "name"=>"status",
	            "operator"=>"=",
	            "value"=>0
	            )
            )
        );

        $result = self::get_result($_item_id, $_item_type_id, $options);
        
        if(count($result) > 0){ 
			return true;
		}
		return false;	
	}

	/*
		
	*/
	public static function deactivate($_item_id, $_item_type_id, $options = array()) {

		// means the item is deactivated
	 	$options2 = array(
	 		"where"=>array(
	 			array(  
	            "name"=>"status",
	            "operator"=>"=",
	            "value"=>0
	            )
            )
        );

	 	$options = array_merge($options, $options2);

		$result = self::get_result($_item_id, $_item_type_id, $options);
        if(count($result) > 0){ 	return true;	 		}

		$status_value = 0;

		$now = getNow();
        // $user_id, $recipient_id,
        $created_at = $updated_at = $now;

        $sql = "INSERT INTO ".self::$table." (item_id, item_type_id, status, created_at, updated_at) 
		VALUES($_item_id, $_item_type_id, $status_value, '$created_at', '$updated_at') " ;

		$result = self::_handle_db_query($sql, array("alt_db"=>@$options['alt_db'] ) );

		if ($result) {

			self::$cacheClearer->clear_cache_for_thread_status($_item_id, $_item_type_id);

			return $result;
		}
		else {
			$err = 'ERROR '.self::$myDBHandler->error(true);
			error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
			throw new Exception(__CLASS__.':'.__FUNCTION__ . " (3) Error Un Blocking User($err)", 1);

			print self::$myDBHandler->error(DEBUGGING);
			return false;	
		}
		
	}

	public static function activate($_item_id, $_item_type_id, $options = array()) {		
        // if deactivated already!
		// means the item is deactivated
	 	$options2 = array(
	 		"where"=>array(
	 			array(  
	            "name"=>"status",
	            "operator"=>"=",
	            "value"=>0
	            )
            )
        );

	 	$options = array_merge($options, $options2);

        $result = self::get_result($_item_id, $_item_type_id, $options);
        
        if(count($result) > 0){
		}else{
			return true;
		}

		$result = self::delete(array("item_id"=>$_item_id, "item_type_id"=>$_item_type_id), array("alt_db"=>@$options['alt_db']) );
        
        self::$cacheClearer->clear_cache_for_thread_status($_item_id, $_item_type_id);
        
        return $result;        
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
