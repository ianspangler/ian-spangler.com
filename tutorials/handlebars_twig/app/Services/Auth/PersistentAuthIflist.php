<?php 
/*
    see PersistentAuth for details
*/

namespace app\Services\Auth;
use Exception;
use app\Models\Users;
use app\Services\Auth\Auth;

class PersistentAuthIflist extends PersistentAuth{
    // START CONFIG
    // Here are some config variables to help integrate this code

    // Database settings
        /* inherited */

    // Session settings
    const SESSION_KEY = 'PersistentAuthIf';
    
    // Cron settings
    const USE_CRON = false;
    
    // Cookie Settings
    const COOKIE_KEY = 'PersistentAuthIf';
    const NUM_DAYS = 30; // number of days key is valid

    static $debug = false;
    // END CONFIG

    static $user_sessions_model = null;

    public static function on_pageload_check(){

        // gets db connection & sets up user_sessions model    
        self::getDb();

        if(Auth::user_is_logged_in()){ //if(isset($_SESSION['id'])){ // already logged in 
           
            if(self::$debug) print "Logged in with the session_id: ".$_SESSION['id']."\n";
            
            if(isset($_COOKIE[self::COOKIE_KEY])){
                if(self::$debug) print "Also you have a valid persistence cookie: ".$_COOKIE['PersistentAuthIf']."\n";
            }else{
                if(self::$debug) print_r($_COOKIE);
                if(self::$debug) print "But you have no persistence cookie \n I'm going to give you a new one now \n";
                self::login($_SESSION['id']);
                if(self::$debug) print "?... ";   
            }   
            #if(self::$debug) print "Done";   
           
        }else{

            if(self::$debug)print "Session_id is expired or SESSION is expired/changed \n";

            if(isset($_COOKIE[self::COOKIE_KEY])){ // there is a cookie
                if(self::$debug) print "But you have a valid persistence cookie: ".$_COOKIE['PersistentAuthIf']."\n so i can try to log you in \n";
                
                self::isCookieLogin(); // get data from cookie;
   
                if(self::cookieLogin() ){  // maybe youre already in the db?

                    if(self::$debug) print "Youre already in the db, so i can set up your Session details. ";

                    // get user data from DB
                    $users_model = new Users(array());
                    $userdata = $users_model::get_result((int)self::$userId);

                    // update the last loggedin field
                    $lastloggedin_date = self::getNow();
                    $userdata2 = $users_model->update(array('user_id'=>(int)$userdata->user_id), array('last_loggedin'=>$lastloggedin_date) );
                    
                    // set the sesstion data
                    $users_model->set_loggedinuser_data($userdata, $userdata->email);
                    

                    #if(self::$debug) print "See ? \n";
                    #if(self::$debug) print_r($_SESSION);

                }else{ //  do nothing. perhaps delete the cookie?
                    if(self::$debug) print "Your cookie details werent in the db, so im going to assume there is something odd going on, and youll need to login ";
                    error_log('ERROR: Cookie present but not in DB. Deleteing Cookie.',1);
                    self::logout();
                }
                    if(self::$debug) print "Done";   

            }else{ // has never been here, or has no cookie for whatever reason
                if(self::$debug) print "And you have no persistence cookie. You must log in \n"; 
                if(self::$debug) print "Done";   
            }           
        }
    }
    /**
     * Set a cookie for this user id
     * 
     * @param type $userId
     */
    public static function login($userId, $expireseconds = null) {
        $key = self::generateKey();
        $expiration = (time() +  (($expireseconds != null) ? $expireseconds : (86400 * self::NUM_DAYS) )); 

        if (!$userId || (int)$userId < 1 ){
            $msg = __FUNCTION__ . " userId $userId is empty ";
            if(DEBUGGING) print $msg;
            error_log($msg);
            return false;  
        } 
        
        try{
            if(self::$debug) print __FUNCTION__." Inserting into DB:$key \n\n";

            self::$user_sessions_model->insert(array('user_id'=>$userId, 'key'=>$key));
            if(self::$debug) print __FUNCTION__ . " Insert user_sessions_model OK: $userId | $key | $expiration ";
            #error_log(__FUNCTION__ . " Insert user_sessions_model OK: ".self::COOKIE_KEY." | $userId | $expiration | $key  ");
        }catch (Exception $e) {  
            $msg = __FUNCTION__ . " Error Processing Request: user_sessions_model.".$e->getMessage();
            throw new Exception($msg);
            if(DEBUGGING) print $msg;
            error_log($msg, 1);
            return false;            
        }
        
        if(self::$debug) print __FUNCTION__." Setting cookie ".self::COOKIE_KEY."\n\n";
        // Store key in cookie
        $res = self::set_cookie(self::COOKIE_KEY,$userId,$key,$expiration);  /*setcookie(self::COOKIE_KEY,  "$userId|$key",   $expiration, "/");*/
        return true;
    }
    /*  
        called from /?logout
        logs user out by 
        a) expiring the cookie
        b) removing the user_session from the db ( this allows for 1 user to be logged infrom 2 devices at the same time and only login gout of one )
    */    
    public static function logout() {
        if (is_null(self::$sessionKey)) self::loadSession();
        self::getDb();

        if(self::$debug) print __FUNCTION__." DELETING ".self::COOKIE_KEY . " :: ". self::$userId. " | ".self::$sessionKey."<br>\n";

        self::$user_sessions_model->deleteOldwithKey(self::$sessionKey);
       
        return self::set_cookie(self::COOKIE_KEY, self::$userId, self::$sessionKey, (time() - 3600));
    }

    /**
     * Checks if the cookie has a valid session. Return true if it does
     * return false if it doesn't
     * 
     * @return boolean whether or not the cookie had a valid session
     */
    public static function cookieLogin() {
        if (is_null(self::$sessionKey)) self::loadSession();
        
        // No cookie at all
        if (!self::$sessionKey || !self::$userId) return false;
        
        if($result = self::$user_sessions_model->get_result(self::$userId, self::$sessionKey) ){
            $_SESSION[self::SESSION_KEY] = true;
            return true;
        }else{
            throw new Exception(__FUNCTION__ . " Error Processing Request", 1);
            return false;   
        }

    }      
    
    /**
     * Run this function on a cron to save user time. This will delete
     * any session that is older than the NUM_DAYS set above
     */
    public static function deleteOld() {
        $db = self::getDb();
        // Delete old sessions
        $timeout = strtotime('-' . PersistentAuth::NUM_DAYS . ' days');
        $mytimeout = date('Y-m-d H:i:s', $timeout);

       if($result = self::$user_sessions_model->delete($mytimeout)){
            return true;
       }else{
            return false;
       }
    }

    /**
     * see if the user is already logged in:
      - has cookie?
      - has db entry?  
     */
    public static function loggedIn(){
        if($result = self::isCookieLogin()){
            // we have a cookie.
            return true; 
        }
            return false;
    }
    

    private static function getNow(){
        date_default_timezone_set("America/New_York");
        $date = new \DateTime(date('Y-m-d H:i:s'));
        return $date->format('Y-m-d H:i:s');
    }
    
}
