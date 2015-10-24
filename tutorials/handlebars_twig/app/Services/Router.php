<?php

namespace app\Services;
use app\Services\Auth\Auth;

class Router {

	public $name;
    /* track the current device */
    public static $device_is_mobile = false;
    private static $redirect_me_to;
    private static $redirect_pages = array(
		'mobile'=>'/?mobile-forward', 
		'desktop'=>'/?desktop-forward' 
		); 

    /* 
    	list of URL derivations that are not alllowed for desktop devices and their redirect targets 
    */
    public static $disallowed_desktop_URIs = array(
    	"about"=>"/privacy" , 
    	"acting-credits"=>"/",
    	"filmmaker-credits"=>"/",
    	"featured-credits"=>"/",
    	"featured-proposals"=>"/proposals"
    );

    /* 
    	list of URL derivations that are allowed for mobile devices 
    */
    public static $allowed_mobile_URIs = array(
		 
 
	);
 
	/* 
    	list of URL derivations that are not alllowed for mobile devices when logged in 
    */
    //updated
    public static $disallowed_mobile_logged_out_URIs = array(
		"addNewMiniProfile.php",
		"casting-proposals",
		"filmmaking-proposals",
		"characters",
		"overview",
		"contact",
		"who",
		"how_it_works",
		"newproposal-form.php",
		"make_proposal.php",
		"post_like_unlike.php",
		"post_dislike_undislike.php",
		"post_comment.php"
    );

    public static $disallowed_mobile_logged_in_URIs = array(
    	"casting-proposals",
		"filmmaking-proposals",
		"characters",
		"overview",
		"contact",
		"who",
		"how_it_works",
		"join-form.php",
    	"login-form.php",
		"login",
		"login.php",
		"login-standard.php",
		"signup-form.php"
		 
    );
    
    public static function is_desktop_prohibited_page($uri){    	
    	# parse URL
		# look for the base folder and see if its in our list
		$parsed = parse_url($uri);
		$parts = explode("/",$parsed['path']);

		# loop through urlparts and try to match against valid requests
		foreach($parts as $k=>$v){
			if(trim($v) == ""){ continue; } if(trim($v) == "includes"){ continue; }
			if(in_array( trim($v), array_keys( self::$disallowed_desktop_URIs) ) ){ 
				self::$redirect_me_to = self::$disallowed_desktop_URIs[trim($v)];
				
				#print " found $v, redirect_me_to=$redirect_me_to ";
				return true; 
			}else{
				#print " no $v ";
			}
		}
		return false; 
    }

    public static function is_mobile_ready_page($uri){
    	
    	if($uri == "/"){ return true; } // home

		# look for the base folder and see if its in our list
		return true; /* updated 1/7 */
		//return self::file_in_url(self::$allowed_mobile_URIs, $uri);
		 
    }
    /*
    called on every page load;
	looks at current url and current device type
	redirects device based on 
	a allowed mobile urls :)
	b disallowed desktop urls :)
	c whether you are loggedin or not :|
	d whether you are looking at a profile  >:{}
    */
	public static function redirect_to_device_page(){
		$my_uri = $_SERVER['REQUEST_URI'];
 
		/*
		Only test against mobile pages if its a mobile device
		Only test against desktop pages if its a desktop device
		*/
		$m = (self::device_is_mobile()) ? 'mobile' : 'desktop' ;
#		print "A";
// prohibit desktops from some pages
		self::desktop_prohibit($m, $my_uri);
#print "B";
		// prohibit logged in user from some pages
		self::mobile_logged_in_prohibit($m, $my_uri);
#print "C";
		// prohibit logged out user from some pages
		self::mobile_logged_out_prohibit($m, $my_uri);
#print "D";		 		
		if(true == self::allow_profile($m, $my_uri)){
			header("Location: " . self::$redirect_pages[$m]."&D&uri=".urlencode( $my_uri));
			exit;
		}

		
		# they have been forwarded to $redirect_pages
		if(preg_match('/[mobile|desktop]-forward/', $my_uri)){
			# self::show_flash('You Bad, You Bad, You Know it, You Know it.');
			# they are mobile and on a non-mobile page	
		
		}elseif($m != 'desktop' && !self::is_mobile_ready_page($my_uri)){				
			#print 'redirecting because '.$_SERVER['REQUEST_URI']. ' not mobile-allowed '.self::is_mobile_ready_page($my_uri);			
			
			header("Location: " . self::$redirect_pages[$m]."&A&uri=".urlencode( $my_uri));
			exit;
		}else{
			#print "===OK==";
		}
		return true;
	}

