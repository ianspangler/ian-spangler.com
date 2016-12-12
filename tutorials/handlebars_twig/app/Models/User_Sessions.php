<?php

namespace app\Models;
use Exception;
use app\Services\Router;

class User_Sessions extends BaseModel{
	/*

	*/
	protected static $table = 'user_sessions';
	const USERID_COLUMN  = 'user_id';
    const SESSION_COLUMN = 'session';
    const CREATED_COLUMN = 'created';
	/* 
		only these columns will be pulled from user table
	*/	
	private static $allowed_fields = array('id','follower_id', 'followed_id', 'datetime');
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'id';
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'user_sess_';
	

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
			$inst = new User_Sessions();
		}
		return $inst;
	}
	
	/*
		since $key might be based on the value in the cookie, it allows a user to 
		 be logged in from 2 places at once, or logged out from one and in from another
	*/
	
	public static function insert($pairs, $options = array()){
		
		$userid = self::$myDBHandler->real_escape_string($pairs['user_id']);
    	$key = self::$myDBHandler->real_escape_string($pairs['key']);
		$userid = (int)$userid;

        /*
        	// this would delete all with the id, but thats not what we want
        	self::deleteOldwithId($userid); 
		*/
        
        self::deleteOldwithKey($key);

        $sql = 'INSERT INTO ' . 
            self::$table . '(' .
            self::USERID_COLUMN . ', ' . 
            self::SESSION_COLUMN . ") VALUES ($userid, '$key')";
        
        #print $sql;

        if (!self::$myDBHandler->query($sql)){
            throw new Exception(__FUNCTION__ . " ERROR ".self::$myDBHandler->error(true));
            if(DEBUGGING) print __FUNCTION__ . " ERROR ".self::$myDBHandler->error(true);
            return false;
        }
        return true;

	}

	public static function get_result($_user_id, $sessionKey){
    	$_user_id = self::$myDBHandler->real_escape_string($_user_id);
    	$sessionKey = self::$myDBHandler->real_escape_string($sessionKey);

		// check if cookie row is found
        $sql = 'SELECT ' . self::USERID_COLUMN . 
                ' FROM ' . self::$table . ' WHERE 
                       ' . self::USERID_COLUMN . ' = '.$_user_id.' AND ' . 
                           self::SESSION_COLUMN . '= \''. $sessionKey.'\' LIMIT 1' ;
            
        if ($result = self::$myDBHandler->query($sql)) {
            return $_user_id;
        } else {
            if(DEBUGGING) print __FUNCTION__ . " ERROR ".self::$myDBHandler->error(true);
            error_log(			__FUNCTION__ . " ERROR ".self::$myDBHandler->error(true));
            return false;
        }
	}

	/*
		called everytime a mobile user loads a page
		added 4/29
	*/
	public static function update_last_active($_user_id){

        $is_mobile = (Router::device_is_mobile() ? 1 : 0);
        
		$now = getNow();
        $sql = "UPDATE ".self::$table." 
        	SET last_active = '$now', 
        	is_mobile = ".$is_mobile." 
        	WHERE user_id = $_user_id AND last_active <> '$now'";
        
        //print $sql;

        $result = false;

        try{
            $result = self::_handle_db_query($sql); 
        } catch (Exception $e) { 
            if(DEBUGGING) print PHP_EOL .__FUNCTION__. " :: " .$e->getMessage() . PHP_EOL. PHP_EOL;
            throw $e;
            return false;
        }

        return $result;
    }

    public static function get_last_active($_user_id){
    	$_user_id = self::$myDBHandler->real_escape_string($_user_id);
    	
    	/*
    		@TODO
    		figure out what to do when there are multiple rows in there
    	*/
    	$sql = 'SELECT last_active FROM ' . self::$table . ' WHERE 
                ' . self::USERID_COLUMN . ' = '.$_user_id.' LIMIT 1' ;
        
        
        $key_name = self::$cache_key_prefix."last_actv_".$_user_id;    
        #print $key_name.PHP_EOL;
        // pass expiration time (in seconds) for cache objects to expire 
        $expires = (60 * 1); // 1 minute
        $result = self::$myDBHandler->get($sql, $key_name, $expires);
                
        if (is_array($result)) { //if ($result = self::$myDBHandler->query($sql)) {
            foreach($result as $row){
            	#print "GOT ".$row['last_active'];
            	return $row['last_active'];
            }
        } else {
            if(DEBUGGING) print __FUNCTION__ . " ERROR ".self::$myDBHandler->error(true);
            error_log(			__FUNCTION__ . " ERROR ".self::$myDBHandler->error(true));
            return "";
        }
    }

    public static function deleteOldwithKey($key) {
    	$key = self::$myDBHandler->real_escape_string($key);
		#error_log('deleting row from db with session_column of '.$key);
        
        // Delete old sessions
        $sql = 'DELETE FROM ' . 
                    self::$table . ' WHERE 
                ' . self::SESSION_COLUMN . ' = \''.$key.'\' LIMIT 1';

         if ($result = self::$myDBHandler->query($sql)) {
            
            // print $sql;

            // print " result ";
            // print_r($result);
            
            // print " rows affected ".self::$myDBHandler->affected_rows();
            
            return true;
        } else {
            if(DEBUGGING) print __FUNCTION__ . " ERROR ".self::$myDBHandler->error(true);
            error_log(__FUNCTION__ . " ERROR ".self::$myDBHandler->error(true));
            return false;
        }
    }

    public static function deleteOldwithId($userid) {
    	$userid = self::$myDBHandler->real_escape_string($userid);

        //$db = self::getDb();
        // Delete old sessions
        $sql = 'DELETE FROM ' . 
                    self::$table . ' WHERE 
                ' . self::USERID_COLUMN . ' = '.$userid.' LIMIT 1';

         if ($result = self::$myDBHandler->query($sql)) {
            return true;
        } else {
            if(DEBUGGING) print __FUNCTION__ . " ERROR ".self::$myDBHandler->error(true);
            error_log(__FUNCTION__ . " ERROR ".self::$myDBHandler->error(true));
            return false;
        }
    }

    /**
     * Run this function on a cron to save user time. This will delete
     * any session that is older than the NUM_DAYS set above
     */
    public static function delete($mytimeout, $options = array()){
    	$mytimeout = self::$myDBHandler->real_escape_string($mytimeout);
    	$sql = 'DELETE FROM ' . 
                    self::$table . ' WHERE 
                ' . self::CREATED_COLUMN . '< \'' . $mytimeout . '\' LIMIT 1';

        if ($result = self::$myDBHandler->query($sql)) {
            return true;
        } else {
            if(DEBUGGING) print __FUNCTION__ . " ERROR " . self::$myDBHandler->error(true);
            error_log(__FUNCTION__ . " ERROR " . self::$myDBHandler->error(true));
            return false;
        }
    }

	public function get_count($user_id){ 

		if(isset(self::$result_num)){
			#print " A ";
			return self::$result_num;
		}else if(isset(self::$result)){
			#print " B ";
			self::$result_num = count(self::$result);
			return self::$result_num;
		}

		/******CACHING *******/
		$key_name = self::$cache_key_prefix."count_".$user_id;	
		$sql = "";
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		$result = self::$myDBHandler->get($sql, $key_name, $expires);
		
		if (is_array($result)) {
			self::$result_num = $result[0]['count'];
		 	return $result[0]['count'];
		}else{
			if(DEBUGGING) print __FUNCTION__ . " ERROR " . self::$myDBHandler->error(true);
            error_log(__FUNCTION__ . " ERROR " . self::$myDBHandler->error(true));
			return null;	
		} 
	}	

	private function _get_set($set_num = 1){		
		 return self::$result;
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

