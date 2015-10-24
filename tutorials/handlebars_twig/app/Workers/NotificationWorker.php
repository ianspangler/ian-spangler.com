<?php
namespace app\Workers;
use app\Workers\Base\BaseWorker;
use app\Models\Notifications;

class NotificationWorker extends BaseWorker{

	var $debugging = false;
	var $notifier = null;

	function __construct(){

		parent::__construct();
		# Add default server (localhost).
		$this->gmworker->addServer(GEARMAN_SERVER);
		
		$this->gmworker->addFunction(  "do_notifications", array( $this, "do_notifications_func") );

		$this->notifier = new Notifications();

	}

	function init(){
		print __CLASS__." Waiting for job...\n";
		while($this->gmworker->work()){
		  if ($this->gmworker->returnCode() != GEARMAN_SUCCESS){
		    echo "return_code: " . $this->gmworker->returnCode() . "\n";
		    break;
		  }
		}
	}
 

	function do_notifications_func($job){
		
		echo __FUNCTION__." Received job: " . $job->handle() . "\n";

		error_log( __FUNCTION__." DEBUG received job: " . $job->handle() );

		$json_workload = $job->workload();
		$json_workload_size = $job->workloadSize();

		if($this->debugging) echo "Workload: $json_workload ($json_workload_size)\n";
		
		// decode it
		$workload = json_decode($json_workload, true);
		
		if($this->debugging) print "Method to be called is ".$workload['method'] .PHP_EOL;
		if($this->debugging) print "Data is ". print_r($workload['arguments'],true) .PHP_EOL;
		
		 if ((int)method_exists($this->notifier, $workload['method']) < 1) {
			$msg = "Method ".$workload['method']." doesn't exist in class Notifications ";
			#print $msg.PHP_EOL;
			error_log("!".$msg);

			return false;
		}
		
		$result = null;
		try{
			$result = $this->notifier->{$workload['method']}($workload['arguments']);
		}
		catch (Exception $e) {	
			if(DEBUGGING) print __METHOD__. " ".$e->getMessage();	
			error_log(__CLASS__." ".$e->getMessage(), 0);
		}

		print " Complete: $result ".PHP_EOL;
		error_log( __FUNCTION__." DEBUG Complete: $result" );

		return true;


	}
}
