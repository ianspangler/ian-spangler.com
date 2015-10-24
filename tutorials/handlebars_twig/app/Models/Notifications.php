<?php
namespace app\Models;
use StdCLass;
use Exception;

class Notifications extends BaseModel{

	/*
	
	*/
	protected static $table = 'notifications';
	/* 
		only these columns will be pulled from table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in table
	*/	
	protected static $primary_id = 'notification_id';
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'notification_';

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
			$inst = new Notifications();
		}
		return $inst;
	}

	/*
		gets all the rows for the user.

		if you want all the rows by something else, you can use self::get(primary_key_value)
	*/
	public function get_result($_mixed, $intent = "", $type = null){		 

		// NOT Using primary_id
		$sql = "SELECT * FROM ".self::$table." WHERE `user_id` = $_mixed ";

 		/******CACHING *******/
		
		$key_name = self::$cache_key_prefix.$intent.$_mixed.(($type)?"_".$type:"");		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		 } catch (Exception $e) {
			//print self::$myDBHandler->error(DEBUGGING);
			//if(DEBUGGING)print $e->getMessage();
        	$mesg =  $e->getMessage() . " // ".self::$myDBHandler->error(true);
			if(DEBUGGING)print $mesg;
			error_log($mesg);

			throw $e;
    	}

		if (is_array($result)) {
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
		
	}

	public function get_status($_mixed){
		$record = self::array_to_object( @self::get((int)$_mixed)[0] );
		return $record->active;
	} 

	/*
		gets the earliest day that things may be
	*/
	/*
	public function get_expiration_day(){
	    date_default_timezone_set("America/New_York");
		$date = new \DateTime('7 days ago');
		return $date->format('d');
	} 
	*/
	public function get_unread_message_count($_mixed){
		$sql = "SELECT count(action_id) as count, action_id, item_id, item_type_id, 
			DATE(date_posted) as day_posted  /* actual DAY the row was created */			
			FROM `".self::$table."` 
				WHERE user_id = ".(int)$_mixed ." 
				AND active = 1 
				AND viewed = 0 
				GROUP BY day_posted, action_id, item_id, item_type_id
				ORDER BY date_posted DESC";	
		
		//print $sql;
		//exit;

 		/******CACHING *******/	
		$key_name = self::$cache_key_prefix.$_mixed."_unreadcount";

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		 } catch (Exception $e) {
			$mesg =  $e->getMessage() . " // ".self::$myDBHandler->error(true);
			if(DEBUGGING)print $mesg;
			error_log($mesg);
        	throw $e;
        	return 0;
    	}
    	//print "get_unread_message_count = ".count($result);
    	//print_r($result) . PHP_EOL;
    	return @count($result);
	}	
	
	public function get_total_message_count($_mixed){

		$sql = "SELECT count(action_id) as count, action_id, item_id, item_type_id, 
			DATE(date_posted) as day_posted  /* actual DAY the row was created */			
			FROM `".self::$table."` 
				WHERE user_id = ".(int)$_mixed ."  
				GROUP BY day_posted, action_id, item_id, item_type_id
				ORDER BY date_posted DESC";	
		
		#print $sql;

 		/******CACHING *******/	
		$key_name = self::$cache_key_prefix.$_mixed."_count";		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		 } catch (Exception $e) {
			$mesg =  $e->getMessage() . " // ".self::$myDBHandler->error(true);
			if(DEBUGGING)print $mesg;
			error_log($mesg);
        	throw $e;
        	return 0;
    	}
    	#print " = ".count($result);
    	return @count($result);

	}
	
	public function get_message_data_ids($_mixed, $offset = 0,  $set_num = 0){
		$sliced = array();
		$result = null;

		// first get the ids ordered by date
		/******CACHING *******/	
		$key_name = self::$cache_key_prefix.$_mixed."_ids_".static::set_size()."_".$offset;			
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		/*
			TO DO : ADD A DATE LIMIT
		*/

		$sql = "SELECT ".self::$primary_id." FROM `".self::$table."` WHERE user_id = ".$_mixed." 
			ORDER BY date_posted DESC ";	
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		 } catch (Exception $e) {
			$mesg =  $e->getMessage() . " // ".self::$myDBHandler->error(true);
			if(DEBUGGING) print $mesg;
			error_log($mesg);
        	throw $e;
        	return array();
    	}
    		
		// then slice for the set
		if (is_array($result)) {
			$rows = array();
			foreach($result as $row){
				array_push($rows, $row[self::$primary_id]);
			}
			$sliced = @array_slice($rows, $offset , static::set_size());	
		}	

		return $sliced;
		
	}
	/*
		this is where we get the details for notifications
		query the table, grouping by " day_posted, action_id, item_id, item_type_id "
		this creates a 'unique' constraint, the groups all same actions on same items/item types by day
	*/
	public function get_message_data($_mixed, $set_num = 0){
		// get everything in the table for the user
		$data = array();		 
		$offset =0;
		
		if((int)static::set_num() > 1){
			$offset = (static::set_size() * (static::set_num() -1));
		}
		
		/*
			Cant slice the ids before we aggregate the results
			$sliced = self::get_message_data_ids($_mixed, $offset, $set_num);
			if(count($sliced)<1){ 	return array(); 		}
		*/

		/*
			get all rows and the day they were posted, grouped by their unique combination
		*/
		$sql = "SELECT ".self::$primary_id.", count(action_id) as count, user_id, action_id, item_id, item_type_id, active_user_id, active,
			date_posted, 						/* actual date the row was created */
			MAX(date_posted) as last_updated, /* the most recent date of the group */
			DATE(date_posted) as day_posted,  /* actual DAY the row was created */
			MAX(active) as active 			  /* if any one of them is active, then the group is active */
			FROM `".self::$table."` 
				WHERE user_id = ".(int)$_mixed ." 
				GROUP BY day_posted, action_id, item_id, item_type_id
				ORDER BY date_posted DESC";	
		
		//print $sql;

 		/******CACHING *******/	
		$key_name = self::$cache_key_prefix.$_mixed."_messages_".static::set_size()."_".$offset;		
		

		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		 } catch (Exception $e) {
			$mesg =  $e->getMessage() . " // ".self::$myDBHandler->error(true);
			if(DEBUGGING) print $mesg;
			error_log($mesg);
        	throw $e;
        	return array();
    	}

    	#print " = ".count($result);

		if (is_array($result)) {
		
			#print PHP_EOL."! OFFSET = $offset & set_num = $set_num".PHP_EOL;
			#print "Size Before = ".count($result) . PHP_EOL; 
			
			$sliced = array_slice( $result, $offset, static::set_size());
			
			if(count($sliced) < 1){  return array(); }
			
			#print "SIze After = ".count($sliced). PHP_EOL;
			
			return $sliced;	
		
		}else{
			$mesg = self::$myDBHandler->error(true);
			if(DEBUGGING) print $mesg;
			error_log($mesg);
        	throw $e;
        	return null;	
		}

	}

	/*
		updates all the rows for the user: sets viewed to 1
		@returns true/false
	*/
	public function clear_unread_message_count($_mixed){

		if( self::get_unread_message_count($_mixed) > 0 ){ 
			
			// update all the rows
			$sql = "UPDATE ".self::$table." SET `viewed` = '1' WHERE user_id = " .(int)$_mixed;
			
			try{
				$result = self::_handle_db_query($sql);
			} catch (Exception $e){
				if(DEBUGGING) print __CLASS__.':'.__FUNCTION__ . " ERROR ".$e->getMessage()." ".PHP_EOL.self::$myDBHandler->error(true);
	            error_log(	__CLASS__.':'.__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
	            return false;
			}		
	        
	        return true;
		}else{
			// print "unread msg ct = ".self::get_unread_message_count($_mixed);
	        return true;
		}

	}

	/*
		updating the notifications rows 
		which ones to update? we take an ID of a row and then find 'related' rows.
		for a row to be 'related' it needs to be
		of the same day
		of the same action_id
		of the same item_type_id
	*/
	public function set_active_for_related_notifications($where_array, $properties_array){

		if((int)$where_array[self::$primary_id] < 1){ 
			throw new Exception(__CLASS__.':'.__FUNCTION__ . " (2) Error ID is invalid", 1);
			return false;
		}
		// get all the proper rows
		$sql = "SELECT n.".self::$primary_id."
			FROM ".self::$table." n
			INNER JOIN ".self::$table." t1 ON (n.action_id = t1.action_id AND t1.".self::$primary_id." = ".(int)$where_array[self::$primary_id].") 
			INNER JOIN ".self::$table." t2 ON (n.item_id = t2.item_id AND t2.".self::$primary_id." = ".(int)$where_array[self::$primary_id].") 
			INNER JOIN ".self::$table." t3 ON (n.item_type_id = t3.item_type_id AND t3.".self::$primary_id." = ".(int)$where_array[self::$primary_id].") 
			INNER JOIN ".self::$table." t4 ON (DATE(n.date_posted) = DATE(t4.date_posted) AND t4.".self::$primary_id." = ".(int)$where_array[self::$primary_id].")";
			#print $sql;

		try{
			$result = self::$myDBHandler->get($sql, null, null);
		 } catch (Exception $e) {
			$mesg = $e->getMessage(). "//".self::$myDBHandler->error(true);
			if(DEBUGGING) print $mesg;
			error_log($mesg);
        	throw $e;
        	return null;	
    	}

		if (!is_array($result)) {
			$mesg =  self::$myDBHandler->error(true);
			if(DEBUGGING) print $mesg;
			error_log($mesg);
        	throw $e;
        	return null;	
		}

		// get the ids together all the rows
		$items_arr = array(); 		
		foreach ($result as $row) {
			array_push($items_arr, $row[self::$primary_id]);
		} 
	
		// update all the rows
		$sql = "UPDATE ".self::$table." set active = '0' WHERE ".self::$primary_id." IN (".implode(",",$items_arr).")";

		try{
			$result = self::_handle_db_query($sql);
		} catch (Exception $e){
			if(DEBUGGING) print __CLASS__.':'.__FUNCTION__ . " ERROR ".$e->getMessage()." ".PHP_EOL.self::$myDBHandler->error(true);
            error_log(	__CLASS__.':'.__FUNCTION__ . " ERROR ".$e->getMessage()." ".self::$myDBHandler->error(true));
            return false;
		}

		return $result;
	}


	/************BEGIN GEARMAN WORKER FUNCTIONS****************/

	/*
		this method builds a function to spawn a new notification when a follower is inserted
	*/
	public function notify_on_follower_inserted($args) {
		
		$required_parameters = array('followed_id', 'follower_id', 'datetime', 'datetime');

		foreach($required_parameters as $k=>$v){
            if( !isset($args[$v]) ){ 
                throw new Exception(__METHOD__." Value for $v is missing.", 1);                
                return false;
            }
        }

        if ($this->_is_refollowing($args)) {  
        	error_log(__METHOD__." Can't post notification on a re-follow");
        	return false;
        }
		
		$sql = "INSERT DELAYED INTO ".self::$table." (`user_id`, `active`, `item_id`, `item_type_id`, `action_id`, `active_user_id`, `last_updated`, `date_posted`) 
				VALUES (".$args['followed_id'].", 1, 0, 0, 5, ".$args['follower_id'].", '".$args['datetime']."', '".$args['datetime']."')";
			
	
		try{
			$result = self::$myDBHandler->query($sql);
		 } catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			print $e->getMessage();
        	throw $e;
        	return false;
    	}
    	return true;
		
	}

	/* 
	checks if there is currently a notification row for the follower/ followed pair
	*/
	private function _is_refollowing($args) { 

		//this statement checks to see if someone is being re-followed so they don't get notified again
		$sql = "SELECT active_user_id FROM ".self::$table." WHERE user_id = ".$args['followed_id']." AND active_user_id = ".$args['follower_id']." AND action_id = 5";
	
		if ($result = self::$myDBHandler->query($sql)) {
			if (self::$myDBHandler->num_rows($result) > 0) {
				return true;
			}
		 } else {
			print self::$myDBHandler->error(DEBUGGING); 
        	return false;
    	}

    	return false;
	}

	/*
		this method spawns new notifications when a proposal is inserted
	*/
	public function notify_on_proposal_inserted($args) {

		$required_parameters = array('date_posted', 'title_id', 'user_id');

		foreach($required_parameters as $k=>$v){
            if( !isset($args[$v]) ){ 
                throw new Exception(__METHOD__." Value for $v is missing.", 1);                
                return false;
            }
        }

		//get all supporters of the proposal's story
		$sql = "SELECT user_id FROM likes WHERE item_id = ".$args['title_id']." AND item_type_id = 3 AND active = 1 AND user_id <> ".$args['user_id']; 
		
		
		/******CACHING *******/
		$key_name = self::$cache_key_prefix."supporters_3_".$args['title_id'];		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 10); // 10 minutes
		
		try {
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		} catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			print $e->getMessage();
        	throw $e;
        	return false;
    	}

		if (is_array($result)) {

			$insert_sql = "INSERT DELAYED INTO notifications (`user_id`, `active`, `item_id`, `item_type_id`, `action_id`, `active_user_id`, `last_updated`, `date_posted`) VALUES ";
			
			foreach ($result as $row) {
				$insert_sql .= "(".$row['user_id'].", 1, ".$args['title_id'].", 3, 3, ".$args['user_id'].", '".$args['date_posted']."', '".$args['date_posted']."'),";
			}

			$insert_sql = rtrim($insert_sql, ",");

			$insert = self::$myDBHandler->query($insert_sql); //run the insert
		}
		else {
			print self::$myDBHandler->error(DEBUGGING);
		}

    	
	}

	/*
		this method spawns new notifications when a like is inserted
	*/
	public function notify_on_like_inserted($args) {
		
		$required_parameters = array('datetime', 'item_id', 'item_type_id', 'user_id');

		foreach($required_parameters as $k=>$v){
            if( !isset($args[$v]) ){ 
                throw new Exception(__METHOD__." Value for $v is missing.", 1);                
                return false;
            }
        }
  
        /** this section should be moved to the right model **/

		if ($args['item_type_id'] == 1) { //proposal like - notify the proposer
 		
	 		$sql = "SELECT user_id FROM proposals WHERE proposal_id = ".$args['item_id']." AND user_id <> ".$args['user_id']." LIMIT 1";

			/******CACHING *******/
			$key_name = self::$cache_key_prefix."proposer_".$args['item_type_id']."_".$args['item_id'];		
		
		}
		else if ($args['item_type_id'] == 3) { //story like - notify the owner/ poster

			$sql = "SELECT user_id FROM titles WHERE title_id = ".$args['item_id']." AND user_id <> ".$args['user_id']." LIMIT 1";

			/******CACHING *******/
			$key_name = self::$cache_key_prefix."storyowner_".$args['item_type_id']."_".$args['item_id'];		
		
		}
		else if ($args['item_type_id'] == 5) { //talent like - notify the owner/ poster

			$sql = "SELECT user_id FROM talent WHERE talent_id = ".$args['item_id']." AND user_id <> ".$args['user_id']." LIMIT 1";

			/******CACHING *******/
			$key_name = self::$cache_key_prefix."talentowner_".$args['item_type_id']."_".$args['item_id'];		
		
		}

		/** /end this section should be moved to the right model **/


		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 10); // 10 minutes
		
		try {
			$result = self::$myDBHandler->get($sql, $key_name, $expires);
		} catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			print $e->getMessage();
        	throw $e;
        	return false;
    	}

		if (is_array($result)) {

			$insert_sql = "INSERT DELAYED INTO notifications (`user_id`, `active`, `item_id`, `item_type_id`, `action_id`, `active_user_id`, `last_updated`, `date_posted`) VALUES ";
			
			foreach ($result as $row) {
				if ($row['user_id'] == 0) { 
					error_log("Can't post notification for an admin-created profile");
					return false; 
				}
				$insert_sql .= "(".$row['user_id'].", 1, ".$args['item_id'].", ".$args['item_type_id'].", 2, ".$args['user_id'].", '".$args['datetime']."', '".$args['datetime']."'),";
			}

			$insert_sql = rtrim($insert_sql, ",");

			$insert = self::$myDBHandler->query($insert_sql); //run the insert
		}
		else {
			print self::$myDBHandler->error(DEBUGGING);
		}
		
	}


	/*
		this method spawns new notifications when a comment is inserted
	*/
	public function notify_on_comment_inserted($args) {

		$required_parameters = array('datetime', 'item_id', 'item_type_id', 'user_id');

		foreach($required_parameters as $k=>$v){
            if( !isset($args[$v]) ){ 
                throw new Exception(__METHOD__." Value for $v is missing.", 1);                
                return false;
            }
        }
        /** this section should be moved to the right model **/
        if ($args['item_type_id'] == 1 || $args['item_type_id'] == 2) { //proposal or credit comment
			$sql = "SELECT user_id FROM likes WHERE item_id = ".$args['item_id']." AND item_type_id = ".$args['item_type_id']." AND active = 1 AND user_id <> ".$args['user_id']; 
			$action_id = 1;
		}
		else if ($args['item_type_id'] == 3 || $args['item_type_id'] == 5) { //story or talent endorsement
			$sql = "SELECT user_id FROM likes WHERE item_id = ".$args['item_id']." AND item_type_id = ".$args['item_type_id']." AND auto_like = 0 AND active = 1 AND user_id <> ".$args['user_id']; 
			$action_id = 6;
		}
		/** /end this section should be moved to the right model **/



		/******CACHING *******/
		$key_name = self::$cache_key_prefix."_supporters_".$args['item_type_id']."_".$args['item_id'];	
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		///print("TRY THIS");

		try {

			$result = self::$myDBHandler->get($sql, $key_name, $expires);

		} catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			print $e->getMessage();
        	throw $e;
        	return false;
    	}
        
		if (is_array($result)) {

			$insert_sql = "INSERT DELAYED INTO notifications (`user_id`, `active`, `item_id`, `item_type_id`, `action_id`, `active_user_id`, `last_updated`, `date_posted`) VALUES ";
			////print($insert_sql);
			
			foreach ($result as $row) {
				$insert_sql .= "(".$row['user_id'].", 1, ".$args['item_id'].", ".$args['item_type_id'].", ".$action_id.", ".$args['user_id'].", '".$args['datetime']."', '".$args['datetime']."'),";
			}

			$insert_sql = rtrim($insert_sql, ",");
			///print($insert_sql);

			$insert = self::$myDBHandler->query($insert_sql); //run the insert
		}
		else {
			print self::$myDBHandler->error(DEBUGGING);
		}


	}
	/************END GEARMAN WORKER FUNCTIONS****************/

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

		$filter = array(
			'user_id' => FILTER_VALIDATE_INT,
			'active' => FILTER_VALIDATE_INT,
			'viewed' => FILTER_VALIDATE_INT,
			'item_id' => FILTER_VALIDATE_INT,	
			'item_type_id' => FILTER_VALIDATE_INT,
			'active_user_id' => FILTER_VALIDATE_INT,
			'action_id' => FILTER_VALIDATE_INT,
			'date_posted' => FILTER_SANITIZE_STRING,	// always $now = date('Y-m-d H:i:s');
			'last_updated' => FILTER_SANITIZE_STRING	// always $now = date('Y-m-d H:i:s');
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
   

}
