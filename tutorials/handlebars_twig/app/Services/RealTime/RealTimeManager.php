<?php
namespace app\Services\RealTime;

//include the pusher publisher library
require_once $_SERVER['DOCUMENT_ROOT'] .'/app/lib/vendor/Pusher/Pusher.php';

use app\Services\Mailer\Mailer;
use app\Services\UserOnline;

class RealTimeManager{

	private static $app_id =  PUSHER_API_ID;//'114858';
	private static $app_key = PUSHER_API_KEY;//'de32507445454f76582a';
	private static $app_secret = PUSHER_SECRET;//'b483427f698ce44fe460';
	private static $pusher = null;
	private static $prefix = 'pusher_';

	public static function Instance()
		{
			static $inst = null;
			if ($inst === null) {
				$inst = new RealTimeManager();
			}
			return $inst;
		}

	public function __construct(){		
		
		try {
			// Connection creation
			if(!isset(self::$pusher)){ 
				self::$pusher = self::_get_pusher();
			}
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return json_encode(array(
		    	'message' => "Error: ".$e->getMessage(),
		    	'success' => false
			)); 
		}
	}

	/********************* Service Methods ************************/
	 
	/*
		calls pusher auth with channel name
		@returns JSON
	*/		
	public static function auth($socket_id, $channel_name){
		
		self::_start_session(); 
		
		try {
			// Connection creation
			if(!isset(self::$pusher)){ 
				self::$pusher = self::_get_pusher();
			}
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return json_encode(array(
		    	'message' => "Error: ".$e->getMessage(),
		    	'success' => false
			));
			 
		}

		// authenticate private channel user
		if(preg_match('/private-/', $channel_name)){			
			/****************
			** the reason why we print and exit is that PUSHER is expecting a very specific response.
			This is not Ideal, but not terrible either
			*/
			  print self::$pusher->socket_auth($channel_name, $socket_id);
			exit;
			/****************
			** 
			*/
		}

		// authenticate presence channel user
		if(preg_match('/presence-/', $channel_name)){		
			//Any data you want to send about the person who is subscribing
			$presence_data = array(
			    'username' => self::_get_session_var(self::$prefix.'user_name') 
			);

			/****************
			** the reason why we print and exit is that PUSHER is expecting a very specific response.
			This is not Ideal, but not terrible either
			*/
			print self::$pusher->presence_auth(
			    $channel_name, //the name of the channel the user is subscribing to
			    $socket_id, //the socket id received from the Pusher client library
			    self::_get_session_var(self::$prefix.'userid'),  //a UNIQUE USER ID which identifies the user
			    $presence_data //the data about the person
			);
			exit;
			/****************
			** 
			*/
		}	

		return json_encode(array(
	    	'message' => "Error: Non-secure Channel requested",
	    	'success' => false
		));

	}

	/*
		not in use
	*/		
	public static function check_presence($socket_id, $channel_name){
		
	}

	/*
		sends message to pusher for distribution.
		DB interaction has already taken place ( in ChatProfile )
		@returns JSON of the generated HTML
	*/		
	public static function send_message($user_id, $recipient_id, $message, $chat_id, $channel_name){
		global $templateMgr;

		self::_start_session();
		//$_POST['channel_name']
		$channel = self::_get_channel($channel_name);
		
		if(!isset($channel) || ($channel == false) || $channel == ""){
			throw new Exception(__METHOD__." Error: Missing Channel", 1);
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			
			return json_encode(array(
		    	'message' => "Error: Missing Channel",
		    	'success' => false
			)); 
		}

		// Connection creation
		try {
			if(!isset(self::$pusher)){ 
				self::$pusher = self::_get_pusher();
			}
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return json_encode(array(
		    	'message' => "Error Sending Message",
		    	'success' => false
			)); 
		}

		//get the message posted by our ajax call 
		//trim and filter it
		$message = trim(filter_var($message, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
		$message = convert_to_link_in_text($message);

		//wrap it with the user's name when we display
		$user_name = self::_get_session_var('user_fn').' '.@self::_get_session_var('user_ln')[0].'.';
		$user_pic = self::_get_session_var('pic');

		///print("message: ".$message);

		//return template of message
		$message = $templateMgr->load( '/shared/user/chat_message.html', 
								array( 'user_id' => $user_id,
										'user_name'=> $user_name,
										'logged_in_user_id' => self::_get_session_var('id'),
										'user_pic'=>$user_pic,
										'message_text' => $message,
										'timestamp' => date('n/j/y g:i A')
								) 
							);

 
		//trigger the 'new_message' event in our channel, 'presence-nettuts'
		self::$pusher->trigger(
		    $channel, //the channel
		    'new_message', //the event
		    array('message' => $message) //the data to send
		);


		/****************
		** the reason why we print and exit is that PUSHER is expecting a very specific response.
			This is not Ideal, but not terrible either
		*/
		//echo the success array for the ajax call
		print json_encode(array(
		    'message' => $message,
		    'success' => true
		)); 


		//send notification email only if user is not "online"
		if (!UserOnline::is_online($recipient_id)) {
			
			$full_user_name = self::_get_session_var('user_fn').' '.@self::_get_session_var('user_ln');
			
			try {
				Mailer::sendMail("message_notification", array('chat_id'=>$chat_id,'recipient_id'=>$recipient_id,'poster_id'=>$user_id,'poster_name'=>$full_user_name,'poster_pic'=>$user_pic));
			}
			catch (Exception $e) {	
				if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
				error_log($e->getMessage(), 0);
				return json_encode(array(
			    	'message' => "Error Sending Message.".$e->getMessage(),
			    	'success' => false
				)); 
			}

		}

		exit;
		/****************
		**  
		*/

	}

	/*
		sets up session vars for pusher 
		@returns JSON
	*/		
	public static function start_session($username){

		self::_start_session();

		//Get the username sent from the user
		//filter it
		$username = trim(filter_var($username, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));

		//set the username for the session
		self::_set_session_var(self::$prefix.'user_name', $username);

		// set a unique id for the user. since we don't have a working user system, we'll just use the time()
		// variable to generate a unique id, and add the user's name to it and the user's session id, then
		// MD5 the whole thing
		self::_set_session_var(self::$prefix.'userid', md5(time() . '_' . $username . '_' . session_id() ) );
		
		//echo the json_encoded success message for our ajax call
		return json_encode(array('success' => true));
	}

	/*********************************************************************/
	// setter
	private static function _set_session_var($name,$value){
		$_SESSION[$name] = $value;
		return true;
	}
	// getter	
	private static function _get_session_var($name){
		return @$_SESSION[$name];
	}

	// get pretty channel
	private static function _get_channel($post_channel){
		// filter it
		$channel = trim(filter_var($post_channel, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
		// do we need to go to the data base?
		return $channel;
	}

	/*
		does what it says
		@returns PUSHER
	*/		
	private static function _get_pusher(){

		try {
			$pusher = new \Pusher(
			    static::$app_key, //APP KEY
			    static::$app_secret, //APP SECRET
			    static::$app_id //APP ID
			);
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return json_encode(array(
		    	'message' => __METHOD__. " ".$e->getMessage(),
		    	'success' => false
			));
			 
		}

		return $pusher;
	}

	private static function _start_session(){
		//Start the session again so we can access the username and userid
		@session_start();
		return;
	}

}
