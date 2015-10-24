<?php

	/**
	 * Runs basic security checks for any page
	 */
	namespace app\Services;
	use app\Services\Auth\Auth;
	use Exception;

	class Security {
	
	static $valid_hosts = array("iflist.com"); //"localhost",

		public static function Instance()
		{
			static $inst = null;
			if ($inst === null) {
				$inst = new Security();
			}
			return $inst;
		}
	
		/**
		* Checks that the page has a referring URL
		*/	
		public static function is_secure($options_array = array()) {
			// a list of tests to add to
			$tests = array(
				'secure_referer'=>'The referer is not accepted',
				'security_test2'=>'You failed the easy test'
			);

			foreach($tests as $f=>$message){
				// if any fail, return false
				if(self::$f() == false){ 
					// some error reporting
					///echo "$message";
					throw new Exception(__CLASS__." ".$message);

					return false;
				}
			}
			/*
			 passed in requirements
			 eg:
			 	$security->is_secure(array('loggedin'=>true))
			 	calls secure_loggedin(true)
			*/
			if(count($options_array)>0){
				foreach($options_array as $option_key=>$option_value){
					$f = 'secure_'.$option_key;
					if(self::$f($option_value) == false){ 
						// print " result ".self::$f($option_value) ;
						throw new Exception(__CLASS__." $option_key failed");
						return false;
					}
				}
			}

			return true;
		}

		public static function secure_loggedin($requirement){
			return (Auth::user_is_logged_in() == $requirement );//return ( isset($_SESSION['id']) == $requirement );

		}

		// check the refering host against the current host or another list of hosts
		public static function secure_referer(){

			if(!isset($_SERVER['HTTP_REFERER'])){ return false; }
			
			$url = parse_url($_SERVER['HTTP_REFERER']);
			$thishost = $_SERVER['HTTP_HOST'];

			if ( preg_match( "/".$url['host']."/", $thishost, $matches) || in_array($url['host'], array_values(self::$valid_hosts)  ) ) {
				return true;
			}

			return false;
		
		}

		public static function security_test2(){
			return true;		
		}
		
		/**
		 * Private constructor so nobody else can instance it
		 */
		private function __construct()
		{
	
		}
	
	}
