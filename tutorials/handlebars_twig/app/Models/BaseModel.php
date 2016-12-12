<?php

namespace app\Models;
use StdClass;
use Exception;

use shared\Services\CacheClearer;

abstract class BaseModel implements ModelInterface{

	/* 
		give a default size for sets of returned data
	*/
	public static $set_size = 30;
	/* 
		give a default set number 
	*/
	public static $set_num = 1;

	public static $offset_increment = 0;

	public static $myDBHandler = null;

    protected static $use_db = null;

    protected static $cacheClearer = null;

	/* required items for all classes that inherit */
    abstract protected function _has_relationships(); 		// create a connection between models
    abstract protected function _get_filtered_values($pairs); // copy of db columns for the model

    // allow use of a different database for this model
    protected static function _use_alternate_db(){
        #print "     IN ".__METHOD__."\n";
        /*if(static::$use_db){
            #error_log( "(".get_called_class()."):  USING ALT : ".static::$use_db, 0);
            self::$myDBHandler->switch_db(static::$use_db);
            return static::$use_db;
        }*/
        return false;
    }

	// allow use of a different database for this model
    public static function get_alternate_db(){
        #print "     IN ".__METHOD__."\n";
        if(static::$use_db){ 
            return static::$use_db;
        }
        return false;
    }

	public function __construct(){
		global $dbHandler;
		if(!$dbHandler){ throw new Exception(__CLASS__." No DB Connection"); print "No DB Connection"; die; }	
		self::$myDBHandler = $dbHandler;

		self::$cacheClearer = CacheClearer::Instance();
	}	
	/*
		a getter/setter
		function($arg) sets the value
		function() gets the value
	*/
	public static function set_size($value = null){
		if(!isset(static::$set_size)){
			static::$set_size = $value;
		}
		return static::$set_size;
	}

	/*
		a getter/setter
		function($arg) sets the value
		function() gets the value
	*/
	public static function set_num($val = null){
		if($val != null){
			self::$set_num = $val;
		}
		return self::$set_num;
	} 

	/*
		a getter/setter
		function($arg) sets the value
		function() gets the value
	*/
	public static function set_offset_increment($val = null){
		if($val != null){
			self::$offset_increment = $val;
		}
		return self::$offset_increment;
	} 
 
    /*
	    generic get function 
	*/
    public static function get($primary_key_value, $options = array()){
    	
    	if((int)$primary_key_value < 1){
    		throw new Exception(__CLASS__.':'.__FUNCTION__ . " (4) Error Processing Request(value ".(int)$primary_key_value." is not valid)", 1);
    		return false;
    	}
    	// updated to allow for different columns to be used
    	$whatclause = " * ";
    	if(isset($options["what"])){
    		$whatclause = implode(",", $options["what"]);
    	}	

    	$sql = "SELECT ".$whatclause." FROM ".static::$table. " WHERE ".static::$primary_id."=".(int)$primary_key_value." LIMIT 1"; 
    	
    	#print PHP_EOL."SQL $sql".PHP_EOL;
		
		try{
			$result = static::_handle_db_query(
				$sql, 
				array( 
					static::$primary_id => (int)$primary_key_value, 
					"alt_db"=>@$options['alt_db'], 
					"key_name"=>@$options['key_name'] ) 
				);

		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage();
            error_log(	__FUNCTION__ . " ERROR ".$e->getMessage());
            return false;
		}
		return $result;
    }

