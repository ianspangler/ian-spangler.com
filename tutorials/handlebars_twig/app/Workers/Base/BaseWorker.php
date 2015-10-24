<?php
namespace app\Workers\Base;

class BaseWorker {

	protected $gmworker = null;

	function __construct(){

		try{
			if (!extension_loaded('gearman')) {
            	error_log('Gearman is required');
            	throw new RuntimeException('The PECL::gearman extension is required.');
            	exit;
        	}
        }
        catch	(RuntimeException $e) {
        	error_log(__CLASS__." ".$e->getMessage(), 0);
			print __METHOD__." ".$e->getMessage();
        }

        try{		
			/* check if connection is successful */
			if (!$this->gmworker = new \GearmanWorker()) {
				$error = 'Unable to connect to Gearman.';
            	error_log(__CLASS__.' '.$error);
				throw new Exception($error);
			}
			return true;
			
		}
		catch (Exception $e) {	
			error_log(__CLASS__.' '.$e->getMessage(), 0);
			print __METHOD__." ".$e->getMessage();
			exit;
		}

		$echo = @$this->gmworker->echo(1);			
        if (!$echo) {
            if(DEBUGGING) print __METHOD__. " Failed to connect to Gearman Server";	
            error_log(__CLASS__.' '. 'Failed to connect to Gearman Server.');
            //self::$isAlive = false;
            exit;//return;
        }


	}

}
