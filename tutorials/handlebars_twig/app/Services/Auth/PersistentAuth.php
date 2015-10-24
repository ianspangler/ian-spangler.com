<?php 
/*
    http://www.chrislondon.co/php-persistent-login/


    First, we need to update the site when you login to store the session into the cookie. This code will go with your current login system.

        if (/* Code that verifies credentials * /) {
            // ... code to log user into session

            PersistentAuth::login($userId);
        }

    Second, we want to check if a new user to the site has cookie credentials. This code will go at the beginning of your code. It checks, first, are we already logged in in our $_SESSION? if not, try the cookie-based login.

        if (/* !loggedIn() * / && ($userId = PersistentAuth::cookieLogin())) {
            // ... use $userId to log user into session 
        }

    Third, in accordance with Charles Miller’s article we cannot allow a user to the following sections if they are logged in via cookie:

    Changing the user’s password
    Changing the user’s email address (especially if email-based password recovery is used)
    Any access to the user’s address, payment details or financial information
    Any ability to make a purchase

        if (PersistentAuth::isCookieLogin()) {
            // User is logged in via cookie and don't have access to this 
            // section. We need to ask them for the password. Maybe send 
            // them to some login page?

            // TODO ask for password or send to a password page
        }


    =SQL============================================

    CREATE TABLE user_sessions (
        user_id INT(11) UNSIGNED NOT NULL,
        session VARCHAR(128) NOT NULL,
        created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    ALTER TABLE user_sessions ADD UNIQUE(session);

*/


namespace app\Services\Auth;
use Exception;
use app\Models\User_Sessions;

class PersistentAuth {
    // START CONFIG
    // Here are some config variables to help integrate this code

    // Database settings
    const SESSION_TABLE  = 'user_sessions';
    const USERID_COLUMN  = 'user_id';
    const SESSION_COLUMN = 'session';
    const CREATED_COLUMN = 'created';

    // Session settings
    const SESSION_KEY = 'PersistentAuthCookie';
    
    // Cron settings
    const USE_CRON = false;
    
    // Cookie Settings
    const COOKIE_KEY = 'PersistentAuthCookie';
    const NUM_DAYS = 30; // number of key is valid
    // END CONFIG

    public static $userId = null;
    public static $sessionKey = null;

    static $user_sessions_model = null;

    /**
     * Set a cookie for this user id
     * 
     * @param type $userId
     */
    public static function login($userId, $expire = null) {
        $key = self::generateKey();
        
        if (!$userId || (int)$userId < 1 ){
            if(DEBUGGING) print "userId $userId is empty ";
            return false;  
        } 
        
        $db = self::getDb();
        static::$userId = (int)$userId;
         
        $stmt = $db->prepare('INSERT INTO ' . 
            PersistentAuth::SESSION_TABLE . '(' .
            PersistentAuth::USERID_COLUMN . ', ' . 
            PersistentAuth::SESSION_COLUMN . ') VALUES (:user_id, 
            :session_key)');
            
        $stmt->bindParam(':user_id',     $userId);
        $stmt->bindParam(':session_key', $key);
        
        // Store key in database
        $stmt->execute();
         
        /*
        $sql = 'INSERT INTO ' . 
            PersistentAuth::SESSION_TABLE . '(' .
            PersistentAuth::USERID_COLUMN . ', ' . 
            PersistentAuth::SESSION_COLUMN . ") VALUES ($userId, $key)";

        if (!$db->query($sql)){
            throw new Exception("dbHandler error ".$db->error(true));
            if(DEBUGGING) print "ERROR ".$db->error(true);
            return false;
        }
        */
        $expiration = time() + (86400 * static::NUM_DAYS); 
        if($expire != null){ $expiration =  time() + (int)$expire; }   
        // Store key in cookie
        return self::set_cookie(static::COOKIE_KEY, $userId, $key, $expiration);
        /* setcookie(static::COOKIE_KEY, 
                "$userId|$key", 
                $expiration, "/");
                */
    }

