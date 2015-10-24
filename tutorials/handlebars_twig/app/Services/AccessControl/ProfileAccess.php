<?php  
namespace app\Services\AccessControl;
use StdClass;

class ProfileAccess{

	private $db;
	private $expire_duration;
	private $reason;
	private $access_levels = array('view'=>array(1,2),'edit'=>array(1));
	private $debug = false;
	private $response_object = null;

	/**
		gets db connection
	*/	
	private function init(){		 
		
		global $dbHandler;

		 
		
	}

	public function access($action, $user_id, $profile_object, $is_admin = false){
		$func = "access_".$action;
		if($this->debug) print __CLASS__."::".__FUNCTION__ .": a: $action, ud: $user_id, admin: $is_admin \n" ;

		$this->set_response_value('profile_object', $profile_object); 

		if($is_admin == true){ 
			$this->set_response_value('reason',"IS_ADMIN"); 
			$this->set_response_value('has_access',1); 
			return $this->response_object;
		}	

		return $this->$func($user_id, $profile_object);

	}

	
	private function access_edit($user_id, $profile_object){
			
		if(!$this->is_owner($user_id, $profile_object)) {
			return $this->response_object;
		}

		if(!$this->has_date($profile_object)){ 
			return $this->response_object;
		}

		// for all other users		
		if($this->is_expired($profile_object)){  
			return $this->response_object;
		}
		return $this->response_object;

	}

	private function access_view($user_id, $profile_object){
		// everyone can view
		$this->set_response_value('reason',"NO_RESTRICTION"); 
		$this->set_response_value('has_access',1); 

		return $this->response_object;
	}

	private function is_owner($uid, $p){

		$this->set_response_value('logged_in_as',$uid);

		if($uid != $p['user_id'] || $p['user_id'] == 0){ 
		// apparently admin tool puts in 0 as user value. so if it has a 0, and you've come this far (aka: you are not an admin, you may not edit )
			if($this->debug) print __CLASS__."::".__FUNCTION__ .": loggedin user: $uid, profile owner: ".$p['user_id']." NOT OWNER \n";
		
			$this->set_response_value(__FUNCTION__, 0);
			$this->set_response_value('reason',"IS_NOT_OWNER");
			$this->set_response_value('has_access',0); 

			return false;
		}
			$this->set_response_value(__FUNCTION__, 1);
			$this->set_response_value('reason',"IS_OWNER");
			$this->set_response_value('has_access',1); 
		return true;
	}	

	private function has_date($p){

		if(!isset($p['date_posted'])){
			if($this->debug) print __CLASS__."::".__FUNCTION__ .": Error. Make sure there is a 'date_posted' value in the passed array.\n" ;
			$this->set_response_value(__FUNCTION__, 0);
			$this->set_response_value('reason',"IS_NOT_OBJECT");
			$this->set_response_value('has_access',0); 

			return false;
		}
			$this->set_response_value(__FUNCTION__, 1);
			$this->set_response_value('has_access',1); 

		return true;

	}

	private function is_expired($p){

		$timeFirst  = strtotime( $p['date_posted'] );
		$timeSecond = strtotime( getNow() );
		$differenceInSeconds = $timeSecond - $timeFirst;		 

		if($this->debug) print __CLASS__."::". __FUNCTION__ .": diff: $differenceInSeconds \n";
 
		if( $differenceInSeconds > PROFILE_ACCESS_DURATION ){
			 
			$this->set_response_value(__FUNCTION__, 1);
			$this->set_response_value('reason',"IS_EXPIRED");
			$this->set_response_value('has_access',0); 
		
			return true;
		}
			$this->set_response_value(__FUNCTION__, 0);
			$this->set_response_value('reason',"IS_NOT_EXPIRED");
			$this->set_response_value('has_access',1); 
			
		return false;

	}

	private function set_response_value($name, $value){
		if($this->response_object == null){
			$this->response_object = new StdClass();
		}

		$this->response_object->$name = $value;
			
	}

	public  function fetch_response(){
		if($this->response_object == null){
			$this->response_object = new StdClass();
		}
 
		return $this->response_object;
			
	}
 
	public  function test_it(){
		$this->init();
		return true;
	}

}
