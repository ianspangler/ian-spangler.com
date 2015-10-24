<?php

namespace app\Views\Chat;
use app\Views\Shared\AbstractAPI;
use app\Services\Auth\Auth;

class ChatRouter extends AbstractAPI {
    protected $User;

    var $default_action = "view_chat_list";
    var $index_action = "index"; 

    var $default_format = "html";
    var $default_set_num = 1;
    var $required_parameters = array('action','set_num','format');

    public function __construct($request, $config) {

        parent::__construct($request, $config);
        $origin = $config['origin'];

    }
/*
    PAGE:
    request new chat 
           with user     (ajax)     POST /chat/create              [userid], [req user]    

    show active chat     (url/ajax) GET /chat/[chat id]

    show chat history    (ajax)     GET /chat/history/[chat id]    
                         (ajax)     GET /chat/history/[chat id]/set/2  

    check for online     (ajax)     GET /chat/presence/[userid] 

    send message:        (ajax)     POST /chat/send             [userid], [chat id], [msg: STRING]     
        
    show list of user's chats (ajax) POST /chat/chat_list        [userid]
        
    block user           (ajax)     POST /chat/block            [userid], [blkd userid]     
        
    unblock user         (ajax)     POST /chat/unblock          [userid], [blkd userid]     

    block user           (ajax)     POST /chat/checkblock        [userid], [blkd userid]     
        
*/
    protected function set_routes(){
 
    
        $this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
            /* update regex here */
            $BASE = '/chat{slash:[/]?}';
            $ID = '{chat_id:\d+}';
            $SET = '{set_num:\d+}';
            $GET_ACTION = '{action:[view|presence|start|history|chat_list]+}'; 
            $POST_ACTION = '{action:[auth|start_session|start|view|presence|send_message|chat_list|block|unblock|checkblock]+}';
            $SUBACTION = '{subaction:[set]+}';
            $FORMAT = '{format:[a-z]+}';
            $DEBUG = '{debug:[debug]+}';
            $OFFSET = '{offset:[offset]+}';
            $OFFSETNUM = '{offset_increment:\d+}';

            /** 
            debugging line should be in all routers 
            */
            $r->addRoute('GET', $BASE . '/explain',                                 'explain'); 

            /* add new patterns here */    

            $r->addRoute('GET', $BASE. '/index.php',                                  'index');   // ''
            //$r->addRoute('GET', $BASE. '/',                                           'index');   // /chat
            $r->addRoute('GET', $BASE,                                                'index');   // /chat
            $r->addRoute('GET', $BASE . '?{other:[^/]+}',                             'index');   // /chat/1? [anything but a slash]
            $r->addRoute('GET', $BASE . '/' .  $ID,                                     'view');    // /chat/11212
            
            $r->addRoute('GET', $BASE . '/' .  $ID . '/' . $FORMAT,                 'view');    // /chat/11212/ajax

            $r->addRoute('GET', $BASE . '/' .  $ID. '/' .  $GET_ACTION,               'get_action');    // /chat/11212

            $r->addRoute('GET', $BASE . '/' .  $GET_ACTION,                             'get_action');      // /chat/DOSOMETHING
            $r->addRoute('GET', $BASE . '/' .  $GET_ACTION  . '/' .  $ID,            'get_action');      // /chat/DOSOMETHING/NUMBER
            $r->addRoute('GET', $BASE . '/' .  $GET_ACTION  . '/' .  $ID .   '/' .  $SUBACTION . '/' . $SET . '/' . $FORMAT , 'get_action');       // eg:  /chat/history/[chat_id]/set/[set_num]/ajax
            $r->addRoute('GET', $BASE . '/' .  $GET_ACTION  . '/' .  $ID .   '/' .  $SUBACTION . '/' . $SET . '/' . $FORMAT . '/' . $OFFSET . '/'. $OFFSETNUM, 'get_action');       // eg:  /chat/history/[chat_id]/set/[set_num]/ajax/offset/NUMBER
 
            // these are only accessed in POST
            $r->addRoute('POST', $BASE . '/' .  $ID,                                  'view');   // /chat/11212
            $r->addRoute('POST', $BASE . '/' .  $ID . '/' . $FORMAT,                 'view');    // /chat/11212/ajax
            $r->addRoute('POST', $BASE . '/' .  $POST_ACTION,                         'get_action');  // /chat/DOSOMETHING
            $r->addRoute('POST', $BASE . '/' .  $POST_ACTION  . '/' .$FORMAT ,     'get_action');  // /chat/DOSOMETHING
            $r->addRoute('POST', $BASE . '/' .  $POST_ACTION  . '/' .$ID,          'get_action');  // /chat/DOSOMETHING/NUMBER

            $r->addRoute('POST', $BASE . '/' .  $POST_ACTION  . '/' .$ID. '/' .$FORMAT,          'get_action');  // /chat/DOSOMETHING/NUMBER/FORMAT

            $r->addRoute('POST', $BASE . '/' .  $POST_ACTION  . '/' .$ID   .       '/' .         $SUBACTION . '/' .    $SET,     'get_action');       // /chat/DOSOMETHING/NUMBER/set/setnumber
            
            // for debugging             
            if(DEBUGGING){
                $r->addRoute('GET', $BASE . '/' .  $ID .  '/' . $FORMAT . '/' . $DEBUG, 'get_action');       // eg:  /chat/history/[chat_id]/ajax/debug

                $r->addRoute('GET', $BASE . '/' . $GET_ACTION . '/' .  $ID .  '/' .  $SUBACTION . '/' . $SET . '/' . $FORMAT . '/' . $DEBUG, 'get_action');       // eg:  /chat/history/[chat_id]/set/[set_num]/ajax/debug

                $r->addRoute('GET', $BASE . '/' . $GET_ACTION  . '/' . $ID . '/' . $FORMAT . '/' . $DEBUG, 'get_action'); // /chat/ACTION/ID/ajax/debug 
                $r->addRoute('GET', $BASE . '/' . $POST_ACTION . '/' . $ID . '/' . $FORMAT . '/' . $DEBUG, 'get_action'); // /chat/ACTION/ID/ajax/debug 
                $r->addRoute('GET', $BASE . '/' . $POST_ACTION . '/' . $ID, 'get_action'); // /chat/ACTION/ID 
                $r->addRoute('GET', $BASE . '/' . $POST_ACTION . '/' . $ID . '/' . $SUBACTION . '/' .    $SET, 'get_action');// /chat/DOSOMETHING/NUMBER/set/setnumber
            } 

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

            
        /*****HARCODE THE ID TO THE LOGGED IN USER******************
        *** */
        $named_args['id'] = Auth::get_logged_in_user_id();//$_SESSION['id'];
        /****
        *************************/

        foreach($this->required_parameters as $k=>$v){
            if( isset($args[$v]) ){ 
                $named_args[$v] = $args[$v];    
            }
        }
        if(isset($args['subaction'])){
            $named_args['subaction'] = $args['subaction'];    
        }

        return $named_args;
    }
   
 }
