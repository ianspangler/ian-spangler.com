<?php  

namespace app\Services;
use app\Models\User_Sessions; 
use app\Services\Router;
use Exception; 

class UserOnline {

	private static $is_online = false;
	private static $online_window = 300; // 5 minutes 

	/**
	* this method updates the user_sessions row for the user
	*/
	public static function update_user_active($_user_id) {
		self::$is_online = false;

		if((int)$_user_id < 1){
			return false;			
		}
		
	
		try{
			$result = User_Sessions::update_last_active((int)$_user_id);
		}
		catch (Exception $e) {
            if(DEBUGGING) print PHP_EOL.__FUNCTION__. "::".$e->getMessage().PHP_EOL;
            return false;
        }
        if($result != false){
            return true;
        }
    
	}

	public static function is_online($_user_id){
		self::$is_online = false;

		#print __FUNCTION__.PHP_EOL;

		if((int)$_user_id < 1){
			return false;			
		}
		
		try{
			$result = User_Sessions::get_last_active((int)$_user_id);
		}
		catch (Exception $e) {
            if(DEBUGGING) print PHP_EOL.__FUNCTION__. "::".$e->getMessage().PHP_EOL;
            return false;
        }

        if($result != ""){

	        $date = new \DateTime($result);
			$ts = $date->getTimestamp();
			// ubtracts this number of seconds from the time stamp. thats when s/he would need to have been active to determine
			$w = strtotime("-".self::$online_window.' seconds');

			if($ts <= $w){
				#print " OFFLINE ";
				return false;
			} else{
				#print " ONLINE ";	
				return true;
			}

        }else{
			return false;        	
        }

	}
}
