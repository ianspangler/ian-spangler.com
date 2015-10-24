<?php

namespace app\Controllers;
use Exception;
use StdClass;

abstract class AbstractController {
 
    protected $default_action = '';
    protected $requested_action = '';
    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct() {
 
    }
   
    /*
        default endpoint
    */
    public function index(){
        //defined requested action
        $this->requested_action = $this->default_action;

        $f = $this->default_action;
        return $this->$f( func_get_args() );
    }

    public function get_action(){ 
        //print_r(func_get_args());
       // exit;
        $action = func_get_args()[0]['action'];
        //$extra = func_get_args()[0]['extra'];

        #print_r(func_get_args());

        //defined requested action
        $this->requested_action = $action;
        //$this->extra = $extra;
        
        //define final action
        $f = $this->_clean($action);
        if ((int)method_exists($this, $f) > 0) {
            return $this->$f(func_get_args());
        }    
        $msg = __CLASS__." Bad Request Exception"; 
        print($f);
        throw new Exception($msg);
        error_log( $msg );
        return $msg;
    }

    public function set_requested_action($action){
        $this->requested_action = $action;

    }
    private function _clean($action) {
        return preg_replace('/-/','_', $action);
    }

    public function testing(){
        print "======OK=====";
        exit;
    }

    /**
     *  an Endpoint
     */
    public function test() {
        if ($this->method() == 'GET') {
            return 'Request=' . $this->args(0) . ' / action='.__FUNCTION__.' / response=' . 'OK' ;
        } else {
            throw new Exception(__CLASS__." Bad Request Exception");

            return "Bad Request";
        }
    }

    
    /*
        parses everything after the ? -- the Querystring
    */
    protected function _get_qs_vars(){
        if( null === $this->args('other') ){ return null ;}
       
        $parts = explode("&",$this->args('other'));
        $vars = new StdClass();
        foreach($parts as $p){
           @list($key, $val) = @explode("=",$p); 
            $vars->{$key} = $val;
        }
        return $vars;
    }

    protected function args($idx = null){
        if($idx == null){ return $this->router->args ;}
        return @$this->router->args[$idx];
    }

    protected function method(){
        return $this->router->method();
    }
    
    public function set_router($router){
        $this->router = $router;
    }

    public function set_size(){
        return static::$set_size;
    }

    protected function _fail($user, $func, $msg = ""){
        $msg = ( ($msg !="") ? $msg : "Unknown Cause of Failure");
        return  array(
            'arguments' =>  $this->args(),
            'final_action' => $func, 
            'user' => $user, 
            'status' => 'FAIL', 
            "msg" => $msg
        );
    }

}