    protected static function set_cookie($cookie_key = null, $userid, $key, $expiration){
        $cookie_key = (($cookie_key)? $cookie_key : static::COOKIE_KEY );

        #print "SET_COOKIE ".$cookie_key . " :: ". $userid. " | ".$key." exp $expiration <br>\n";

        return setcookie($cookie_key,  $userid."|".$key,  $expiration, "/");

    }
    /**
     * Remove the cookie so they can't cookie login
     */
    public static function logout() {
        #print " unsetting ". static::COOKIE_KEY."<br>\n";

        return self::set_cookie(static::COOKIE_KEY, static::$userId, static::$sessionKey, (time() - 3600));
        /*
        setcookie(static::COOKIE_KEY,
                  static::$userId . '|' . static::$sessionKey,
                  time() - 3600, "/");
        */          
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
        
        $db = self::getDb();
        
        // check if cookie row is found
        
        $stmt = $db->prepare('SELECT ' . PersistentAuth::USERID_COLUMN . 
            ' FROM ' . PersistentAuth::SESSION_TABLE . ' WHERE 
            ' . PersistentAuth::USERID_COLUMN . ' = :user_id AND ' . 
            PersistentAuth::SESSION_COLUMN . '= :session LIMIT 1');
            
        $stmt->bindParam(':user_id',   static::$userId);
        $stmt->bindParam(':session',   static::$sessionKey);

        if ($data = $stmt->fetch()) {
            $_SESSION[PersistentAuth::SESSION_KEY] = true;
            return self::$userId;
        } else {
            return false;
        }
    

        /*$sql = 'SELECT ' . PersistentAuth::USERID_COLUMN . 
                ' FROM ' . PersistentAuth::SESSION_TABLE . ' WHERE 
                       ' . PersistentAuth::USERID_COLUMN . ' = '.self::$userId.' AND ' . 
                           PersistentAuth::SESSION_COLUMN . '= '. self::$sessionKey.' LIMIT 1' ;
            
        if ($result = $db->query($sql)) {
            $_SESSION[PersistentAuth::SESSION_KEY] = true;
            return self::$userId;
        } else {
            if(DEBUGGING) print $db->error(true);
            error_log('DB ERROR '.$db->error(true));
            return false;
        }
        */

    }

    /**
     * Check if we were logged in via cookie
     * 
     * @return bool
     */
    public static function isCookieLogin() {
        if (is_null(static::$sessionKey)) static::loadSession();

        return isset($_SESSION[static::SESSION_KEY]) 
                  && $_SESSION[static::SESSION_KEY];
    }

    /**
     * generate 128-bit random number (39 characters long)
     * 
     * @return string
     */
    protected static function generateKey() {        
        // get the largest 999999999 we can rand to.
        /*$max = (int)str_pad('', strlen(mt_getrandmax()) - 1, 9);
        $min = (int)str_pad('1', strlen($max), 0, STR_PAD_RIGHT);

        $key = '';
        
        while (strlen($key) < 69) {
            $key .= mt_rand($min, $max);
        }
        return substr($key, 0, 69);
        */
        return bin2hex(openssl_random_pseudo_bytes(32));
        /*
            ********************

            WHEN YOU HAVE TIME, IMPLEMENT THIS
            The better approach that I recommend is to store the cookie with three parts.

            function onLogin($user) {
                $token = GenerateRandomToken(); // generate a token, should be 128 - 256 bit
                storeTokenForUser($user, $token);
                $cookie = $user . ':' . $token;
                $mac = hash_hmac('sha256', $cookie, SECRET_KEY);
                $cookie .= ':' . $mac;
                setcookie('rememberme', $cookie);
            }
            Then, to validate:

            function rememberMe() {
                $cookie = isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : '';
                if ($cookie) {
                    list ($user, $token, $mac) = explode(':', $cookie);
                    if ($mac !== hash_hmac('sha256', $user . ':' . $token, SECRET_KEY)) {
                        return false;
                    }
                    $usertoken = fetchTokenByUserName($user);
                    if (timingSafeCompare($usertoken, $token)) {
                        logUserIn($user);
                    }
                }
            }
        */
    }
    
    
    /**
     * Run this function on a cron to save user time. This will delete
     * any session that is older than the NUM_DAYS set above
     */
    public static function deleteOld() {
        $db = self::getDb();
        // Delete old sessions
         
            $stmt = $db->prepare('DELETE FROM ' . 
            PersistentAuth::SESSION_TABLE . ' WHERE 
            ' . PersistentAuth::CREATED_COLUMN . '< :timeout LIMIT 1');
        
            $timeout = strtotime('-' . PersistentAuth::NUM_DAYS . ' days');
            $stmt->bindParam(':timeout', date('Y-m-d H:i:s', $timeout));
            $stmt->execute();
         

        $timeout = strtotime('-' . static::NUM_DAYS . ' days');
        $mytimeout = date('Y-m-d H:i:s', $timeout); 
    }

    /**
     * 
     */
    protected static function loadSession() {
        $db = self::getDb();

        if (!static::USE_CRON) {
            static::deleteOld();
        }
        
        // load cookie
        if (isset($_COOKIE[static::COOKIE_KEY])) {
            $parts = explode('|', $_COOKIE[static::COOKIE_KEY]);
            
            static::$userId     = $parts[0];
            static::$sessionKey = $parts[1];
        } else {
            static::$userId = static::$sessionKey = false;
        }
    }

    /**
     * Function to get our database connection.
     * 
     * TODO change this to fill your needs
     * 
     * @global type $db
     * @return PDO $db
     */
    protected static function getDb() {
        global $dbHandler;
        //print "IN getDb";
        static::$user_sessions_model = new User_Sessions();
        //var_dump(self::$user_sessions_model);

        return $dbHandler;
    }
    
    public static function test_me() {
        global $dbHandler;    
        return $dbHandler;
    }
}
