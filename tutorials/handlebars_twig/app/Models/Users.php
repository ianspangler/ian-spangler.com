<?php

namespace app\Models;
use Exception;
use StdClass;
use app\Models\Notifications;
use app\Services\Auth\PersistentAuthIflist;

class Users extends BaseModel{

	protected static $table = 'users';
	/* 
		only these columns will be pulled from user table
	*/	
	protected static $allowed_fields = array('user_id' ,'last_name' ,'first_name' , 'username' , 'email', 'dob', 'gender', 'main_pic', 'main_pic_geom', 'join_date', 'oauth_uid', 'oauth_provider', 'is_admin', 'last_loggedin' );
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'user_id';
	/*
		relationships
	*/
	protected static $related = array(
		"has_many" => array(
			"notifications" => array()
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
	protected static $cache_key_prefix = 'users_';


	public function __construct($config){
		//
		parent::__construct();
		 
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new Users(array());
		}
		return $inst;
	}
	
	/*
		get a user by id or (FUTURE) url_handle
	*/	
	public static function get_result($_mixed, $related = array(), $options = array() ){
		// updated to pass options to get
		$result = self::get($_mixed, $options);

		if (is_array($result)) { 
			
			foreach($result as $row){				
				self::$result = self::array_to_object( $row );
				break;
			}  
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
	
	public static function get_list_for_user_data($user_ids){


		return self::get_list(array(
            "key_name"=>implode("",$user_ids),
            "where"=>array(
                    array(
                    "name"=>"user_id",  // where column like " where name IN ($user_ids) "
                    "operator"=>"IN", 
                    "value"=>$user_ids
                    )/*,
                        array(
                        "name"=>"user_id",
                        "operator"=>"<>",
                        "value"=>"999"
                    )*/
                ),
             "what"=>array( // column list
                "user_id",
                    "last_name",
                    "first_name",
                    "username",
                    "email",
                    "oauth_provider",
                    "oauth_uid",
                    "main_pic", 
                    "main_pic_geom"
                    )     
            ) 
        );
	}
	/*
		required by interface
		to be used to attach secondary and associated data
	*/
	protected function _has_relationships(){
		return (count(self::$related['has_many']) >0 || count(self::$related['has_one']) > 0) ;
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
		sql for a user data
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

	//////////// ITEMS MOVED FROM LOGIN-STANDARD.PHP

	public function set_loggedinuser_data($userdata,$email){

		$_SESSION['id'] = $userdata->user_id ;  
		$_SESSION['oauth_id'] = $userdata->oauth_uid ; 
		$_SESSION['oauth_provider'] = $userdata->oauth_provider ;  
		$_SESSION['username'] = $userdata->username ; 
		$_SESSION['user_fn'] = $userdata->first_name ;  
		$_SESSION['user_ln'] = $userdata->last_name ;  
		$_SESSION['is_admin'] = @$userdata->is_admin ;  
		$_SESSION['email'] = $email;
		$_SESSION['main_pic'] = $userdata->main_pic;		

		unset($_SESSION['error']);

	}

	public function set_remember_me($remember_me, $userdata){
		if ($remember_me == 'Y') { 

			/************ Added 2/11 *********/ 
			$sampleexpireseconds = null;
			if(isset($_POST['seconds'])&& (int)$_POST['seconds'] < 30 && (int)$_POST['seconds'] > 1){
				$sampleexpireseconds = $_POST['seconds'];
		    	#error_log( "DEBUG: using $sampleexpireseconds duration ",1);   		 
			}

		 	// Store key in cookie    
		    // if not logged in, log them in
		    if (false == PersistentAuthIflist::loggedIn()){ 
		        // ... use $userid to log user into session  # error_log( "DEBUG: logging you in, ".$userdata['user_id'] ,1);   		 
		    	try{
					if(false == PersistentAuthIflist::login($userdata->user_id)){
						throw new Exception( "PersistentAuthIflist::login Failed with ".$userdata->user_id .", ". $sampleexpireseconds."." );
					}
	    		}
	            catch (Exception $e) {    
	                error_log(" Failure connecting to PersistentAuthIflist login. ". $e->getMessage() ); #print "\n FAIL ". $e->getMessage()."\n";
	                exit; 
	            }   

			}else{
				error_log( "User already logged in",1);
			} 
		/************  *********/ 
			 
		}else{
			error_log( "DEBUG: ".__FUNCTION__." : NOT IN REMEMBER ME ");
		}  
	}	

}

