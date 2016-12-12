<?php

namespace app\Views\Shared;
use app\Services\Router;
use app\Services\Auth\Auth;
use app\Services\TemplateManager;

/*
	this class has functions used on the home page for logged in users
*/
class HelperBase{

	protected $msg;
	protected $status;
	protected $loggedin_user_id;
	protected $initial_section_list_items = null;
	protected $initial_section_total_count = null; 
	protected $initial_section = null;
	
	public function init(){
		global $response,$parts;

		/* 
			handle the request by showing the appropriate version of the response, per the results of the response
		*/

		/* 
			failure 
		*/
		$this->status =  ((in_array('json', $parts)) ? json_decode($response)->status : $response['status']);
		$this->msg =  ((in_array('json', $parts)) ? json_decode($response)->status : $response['msg']);

		//print_r($response);
		if( $this->status == 'FAIL'){
			$this->output_errors(8, "There has been an error");
			exit;
		}

	}

	public function build($details){
		global $response, $details, $dbHandler;
		/* 
			success, set some globals  
		*/
		$this->loggedin_user_id = (Auth::user_is_logged_in() ? Auth::get_logged_in_user_id() : "" ); //(isset($_SESSION['id']) ? $_SESSION['id'] : "");
		$this->initial_section_list_items = null;
		$this->initial_section_total_count = null; 

		/*
			assign values to the above
		*/ 
		$this->set_initial_values();

		/* 
			derive the function name from the format, default is html
		*/
		$func = $details->args['format']."_response";

		/* 
			oops not so fast. die because we cant handle that format
		*/

		if ((int)method_exists($this, $func) < 1) {
			$this->output_errors(9, "Error: requested function doesnt exist." );
			exit;
		}

		/* 
			make the call to render a response
		*/
		$this->{$func}($response, $details);

		#if(DEBUGGING) print $dbHandler->report_all();

	}

	function ajax_response(){ 	}

	/* default response */
	function html_response($response, $details){ 	}

	function json_response($response, $details){
		header("Content-Type: application/json");
		print_r(json_encode($response));
		exit;		
	}


	function output_errors($code, $mesg){
		global  $parts, $response, $details;

		( (DEBUGGING)? print "\n <br>".__CLASS__." DEBUGGING: Error Status = Fail \n <br>".
			$this->msg." \n <br>".
			$mesg." \n <br>".
			print_r($parts,true)." \n <br>".
			print_r($response,true)." \n <br>".
			print_r($details,true)  : showError($code, $mesg . $this->msg));

	}
}