    /**
	    a very configurable method of getting a list of results.
	    see ChatProfile for an example
	    sample:
	    get_list(
	    	array(
		    	"what"=>array(),
		    	"order"=>array(),
		    	"where"=>array(),
		    	"expires"=>array(),
		    	"key_name"=>array(),
		    	"alt_db"=>array(),
		    	"limit"=>array(),
		    	"offset"=>array(),
		    	"group"=>array()
	    	)
	    )
	*/
    public static function get_list($options = array()){
   
    	$uid = __FUNCTION__;

    	$whatclause = self::_get_what_clause($options); 

    	list($whereclause, $uid) = self::_get_where_clause($options, $uid);

    	$orderclause = self::_get_order_clause($options); 

    	$limitclause = self::_get_limit_clause($options); 

    	$offsetclause = self::_get_offset_clause($options);
    	
    	$groupbyclause = self::_get_groupby_clause($options);

    	$alias = (isset($options["alias"])? " ".$options['alias'] : "");

    	$sql = "SELECT ".$whatclause." FROM ".static::$table. $alias ." ". $whereclause . $groupbyclause . $orderclause . $limitclause . $offsetclause; 		
   
   		///print($sql);
	
   
		try {
			$result = static::_handle_db_query($sql, array( 
				static::$primary_id => $uid, 
				"key_name"=>@$options['key_name'] , 
				"expires"=>@$options['expires'] , 
				"alt_db"=>@$options['alt_db'] 
				) );


		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage();
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage());
            return false;
		} 
		return $result;
    }

    /*
	    generic insert function 
    	@param pairs is key, $value pairs of valid columns like
    	array(
    		'last_loggedin'=>$lastloggedin_date,
			'is_admin'=>'etc' ...
    		)
    	returns the row object	
    */	
    public static function insert($pairs, $options = array()){
 
    	$pairs = array_map(function ($str){ 
			return self::$myDBHandler->real_escape_string((string)$str);
		}, $pairs);
    	// not sure wy this is here
    	if(!array_key_exists('active', $pairs)){ $pairs['active'] = '1';}

    	// update / add the posted and updated times
		list($keys, $values) = static::_build_timestamps(array_keys($pairs), array_values($pairs), null);  
	
	 	$class = get_called_class();
        $inst = $class::Instance();

		// filter for accepted columns only
		$pairs = $inst->_get_filtered_values( array_combine ( $keys , $values ) );  

    	$keys = "`".implode("`,`",array_keys($pairs))."`";
    	$values = "'".implode("','",array_values($pairs))."'";

		$sql = "INSERT INTO ".static::$table. " (".$keys.") VALUES(".$values.")"; 
    	    	
    	try{
			$result = static::_handle_db_query($sql, array("alt_db"=>@$options['alt_db'] ));
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage();
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage());
            return false;
		}
		
		return $result;

    }

    /*
    generic update function
    	@param pairs is key, $value pairs of valid columns like
    	array(
    		'last_loggedin'=>$lastloggedin_date,
			'is_admin'=>'etc' ...
    		)
    	returns the result object	
    */	
    public static function update($primary_identifier, $pairs){

    	$key = array_keys($primary_identifier)[0];
    	$value = array_values($primary_identifier)[0];
    	
    	if(!isset($key) || (int)$value < 1){
			error_log(__CLASS__.':'.__FUNCTION__.': ERROR where values are empty');
    		return false;
    	}
 
    	$pairs = array_map(function ($str){
			return self::$myDBHandler->real_escape_string((string)$str);
		}, $pairs);

		list($keys, $values) = static::_build_timestamps(array_keys($pairs), array_values($pairs), $value);  
 
		$pairs = array_combine( $keys, $values);

    	$sql = "UPDATE ".static::$table. " SET ".self::_build_name_values($pairs, 'update')." WHERE `".$key."`='".$value."' LIMIT 1"; 

		try{
			$result = static::_handle_db_query($sql);
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage();
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage());
            return false;
		}	
		return $result;
 
    }


    /*
    generic delete function
    	 
    	@param pairs is key, $value pairs of valid columns like
    	array(
    		'last_loggedin'=>$lastloggedin_date,
			'is_admin'=>'etc' ...
    		)
    	returns the result object	
    */	
    public static function delete($pairs, $options = array()){
    	/*
            allow it to use an alt db
        
        if( isset($options['alt_db']) ){
            static::_use_alternate_db();
        }*/

        $pairs = array_map(function ($str){ 
			return self::$myDBHandler->real_escape_string($str);
		}, $pairs);


    	$sql = "DELETE FROM ".static::$table. " WHERE ".self::_build_name_values($pairs, 'delete')." "; 
    	//print $sql;
    	//return;

		try{
			$result = static::_handle_db_query($sql, array("alt_db"=>@$options['alt_db']) );
		} catch (Exception $e){
			if(DEBUGGING) print __FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true);
            error_log(			__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
            return false;
		}	
		return $result;
 
    }
	 
	public static function count($options){
    /*
	    generic delete function
	*/		

	} 

	/********************************/


	protected static function _get_what_clause($options){
		$whatclause = " * ";
		if(isset($options["what"])){
			$whatclause = implode(",", $options["what"]);
		}   
		return $whatclause;
	}

   	protected static function _get_where_clause($options, $uid){
	    $whereclause = "";
	    if(isset($options["where"])){
	        $whereclause = " WHERE ";
	        foreach($options["where"] as $w){
	            if(($w["operator"] == "IS") || ($w["operator"] == "IS NOT")){
	                $whereclause .= $w["name"]." ".$w["operator"] . " ". $w["value"] ." AND ";
	                continue;
	            }

	            if(($w["operator"] == "IN") || ($w["operator"] == "NOT IN")){
	                $whereclause .= $w["name"]." ".$w["operator"]." (".implode(",",$w["value"]).") AND ";
	                continue;                   
	            }
	                
	            $whereclause .= $w["name"]." ". $w["operator"] ." '". $w["value"] ."' AND ";

	            $uid .= $w["name"].urlencode($w["value"]); 
	        }
	    }

	    $whereclause = preg_replace('/AND $/', '', $whereclause);
        return array($whereclause, $uid);
    }

   	protected static function _get_order_clause($options){
	    $orderclause = " ";

	    if (isset($options["order_clause"])) {
	    	$orderclause = " ORDER BY ".$options["order_clause"];
	    }
	    else if(isset($options["order"])){
	        $orderclause = " ORDER BY ";
	        foreach($options["order"] as $item){
	            $orderclause .= $item["name"] ." ".$item["direction"].",";
	        }   
	    }
	    $orderclause = preg_replace('/,$/', '', $orderclause);
        return $orderclause;
   	}

   	protected static function _get_limit_clause($options){
	    $limitclause = " ";
	    if(isset($options["limit"])){
	        $limitclause = " LIMIT ".$options["limit"] ." ";
	    }    
        return $limitclause;
    }

   	protected static function _get_offset_clause($options){
	    $offsetclause = "";
	    if(isset($options["offset"]) && isset($options["limit"])){
	        $offsetclause = " OFFSET ".$options["offset"] ." ";
	    }
        return $offsetclause;
    }

   	protected static function _get_groupby_clause($options){
	    $groupbyclause = " ";
	    if(isset($options["groupby"])){
	        $groupbyclause = " GROUP BY ".$options["groupby"];
	    }
        return $groupbyclause;
    }

	/********************************/


	/*
	 	ensures timestamps are in place for db
	*/
	protected static function _build_timestamps($cols, $vals, $_id){
		if(!in_array('last_updated', $cols) ){  
			$now = static::getNow();
			$cols[] = 'last_updated';
			$vals[] = $now;
			
			if(!$_id){
				$cols[] = 'date_posted';
				$vals[] = $now;
			}	
		}
		return array($cols, $vals);
	}

	/*
		this function should allow us to get multiple related models
		assumed the key passed in is the name of the table
		assumed the foreign key is the primary_key of this class (user_id for users)
	*/
	protected static function _get_with_related($related, $result){
		$class = get_called_class();
        $inst = $class::Instance();

        if( (count($related) > 0) && $inst->_has_relationships() ){
			
			foreach($related as $related_request){
				$related_model = static::word_to_model($related_request, true);
				$related_props = static::$related['has_many'][$related_request];
					$r = new $related_model();

				if(array_key_exists($related_request, static::$related['has_many'])){
	
					#print "A GOT a Related: ".print_r($r,true)." \n";
					#print "Primary id = ".$result->{static::$primary_id}."\n";

					$result->$related_request = $r->get_result($result->{static::$primary_id});

				}else if(array_key_exists($related_request, static::$related['has_one'])){

					#print "B GOT a Related: $r \n";
					$result->$related_request = $r->get_result($result->$primary_id);

				}
			}
		}
		return $result;
	}

	/* 
		all calls to dbhandler go through here
		returns mysqli result object
	*/
	protected static function _handle_db_query($sql, $options = array()){
		$result = null;
		
		// pass expiration time (in seconds) for cache objects to expire
		$expires = (60 * 1); // 1 minute
        if( isset($options['expires']) ){
            $expires = $options['expires'];
        }
        /*
            allow it to use an alt db
        */
        if( isset($options['alt_db']) ){
            static::_use_alternate_db();
        }

        ///print(static::$primary_id);
        //print("HELLO: ".@$options[static::$primary_id]);

		if( isset($options[static::$primary_id])){ // select statements

			/******CACHING *******/
			$key_name = static::$cache_key_prefix.@$options['key_name'].$options[static::$primary_id];
			//print($options[static::$primary_id]."<br><br>");
			//exit;

			//print $key_name.PHP_EOL;
			//error_log("===Setting Key Name ".$key_name ,0);

			$result = self::$myDBHandler->get($sql, $key_name, $expires);			

			if (!is_array($result)) { 
				$err = 'ERROR Result is not an array '.print_r($result, true);
				error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
	    		throw new Exception(__CLASS__.':'.__FUNCTION__ . " (1) Error Processing Request ($err. SQL: $sql)", 1);
				return false;	
			}

			return $result; 

		}else{ // update, delete, insert 
			/*
		        allow it to use an alt db
		    */
		    if( isset($options['alt_db']) ){
		        static::_use_alternate_db();
		    }
			if ($result = self::$myDBHandler->query($sql)) {
					
				// return the last row added, updated
				if( self::query_type($sql, 'DELETE') || self::query_type($sql, 'SELECT' ) ){  
					// do nothing, return below

				}else if( self::query_type($sql, "INSERT") ){
					/*
				        allow it to use an alt db
				    */
				    if( isset($options['alt_db']) ){
				        static::_use_alternate_db();
				    }
        			
        			$my_id = self::$myDBHandler->insert_id();
        			
        			/*
			            allow it to use an alt db
			        */
			        if( isset($options['alt_db']) ){
			            static::_use_alternate_db();
			        }
					return self::array_to_object(static::get((int)$my_id)[0] );

				}else if( self::query_type($sql, "UPDATE") ){   

					return $result; 

				}else{ 
					error_log(__CLASS__.':'.__FUNCTION__.': '." (2) Error : Strange Case: ( SQL: $sql)");
				}
				return $result; 
			
			}else{
				$err = 'ERROR '.self::$myDBHandler->error(true);
				error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
				throw new Exception(__CLASS__.':'.__FUNCTION__ . " (3) Error Processing Request($err. SQL: $sql)", 1);

				print self::$myDBHandler->error(DEBUGGING);
				return false;
			}	
		}
		return false;
	}

	protected static function query_type($query, $type){
	    if ( preg_match("/^".$type."/", $query)) //stripos(substr($query,0, 20), $type))       // look only at beginning of string
	        return true;
	    return false;
	}
	/**
		returns a list of name value pairs formatted in one of two ways
    */
	private static function _build_name_values($pairs, $type){
		$delim = "AND ";
    	if($type == 'update'){ $delim = ", "; }
    	$req = '';
    	foreach ($pairs AS $col => $val){
      		$req .= "`$col`='$val' ".$delim;
    	}
    	$req = substr($req, 0, -strlen($delim));
    	return $req;
    }

	public static function array_to_object( $row ){
        if($row == null){ return null; }        
        $ob = new StdClass();
        foreach($row as $k=>$v){
            $ob->$k = $v;
        }
        return $ob;
    }

    public static function object_to_array( $data ) {  

        if (is_array($data) || is_object($data)){

	        $result = array();
	        foreach ($data as $key => $value)
	        {
	            $result[$key] = self::object_to_array($value);
	        }
	        return $result;
	    }
	   
	    return $data;
    }
    /*
    convert a string to a basemodel
    prepend the namespace
    */
    protected static function word_to_model($name, $prepend = false){
    	return ($prepend? __NAMESPACE__.'\\' : "") . ucfirst(strtolower($name)); 
    }
    /*
    this is a duplicate of a function in global.
	we can recitfy that , but not now
    */
    protected static function getNow(){
	    date_default_timezone_set("America/New_York");
		$date = new \DateTime(date('Y-m-d H:i:s'));
		return $date->format('Y-m-d H:i:s');
	  
	}

	

}


