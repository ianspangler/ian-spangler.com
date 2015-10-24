<?php

namespace app\Views\Credits;
use app\Views\Shared\AbstractAPI;
use app\Services\Auth\Auth;

class CreditsRouter extends AbstractAPI {
    protected $User;

    var $default_action = "listing";
    var $index_action = "index"; 

    var $default_format = "html";
    var $default_set_num = 1;
    var $required_parameters = array('action','set_num','format');

    public function __construct($request, $config) {
        parent::__construct($request, $config);
        $origin = $config['origin'];

    }

    protected function set_routes(){

        $this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            /* update regex here */
            $BASE = '';
            $SET = '{set_num:\d+}';
            $ACTION = '{action:[/acting\-credits|/filmmaker\-credits|featured\-credits]+}';
            //$SUBACTION = '{subaction:[deactivate]+}';
            $FORMAT = '{format:[a-z]+}';
            $DEBUG = '{debug:[a-z]+}';
            $ID = '{id:\d+}';

             /** 
            debugging line should be in all routers 
            */
            $r->addRoute('GET', $BASE . '/explain',                                 'explain'); 

            /* add new patterns here */    
            $r->addRoute('GET', $BASE,                                               'get_action');       // ''
            $r->addRoute('GET', $BASE . '/index.php',                                'index');       // ''
            $r->addRoute('GET', $BASE. '/' . $DEBUG,                                 'get_action');       // ''

            $r->addRoute('GET', $BASE . '/' . $ACTION . '/',                          'get_action');  /*  /featured-proposals/1/  */
            $r->addRoute('GET', $BASE . '/' . $ACTION,                                'get_action');  /*  /featured-proposals/1  */

            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $SET,                     'get_action');  /*  /featured-proposals/1/  */
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $SET. '/' . $FORMAT,                     'get_action');  /*  /featured-proposals/1/  */

            $r->addRoute('GET', $BASE . '/',                                          'get_action');       // /

            $r->addRoute('GET', $BASE . '/' . $SET. '?{other:[^/]+}',                  'get_action');  // /acting-credits/1? [anything but a slash]
            $r->addRoute('GET', $BASE . '/' . $SET . '/' . $FORMAT,                'get_action');  // /facting-credits/1/ajax
            $r->addRoute('GET', $BASE . '/' . $SET . '/' . $FORMAT . '/' . $DEBUG, 'get_action');  // /facting-credits/1/ajax/debug

        });
    }

    /* 
        establishes defaults for the req'd parameters
    */
    protected function set_defaults($args){
        $named_args = array();
        $named_args['action'] = $this->index_action; 
        $named_args['set_num'] = $this->default_set_num;
        $named_args['format'] = $this->default_format;
        $named_args['id'] = Auth::get_logged_in_user_id(); 

        foreach($this->required_parameters as $k=>$v){
            if( isset($args[$v]) ){ 
                $named_args[$v] = $args[$v];    
            }
        }

        return $named_args;
    }
   
 }
