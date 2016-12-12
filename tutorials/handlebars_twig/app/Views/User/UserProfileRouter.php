<?php

namespace app\Views\User;
use app\Views\Shared\AbstractAPI;

class UserProfileRouter extends AbstractAPI {
    protected $User;

    var $default_action = "activity";
    var $index_action = "index"; 
    var $default_format = "html";
    //var $extra = null;
    var $default_set_num = 1;
    var $required_parameters = array('action','set_num','format');

    public function __construct($request, $config) {
        parent::__construct($request, $config);
        $origin = $config['origin'];

    }

    protected function set_routes(){

        $this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            /* update regex here */
            $BASE = '/users/';
            $ID = '{id:\d+}';
            $SET = '{set_num:\d+}';
            $ACTION = '{action:[activity|following|followers|rr_followers|rr_following]+}{slash:[/]?}';
            //$EXTRA = '{extra:[rr]+}';
            $FORMAT = '{format:[a-z]+}';
            $DEBUG = '{debug:[a-z]+}';

            /** 
            debugging line should be in all routers 
            */
            $r->addRoute('GET', $BASE . '/explain',                                 'explain'); 

            /* add new patterns here */    
            $r->addRoute('GET', $BASE . $ID,                                                'index');       // /users/1
            $r->addRoute('GET', $BASE . $ID . '?{other:[^/]+}',                             'index');       // /users/1? [anything but a slash]
            $r->addRoute('GET', $BASE . $ID . '/',                                          'index');       // /users/1/
            $r->addRoute('GET', $BASE . $ID . '/' . $SET,                                   'index');       // /users/1/1
            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION,                                'get_action');  // /users/1/activity eg.
            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $SET,                   'get_action');  // /users/1/activity/2
            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $FORMAT,                'get_action');  // /users/1/activity/ajax
            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $FORMAT . '/' . $DEBUG, 'get_action');  // /users/1/activity/ajax/debug
            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $SET . '/' . $FORMAT,   'get_action');// /users/1/activity/2/ajax
            // print($BASE . $ID . '/' . $ACTION . '/' . $SET . '/' . $EXTRA . '/' . $FORMAT);
            //exit;
            //$r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $SET . '/' . $EXTRA . '/' . $FORMAT,   'get_action');// /users/1/following/2/rr/ajax

            $r->addRoute('GET', $BASE . $ID . '/' . $ACTION . '/' . $SET . '/' . $FORMAT . '/' . $DEBUG, 'get_action');// /users/1/activity/2/ajax/debug
        
            //print_r($r);
            //exit;
        });

    }

    /* 
        establishes defaults for the req'd parameters
    */
    protected function set_defaults($args){
        $named_args = array();
        $named_args['action'] = $this->index_action;//'index';
        $named_args['set_num'] = $this->default_set_num;
        //$named_args['extra'] = $this->extra;
        $named_args['format'] = $this->default_format;

        foreach($this->required_parameters as $k=>$v){
            if( isset($args[$v]) ){ 
                $named_args[$v] = $args[$v];    
            }
        }
        return $named_args;
    }
   
 }
