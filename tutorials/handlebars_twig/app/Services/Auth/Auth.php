<?php

namespace app\Services\Auth;

class Auth {

	private static $user_is_logged_in = false;
    private static $user_is_admin = false;

    public static function get_logged_in_user_id($b = false){
    	return @$_SESSION['id'];
    }
    public static function set_user_is_logged_in($b = false){
    	self::$user_is_logged_in = (($b == true)?true:false);
    }

    public static function user_is_logged_in(){
    	self::$user_is_logged_in = (isset($_SESSION['id'])?true:false);
    	return self::$user_is_logged_in;
    }

    public static function user_is_admin($_id = null){
        if($_id != null){
            //get from database;
            //self::$user_is_logged_in = (isset($_SESSION['id'])?true:false);
        }else{
            self::$user_is_admin = (isset($_SESSION['is_admin'])?true:false);     
        }
        return self::$user_is_admin;
    }

}
