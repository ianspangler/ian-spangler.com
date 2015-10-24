<?php

namespace app\Config\Chat;
use StdClass;
use Exception;
/*

*/
class ChatConfig{

	private static $config_values;

	public function __construct($config){
		static::$config_values = new StdClass();
		static::_load_config($config);
		//
		//parent::__construct();
		 
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new ChatConfig(array());
		}
		return $inst;
	}

	public static function _load_config($config){
		
		foreach( $config as $name => $value){
			static::set($name, $value);
		}
	}

	public static function get_config(){
		return static::$config_values;
	}
	public static function get($name){
		return static::$config_values->{$name};
	}

	public static function set($name, $value){
		static::$config_values->{$name} = $value;
		return static::$config_values->{$name};	
	}

}


