<?php  

namespace app\Services;
use app\Models\Notifications; ///for testing
use Exception;
use GearmanException;

class GearManager {

	private static $gearman;
	private static $isAlive = false;

	/**
	* initializes the cache object
	*/	
	public static function init(){		 

		try {
			// Connection creation
			if(!self::$gearman = new \GearmanClient()){
				throw new Exception("Error Starting up Gearman", 1);
			}
		//self::$gearman->setExceptionCallback (  )

		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return;
		}
		

		try{
		    self::$gearman->addServer();
		}catch (\GearmanException $e) {
		   //if(DEBUGGING) print __METHOD__. " Failed to connect to Gearman Server";	
            error_log( __METHOD__.' Failed to connect to Gearman Server.');
            self::$isAlive = false; 
            return;
		}

		try {
			@self::$gearman->addServer(GEARMAN_SERVER);
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log($e->getMessage(), 0);
			return;
		} 
		self::$isAlive = true;
	}

	public function _fail(){
		print "this is the fail function";
	}
	

	/**
	* this method adds a new process by the method name and args given
	*/
	public static function process($method_name, $args) {
		
		error_log(__METHOD__." DEBUG in process");

		if(!self::$gearman){ error_log(__METHOD__." ERR nogearman"); return false; }
		if(!self::$isAlive){ error_log(__METHOD__." ERR not alive"); return false; }


		// self::$gearman->doBackground('junk', json_encode( array('method'=>$method_name, 'arguments'=>$args) ) );
		error_log(__METHOD__." DEBUG calling do_notifications...".$method_name);

		$job_handle = self::$gearman->doBackground('do_notifications', json_encode( array('method'=>$method_name, 'arguments'=>$args) ) );
		
		if (self::$gearman->returnCode() != GEARMAN_SUCCESS){
		  error_log(__METHOD__. "bad return code",1);
		  return false;
		  //exit;
		}else{
			error_log(__METHOD__. "GOOD return code job_handle = $job_handle",1);
		}

		/*$done = false;
		do {
		   ///sleep(3);
		   $stat = self::$gearman->jobStatus($job_handle);
		   if (!$stat[0]) // the job is known so it is not done
		      $done = true;
		   error_log(__METHOD__. "Running: " . ($stat[1] ? "true" : "false") . ", numerator: " . $stat[2] . ", denomintor: " . $stat[3] ,1);
		}
		while(!$done);*/

		//error_log(__METHOD__. "done!",1);

		return true;

		//TESTING
		//$notifier = new Notifications();
		//$notifier->notify_on_follower_inserted($args);
		//$notifier->notify_on_proposal_inserted($args);
		//$notifier->notify_on_like_inserted($args);
		///$notifier->notify_on_comment_inserted($args);

	}


	/**
	* this method kills a process by the method given
	*/
	public static function kill($method_name, $args) {

		
	}

	/**
	* this method kills all processes currently running or in the queue
	*/
	public static function killAll() {

		
	}


}