	private static function allow_profile($m, $my_uri){
		/*
		 special case: story page doesnt have a match, so we can use the script name
		 but what if its a talent page?
		*/
		if($m != 'desktop' && $_SERVER['SCRIPT_NAME'] == '/profiles/profile.php'){
			
			/*
			ENABLED TALENT FOR MOBILE
			*/
			if(preg_match('/roles/', $my_uri)){ 
				return true;
			}else if( (isset( $_REQUEST['type'])?$_REQUEST['type']:"") == "titles" ){ // should allow for stories only
				# stories?
				return false;
			}	
		} 
		return false;
	}

	private static function desktop_prohibit($m, $my_uri){
		# desktop prohibited ?
		if($m == 'desktop' && self::is_desktop_prohibited_page($my_uri) ){
			error_log('Redirect : '.__FUNCTION__);
			#print "prohib $my_uri";
			header("Location: " . self::$redirect_me_to."?redir=1&uri=".urlencode( $my_uri) );
			exit;
		}
	}

	private static function mobile_logged_in_prohibit($m, $uri){
		self::$redirect_me_to = "/";
		if($m == 'mobile' && self::is_mobile_logged_in_disallowed($uri) ){
			error_log('Redirect : '.__FUNCTION__);
			#print "1 prohib $uri";
			header("Location: " . self::$redirect_me_to."?redir=1&B&uri=".urlencode($uri) );
			exit;
		}
	}

	private static function mobile_logged_out_prohibit($m, $uri){
		self::$redirect_me_to = "/";
		if($m == 'mobile' && self::is_mobile_logged_out_disallowed($uri) ){
			error_log('Redirect : '.__FUNCTION__);
			#print "2 prohib $uri";
			header("Location: " . self::$redirect_me_to."?redir=1&C&uri=".urlencode($uri) );
			exit;
		}
	}

	private static function is_mobile_logged_in_disallowed($uri){
		if($uri == "/"){   return false; } // home

		# is logged out, dont run this test
		if(!Auth::user_is_logged_in()){ return false; }
		//if (!isset($_SESSION['id'])) { return false; }
		return self::file_in_url(self::$disallowed_mobile_logged_in_URIs, $uri);
	}

	private static function is_mobile_logged_out_disallowed($uri){
		if($uri == "/"){   return false; } // home

		# is logged in, dont run this test
		if(Auth::user_is_logged_in()){ return false; }
		//if (isset($_SESSION['id'])) { return false; }
		return self::file_in_url(self::$disallowed_mobile_logged_out_URIs, $uri);
	}

	private static function file_in_url($urls, $uri){
		# parse URL
		$parsed = parse_url($uri);
		$parts = explode("/",$parsed['path']);
		# loop through urlparts and try to match against valid requests
		foreach($parts as $k=>$v){
			if(trim($v) == ""){ continue; }
			if(trim($v) == "includes"){ continue; }
			if(in_array( trim($v), $urls ) ){ 
				 #print " I found $v ";
				return true; 
			}else{
				  #print "I NO found $v ";
			}
		} 
		return false;		 		
	}

	public static function is_file_in_url($urls, $file){
		return self::file_in_url($urls, $file);
	}

	public static function get_current_file(){
		$parsed = parse_url($_SERVER['REQUEST_URI']);
		$parts = explode("/",$parsed['path']);
		return $parts[count($parts)-1];
	}

	public static function skip_login_page() {

		$my_uri = $_SERVER['REQUEST_URI'];

		if (preg_match('/login/', $my_uri)) {
			//check if logged in and a referring page for the login form has been set
			if(Auth::user_is_logged_in() && isset($_SESSION['login_referer']) ){ 
			//if (isset($_SESSION['id']) && isset($_SESSION['login_referer'])) { 
				header('Location: '.$_SESSION['login_referer']);
			}
		}
	}

	public static function error_redirect(){
		$m = (self::device_is_mobile()) ? 'mobile' : 'desktop' ;
		//header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
		header('Location: '.self::$redirect_pages[$m]."&r=auth");
	}
	
	public static function show_flash($msg){
		//return; // not working
		print  '<h4 class="flash"> '.$msg.' </h4>  ';
	}

    public static function show_page(){
    	print " <!-- current uri: ". $_SERVER['REQUEST_URI'] ."--> ";
    }

    public static function set_device_is_mobile(){
    	self::$device_is_mobile = true;
    }

    public static function device_is_mobile(){
    	return self::$device_is_mobile;
    }

}
