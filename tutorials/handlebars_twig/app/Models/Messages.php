<?php

namespace app\Models;
use Exception;
use StdClass;


class Messages extends BaseModel{

	protected static $table = 'messages';
    // allows us to use a different database for this model
    protected static $use_db = CHAT_DB;

   	public static $set_size = 30;

	/*
		only these columns will be pulled from user table
	*/	
	protected static $allowed_fields = array('message_id', 'body', 'user_id', 'recipient_id', 'chat_id', 'created_at', 'updated_at', 'read_at');
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'message_id';
	
	/*
		relationships
	*/
	protected static $related = array(
		"has_many" => array(
			"null" => array()
			),
		"has_one" => array(
			"null" => array()
			)
		);

	/* 
		object to store
	*/	
	private static $result = null;
	/*
	*/
	protected static $cache_key_prefix = 'messages_';


	public function __construct($config){

		if (is_array($config) && isset($config['set_size'])) {

  			self::set_size($config['set_size']);			
    	}
		
		parent::__construct();
		 
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Messages(array());
		}
		return $inst;
	}
	
	/*
		get a user by id
	*/	
	public static function get_result($_chat_id, $related = array(), $options = array()){
        // will switch DB if needed
        self::_use_alternate_db();
        
        $rows = array();
        $offset = self::$set_size;
        $offset_increment = self::$offset_increment;
		$limit = static::set_size(); 

		// set the columns
        $whatclause = " * ";
    	if(isset($options["what"])){
    		$whatclause = implode(",", $options["what"]);
    	}

    	$message_ids = array();
    	//1 get all the ids in order first
    	$sql = "SELECT ".self::$primary_id." FROM ".self::$table."  WHERE chat_id = ".$_chat_id ." ORDER BY created_at DESC ";
        //print $sql.PHP_EOL;
        
        /******CACHING *******/
        $key_name = self::$cache_key_prefix."ids_".$_chat_id;
        // pass expiration time (in seconds) for cache objects to expire
        $expires = (1 * 1); // 10 seconds

        try{
            $result = self::$myDBHandler->get($sql, $key_name, $expires);
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return array();
        }
        if (is_array($result)) { 
			foreach($result as $row){
				array_push($message_ids, $row[self::$primary_id] ); 
			}
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		/*
			deal wth sets, offset
		*/
		if(isset($options['set_num'])  || ((int)static::set_num() > 1) ){
			#print "set_size: (".$options['set_num'].")".static::set_size() .",". static::set_num().PHP_EOL;
			$offset = (static::set_size() * (static::set_num()-1) ) + $offset_increment; 
        }
        // get selection we want
		$message_ids2 = array_slice( $message_ids , $offset, $limit);
		if(count($message_ids2) < 1){  return array();  }
		
		// will switch DB if needed
        self::_use_alternate_db();

		// now get the data using the small set of ids
        $sql = "SELECT ".$whatclause." , ( ". self::_get_count_sql($_chat_id)." ) as total_count 
        FROM ".self::$table." 
        WHERE ".self::$primary_id." IN (".implode(",",$message_ids2).")"; 
        
        //print $sql;
        /******CACHING *******/
        $key_name = self::$cache_key_prefix.$_chat_id;

        // pass expiration time (in seconds) for cache objects to expire
        $expires = (1 * 1); // 1 seconds

        try{
            $result = self::$myDBHandler->get($sql, $key_name, $expires);
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return array();
        }

		if (is_array($result)) { 
			foreach($result as $row){
				array_push($rows, self::array_to_object( $row )); 
			}

            self::$result = $rows;
			if(self::$result == null){ return null; }	
			/*
			 	handle relationships
			*/
			 return self::$result = self::_get_with_related($related, self::$result);
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		return null;	
	} 
	
	/*
		get total message count for a chat
	*/
	private static function _get_count_sql($_chat_id){
		return "SELECT count(".self::$primary_id.") 
			FROM ".self::$table." 
        	WHERE chat_id = ".$_chat_id ;
	}

	/* 
		get latest message from either user in a chat
	*/
	public static function get_latest_excerpt($_chat_ids){
		// will switch DB if needed
        self::_use_alternate_db();
        $rows = array();
        
		$sql = "SELECT chat_id, body, created_at, updated_at, user_id, recipient_id 
		FROM ".self::$table." 
		WHERE chat_id IN (".implode(",", $_chat_ids ).") 
		AND created_at IN ( 
				SELECT max(created_at) 
				FROM ".self::$table." 
				WHERE chat_id IN (".implode(",", $_chat_ids).") 
				GROUP BY chat_id
				)
		GROUP BY created_at DESC ";

        /******CACHING *******/
        $key_name = self::$cache_key_prefix."excerpt_".implode("", $_chat_ids );

        // pass expiration time (in seconds) for cache objects to expire
        $expires = (60 * 1); // 1 minute

        try{
            $result = self::$myDBHandler->get($sql, $key_name, $expires);
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return array();
        }

		if (is_array($result)) { 
		  	foreach($result as $row){
				array_push($rows, self::array_to_object( $row )); 
			}

			 return $rows ; 
		}else{  
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
		return null;	

	}

	/*
		update the read_at field to indicate the message has been read 
	*/
    public static function update_chat_messages($user_id, $chat_id){
    	// will switch DB if needed
        self::_use_alternate_db();

        $now = getNow();
        $sql = "UPDATE ".self::$table." SET read_at = '$now' WHERE recipient_id = $user_id and chat_id = $chat_id";
        
        $result = false;

        try{
            $result = self::_handle_db_query($sql, array("alt_db"=>self::$use_db)); 
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return array();
        }
        // clear cache
		self::$cacheClearer->clear_cache_for_messages_count($user_id);

        return $result;
    }

    public static function get_list_for_read_count($chat_ids){
    	return self::get_list(array(
            "alt_db" =>true,
            "where"=>array(
                array(
                    "name"=>"chat_id",
                    "operator"=>"IN",
                    "value"=>$chat_ids
                ) 
            ),
             "what"=>array(
                "count(chat_id) as message_count",
                "chat_id"
                ),
             
             "groupby"=>"chat_id"

            ) 
        );
    }	
    
    public static function get_list_for_unread_count($chat_ids, $_user_id){
    	
    	return self::get_list(array(
            "alt_db" =>true,
            "where"=>array(
                array(
                    "name"=>"chat_id",
                    "operator"=>"IN",
                    "value"=>$chat_ids
                ),
                array(
                    "name"=>"user_id",
                    "operator"=>"<>",
                    "value"=>$_user_id
                ),
                array(
                    "name"=>"read_at",
                    "operator"=>"IS",
                    "value"=>"NULL"
                )
            ),
             "what"=>array(
                "count(chat_id) as unread_count",
                "chat_id",
                "user_id",
                "read_at"
                ),
             
             "groupby"=>"chat_id"

            ) 
        );
    }

    public static function get_unread_count($user_id){

    	// will switch DB if needed
        self::_use_alternate_db();
        $sql = "SELECT count(".self::$primary_id.") as count 
        	FROM ".self::$table." 
        	WHERE recipient_id = ".$user_id . " 
        	AND read_at IS NULL ";

        
        /******CACHING *******/
        $key_name = self::$cache_key_prefix."unreadcount_".$user_id;


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
		  	foreach($result as $row){
				return $row['count']; 
			} 
			// clear cache
			self::$cacheClearer->clear_cache_for_messages_count($user_id);

			return $rows ; 
		}else{  
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		return null;	
 
    } 

    /* 
	    wrap the default insert function so we can also clear cache
    */
    public static function insert_a_message($fields, $options = array()){
    	$result = self::insert( 
            $fields,
            $options
        );
        
    	//// clear cache for recipient
        self::$cacheClearer->clear_cache_for_messages_count($fields['recipient_id']);
        
        return $result;

    }

	/*
		required by interface
		to be used to attach secondary and associated data
	*/
	protected function _has_relationships(){
		return (count(self::$related['has_many']) > 0 || count(self::$related['has_one']) > 0) ;
	} 
	/*
	this needs to match the schema
	*/
	protected function _get_filtered_values($pairs){ 

		$filter = array( 
			'message_id' => FILTER_VALIDATE_INT,
			'body' => FILTER_SANITIZE_STRING,
			'user_id' => FILTER_VALIDATE_INT,
			'recipient_id' => FILTER_VALIDATE_INT,	
			'chat_id' => FILTER_VALIDATE_INT,
			'created_at' => FILTER_SANITIZE_STRING,
			'updated_at' => FILTER_SANITIZE_STRING,
			'read_at' => FILTER_SANITIZE_STRING	// always $now = date('Y-m-d H:i:s'); 
		);
		$arr = filter_var_array($pairs, $filter);
		
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


	/*
	 	ensures timestamps are in place for db
	*/
	protected static function _build_timestamps($cols, $vals, $_id){
		if(!in_array('updated_at', $cols) ){  
			$now = static::getNow();
			$cols[] = 'updated_at';
			$vals[] = $now;
			
			if(!$_id){
				$cols[] = 'created_at';
				$vals[] = $now;
			}	
		}
		return array($cols, $vals);
	}
	
	/**
		sql for a data
	*/
	/*private static function _get_sql($_mixed, $options = null){
		$where_clause = " WHERE ";
		if(is_int( $_mixed )){
			$where_clause .= self::$table.".".self::$primary_id." = ". $_mixed;
		}else{
			return new StdClass();
		}
		 
 		$sql = "SELECT ".implode(", ", self::$allowed_fields)." FROM ".self::$table . $where_clause;
		 
		return $sql;			

	}*/



}

