<?php
// ConfigReader.php

namespace app\Services\Conf;

class ConfigReader {
	#var $conf = array();

	public function __construct() {
		// open file, load data
		if( defined( 'DOCROOT' ) ){

			$file = DOCROOT.basename($_SERVER['DOCUMENT_ROOT']).'.env'.'/environment.json';

		}else{

			$file = $_SERVER['DOCUMENT_ROOT'] .'/../'.basename($_SERVER['DOCUMENT_ROOT']).'.env'.'/environment.json';

		}
		
		if(!file_exists($file)){ echo "Environment file not found. $file"; die; } 

		$json = json_decode(file_get_contents($file),true);
		 
		foreach($json as $k=>$v){
			$this->$k = $v;
		}
		return;
	}

	public function get($v){
		if(!isset($this->$v)){ return false; }
		return $this->$v;
	}

}
