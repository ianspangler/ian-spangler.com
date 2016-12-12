<?php

namespace app\Models;
use Exception;
use StdClass;

class Chats extends BaseModel{

	protected static $table = 'users_chats';
    // allows us to use a different database for this model
    protected static $use_db = CHAT_DB;//'IflistMessages';

   	public static $set_size = 100;
	/*
		only these columns will be pulled from user table
	*/	
	protected static $allowed_fields = array('chat_id', 'user_id_1', 'user_id_2', 'active' , 'created_at', 'updated_at' );
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'chat_id';
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
	protected static $cache_key_prefix = 'chats_';


	public function __construct($config){
		//
		parent::__construct();
		 
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Chats(array());
		}
		return $inst;
	}
	
	/*
		get a list of chats by user_id
	*/	
	public static function get_result($_user_id, $related = array()){
        // will switch DB if needed
        self::_use_alternate_db();
        $rows = array(); 

        $sql = "SELECT * FROM ".self::$table." uc 	
		inner JOIN messages m ON m.chat_id = uc.chat_id
		WHERE uc.user_id_1 = ".$_user_id." OR uc.user_id_2 = ".$_user_id." 
		GROUP BY uc.chat_id ORDER BY uc.updated_at DESC
		LIMIT ".self::set_size();

        /******CACHING *******/
        $key_name = self::$cache_key_prefix.'list_'.$_user_id;

        // pass expiration time (in seconds) for cache objects to expire
        $expires = (60 * 0.5); // 30 seconds

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
				// return an array
                array_push($rows, self::array_to_object( $row ));
			}
            self::$result = $rows;
			if(self::$result == null){ return null; }
			/*
			 	handle relationships
			*/
			 return self::$result = self::_get_with_related($related, self::$result);
		}else{
			print "\n !!".self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		return null;	
	}
	
	/*
		get a specific chats by user_id, recipient_id
	*/	
	public static function get_a_chat($_user_id, $recipient_id, $related = array()){
        // will switch DB if needed
        self::_use_alternate_db();
        $rows = array();


        $sql = "SELECT * FROM ".self::$table." WHERE 
        ( user_id_1 = ".$_user_id." AND user_id_2 = ".$recipient_id .") 
        OR 
        ( user_id_1 = ".$recipient_id." AND user_id_2 = ".$_user_id .")  "; 

        /******CACHING *******/
        $key_name = self::$cache_key_prefix."users_".$_user_id."_".$recipient_id;

        // pass expiration time (in seconds) for cache objects to expire
        $expires = (60 * 0.5); // 30 seconds

        try{
            $result = self::$myDBHandler->get($sql, $key_name, $expires);
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return null;
        }

		if (is_array($result)) { 
			foreach($result as $row){
				// return one item
                return self::array_to_object( $row );
			}
			if(self::$result == null){ return null; } 
		}else{
			print "\n !!".self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		return null;	
	} 

	/*
		get a specific chats by chat_id
	*/	
	public static function get_a_chat_by_id($_chat_id){
        // will switch DB if needed
        self::_use_alternate_db();
        $rows = array();

        $sql = "SELECT ".implode(",",self::$allowed_fields)." FROM ".self::$table." WHERE chat_id = ".$_chat_id ;
        //print $sql;

        /******CACHING *******/
        $key_name = self::$cache_key_prefix."chat_".$_chat_id;

        // pass expiration time (in seconds) for cache objects to expire
        $expires = (60 * 0.5); // 30 seconds

        try{
            $result = self::$myDBHandler->get($sql, $key_name, $expires);
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return null;
        }

		if (is_array($result)) { 
			foreach($result as $row){
				// return one item
                return self::array_to_object( $row );
			}
			if(self::$result == null){ return null; } 
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

		return null;	
	} 

	public static function create($user_id, $recipient_id){
		// will switch DB if needed
        self::_use_alternate_db();

        $now = getNow();
        // $user_id, $recipient_id,
        $created_at = $updated_at = $now;
        $active = 1;

        $sql = "INSERT INTO ".self::$table." (user_id_1, user_id_2, active, updated_at, created_at)
        VALUES ($user_id, $recipient_id, '$active', '$updated_at', '$created_at')";

        $result = false;

        try{
            $result = static::_handle_db_query($sql, array("alt_db"=>self::$use_db)); 
        } catch (Exception $e) {
            print self::$myDBHandler->error(DEBUGGING);
            if(DEBUGGING) print $e->getMessage();
            throw $e;
            return array();
        }

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
			'chat_id' => FILTER_VALIDATE_INT,
			'user_id_1' => FILTER_VALIDATE_INT,
			'user_id_2' => FILTER_VALIDATE_INT,	
			'created_at' => FILTER_SANITIZE_STRING,
			'updated_at' => FILTER_SANITIZE_STRING,
			'active' => FILTER_VALIDATE_INT,	// always $now = date('Y-m-d H:i:s'); 
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
		/*
		if(!in_array('last_updated', $cols) ){ // || ( in_array('last_updated', $vals) && $vals['last_updated'] == "")){
			$now = static::getNow();
			$cols[] = 'last_updated';
			$vals[] = $now;
			
			if(!$_id){
				$cols[] = 'date_posted';
				$vals[] = $now;
			}	
		}*/
		return array($cols, $vals);
	}

	/**
		sql for data
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

