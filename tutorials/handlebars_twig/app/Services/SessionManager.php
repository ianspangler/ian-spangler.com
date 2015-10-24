<?php

namespace app\Services;

class SessionManager{

	private static $urls = array(
		'fb_login_redirect.php',
		'login',
		'login.php',
		'login-form.php',
		'login-standard.php',
		'login-facebook.php',
		'signup-form.php',
		'join-form.php',
		'addNewUser.php'
		);

	public static function sess_start(){

		if (version_compare(phpversion(), '5.4', '<')) {
			// Source: http://www.php.net/manual/en/function.session-status.php
			// For versions of PHP < 5.4.0

			if(session_id() == '') {
			    session_start();
			}
		}else{
			if (session_status() == PHP_SESSION_NONE) {
	    		session_start();
			}
		}
	}


	
	public static function clear_redirect(){
		self::sess_start();

		$uri = $_SERVER['REQUEST_URI'];
		
		# parse URL
		# look for the base folder and see if its in our list
		$parsed = parse_url($uri);
		$parts = explode("/",$parsed['path']);
		# loop through urlparts and try to match against valid requests
		foreach($parts as $k=>$v){
			if(trim($v) == ""){ continue; }
			if(trim($v) == "includes"){ continue; }
			
			if(in_array( trim($v), self::$urls ) ){ 
				#if(DEBUGGING) print " found $v <br>";
				return true; 
			}else{
				#if(DEBUGGING) print " unfound $v <br>";
			}
		} 
		if(isset($_SESSION['redirect_url'])){ 
			#if(DEBUGGING) print "<br>SessionManager: unsetting ";
			unset($_SESSION['redirect_url']);
			#if(DEBUGGING) print "<br>SessionManager: proof: ". @$_SESSION['redirect_url'] ;
		}else{
		}
		
		return true;
	}

	/*
		added [1/22/15, 3:15:35 PM]
	*/
	public static function check_loggedin(){
		if(isset($_SESSION['id']) && trim($_SESSION['id'])!=''){
			header( 'X-Logged-In:True', true);
		}else{
			header( 'X-Logged-In:False', true );
		}	
	}
	/*
		added [1/26/15]
	*/
	public static function set_mobile_status($bool){
		if($bool == true){
			header( 'X-isMobile:True', true);
		}else{
			header( 'X-isMobile:False', true );
		}	

	}
}
