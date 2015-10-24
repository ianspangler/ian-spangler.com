<?php
namespace app\Views\Shared;
use Exception;
use StdClass;

// source http://coreymaynard.com/blog/creating-a-restful-api-with-php/
require_once $_SERVER['DOCUMENT_ROOT'] . '/app/lib/vendor/FastRoute/src/bootstrap.php';

abstract class AbstractAPI {
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    protected $uri = '';

    protected $request = '';

    protected $routeInfo = Null;

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';
    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    var $args = Array();
    /**
     * Property: file
     * Stores the input of the PUT request
     
     protected $file = Null;
    */
     protected $format = Null;

     protected $controller = Null;

     protected $dispatcher = Null; 

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request, $config) {
        $this->controller = $config['controller'];
        $this->controller->set_router($this);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = @$_SERVER['REQUEST_URI'];
        $this->request = @$_REQUEST;

        /* calls the child class's set route method */
        $this->set_routes();

        /* set up the routes in the child class */
        $this->routeInfo = $this->dispatcher->dispatch($this->method, $this->uri);

        switch ($this->routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                if(DEBUGGING){ 
                    print "<br />httpmethod ".$this->method;
                    print "<br />uri: ".$this->uri."<br>";
                    var_dump($this->routeInfo);
                }
                $msg = __CLASS__.":".__FUNCTION__." :: Page Not Found.";
                throw new Exception($msg);
                error_log($msg);
                $this->_response($msg, 404);

                break;

            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $this->routeInfo[1];
                // ... 405 Method Not Allowed
                throw new Exception("METHOD_NOT_ALLOWED");
                error_log("405 METHOD_NOT_ALLOWED !");
                $this->_response('Invalid Method', 405);
                
                break;

            case \FastRoute\Dispatcher::FOUND:
                $this->endpoint = $this->routeInfo[1];
                $this->args = $this->routeInfo[2];

                $this->_defaults();

                /* respond with json or default format */
                if($this->_is_json()){
                    header("Content-Type: application/json");
                }

                // ... call $handler with $vars
                // $this->{$this->endpoint}($this->args);
                /* instead of responding, we let the Helper call processAPI */
                break;
        }    
         
    }

    private function _defaults(){
        /*
            sets the values that will be needed to execute the request.
            typically these come in from the REQUEST array. but in some cases like UserHome, one of them will come from SESSION!
        */
        $defaults = $this->set_defaults($this->args);
        $this->format = $defaults['format']; 

        foreach($defaults as $k=>$v){             
            $this->args[$k] = $v;                
        }
    }
  
    protected function set_routes(){
        /* get overwritten by the inheritor */
    }

    private function _is_json(){
        return (isset($this->format) && $this->format == 'json');
    }

    /** 
        Where the user-facing action happens
        Called from the Helper
        Calls the appropriate method in the Controller
     */
    public function processAPI() {
        if ((int)method_exists($this->controller, $this->endpoint) > 0) {
            return $this->_response($this->controller->{$this->endpoint}($this->args)); 
  
        }else if ((int)method_exists($this, $this->endpoint) > 0) {
            // allows us to have Base functions for debugging etc !!!
            return $this->_response($this->{$this->endpoint}($this->args)); 
        }
 
        return $this->_fail("No Endpoint: $this->endpoint", 404);
    }

    /* debug function that can get overwritten by the inheritor */
    private function explain() {       
       if(!DEBUGGING){
            return;
       }

        var_dump( $this->dispatcher );
        exit;
    }

    public function getDetails() {
        return $this;
    }   
    /* does what it says */
    public function get_uri_parts(){
        $parts = parse_url($this->uri); 
        $path_parts = explode("/", $parts['path']);
        $primary_action = @$path_parts[1];
        $secondary_action = @$path_parts[2];
        
        return  array(
                'method' =>  $this->method,
                'uri' => $this->uri, 
                'parts'=>  $parts , 
                'request'=>$this->request,
                'primary_action'=>$primary_action,
                'secondary_action'=>$secondary_action
            );
    }

    public function method(){
        return $this->method;
    }

    public function test_it(){
        return true;
    }

    protected static function array_to_object( $row ){
        if($row == null){ return null; }
        
        $ob = new StdClass();
        
        foreach($row as $k=>$v){
            $ob->$k = $v;
        }
        #$ob->total_count = count($row);
        return $ob;
    }
    
    private function _fail( $msg , $code){
      return  array(
                    'arguments' =>  $this->args,
                    'final_action'=>"", 
                    'user'=> "", 
                    'status'=>'FAIL', 
                    "msg" => $msg
            );
    }

    public function _request_args() {
        return $this->_cleanInputs($_REQUEST);
    }    

    /***************************************************************************/


    public function redirect($location, $status = 301) {
        header("Location: " . $location);
    }    

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

        /* respond with json or default format */
        return ( $this->_is_json() ? json_encode($data) :  $data ) ;
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code) {
        $status = array(  
            200 => 'OK',
            404 => 'Not Found',   
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ); 
        return ($status[$code])?$status[$code]:$status[500]; 
    }
    
}
