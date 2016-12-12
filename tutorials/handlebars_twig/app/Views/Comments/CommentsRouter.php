<?php

namespace app\Views\Comments;
use app\Views\Shared\AbstractAPI;
use app\Services\Auth\Auth;

class CommentsRouter extends AbstractAPI {
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
            $BASE = '/comments';
            $SET = '{set_num:\d+}';
            $ACTION = '{action:[listing|check_status]+}';
            $SUBACTION = '{subaction:[deactivate]+}';
            $ITEMTYPEID ='{itemtype:[1|2|3|4|5]}';  // |proposal_id|credit_id|title_id|talent_id|role_id
            $ID = '{id:\d+}';
            $IDLIST = '{ids:[0-9,]+}';
            $COMMENT_ID = '{comment_id:[0-9]+}';
            $PROFILE_ID = '{profile_id:[0-9]+}';

            $FORMAT = '{format:[a-z]+}';
            $DEBUG = '{debug:[a-z]+}';

            /** 
            debugging line should be in all routers 
            */
            $r->addRoute('GET', $BASE . '/explain',                                 'explain'); 

            /* add new patterns here */    
            $r->addRoute('GET', $BASE . '/index.php',                                 'index'); 
            $r->addRoute('GET', $BASE,                                                'index');       
            $r->addRoute('GET', $BASE . '/',                                          'index');       

            $r->addRoute('GET', $BASE . '/' . $COMMENT_ID,                            'view');            // /comments/4998 

            // this one has to be before #5, #6
            #1
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $PROFILE_ID,   'get_action');  // /comments/list/1/4998 

            #2
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $PROFILE_ID . '/' . $SET,   'get_action');  // /comments/listing/1/2750/2

            #3
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $PROFILE_ID . '/' . $FORMAT,   'get_action');  // /comments/listing/1/2750/ajax

            #4
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $PROFILE_ID . '/' . $SET . '/' . $FORMAT,   'get_action');  // /comments/listing/1/2750/2/ajax

            #5
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $IDLIST,   'get_action');  // /comments/list/1/4998 or /comments/list/2/1,2,3,6,9,11            

            #6
            $r->addRoute('GET', $BASE . '/' . $ACTION . '/'. $ITEMTYPEID . '/' . $IDLIST . '/' . $FORMAT,   'get_action');  // /comments/list/1/4998 or /comments/list/2/1,2,3,6,9,11            

            #----->            
           // if(DEBUGGING) $r->addRoute('GET', $BASE . '/' . $ACTION . '/' . $ID,                   'get_action');  // /activity/2            

            $r->addRoute('POST', $BASE . '/' . $ACTION,                                'get_action');  // /activity eg.
        });

        
    }

    /* 
        establishes defaults for the req'd parameters
    */
    protected function set_defaults($args){
        $named_args = array();
        $named_args['action'] = $this->index_action;//'index';
        $named_args['set_num'] = $this->default_set_num;
        $named_args['format'] = $this->default_format;
        $named_args['id'] = Auth::get_logged_in_user_id();//$_SESSION['id'];

        foreach($this->required_parameters as $k=>$v){
            if( isset($args[$v]) ){ 
                $named_args[$v] = $args[$v];    
            }
        }
        return $named_args;
    }
   
 }
