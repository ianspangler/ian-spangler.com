<?php

namespace app\Controllers\Chat;
use app\Controllers\AbstractController;
use app\Views\Chat\ChatProfile;
use app\Models\Chats;
use app\Services\Router;

/*
    PAGE:
    request new chat 
           with user     (ajax) PAGEOST /chat/new              [userid], [req user]    

    show active chat     (url/ajax) GET /chat/[chat id]

    show chat history    (ajax)     GET /chat/history/[chat id]    
                         (ajax)     GET /chat/history/[chat id]/set/2  

    check for online     (ajax)     GET /chat/presence/[userid] 

    send message:        (ajax)     POST /chat/send             [userid], [chat id], [msg: STRING]     
        
    show list of user's chats (ajax) POST /chat/list        [userid]
        
    block user           (ajax)     POST /chat/block            [userid], [blkd userid]     
        
    unblock user         (ajax)     POST /chat/unblock          [userid], [blkd userid]     

    block user           (ajax)     POST /chat/checkblock        [userid], [blkd userid]     
        
*/

class ChatController extends AbstractController{
    protected static $Chat;
    protected static $User;

    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "view_chat_list";

    var $cp = null;

    public function __construct() {
        // get info from ChatProfile
        $this->cp = new ChatProfile(self::$set_size, self::$set_num);
    }

    /*
    called at beginning of most or all of the action functions
    */
    private function _before($function, $arguments = array()){
        if(isset($arguments['user_id']) && $arguments['user_id'] != $this->args('id') ){
            return $this->_fail(false, $function , "Logged in ID Mis-Match");
        } 

        if($this->requested_action == ""){
            $this->set_requested_action($function);
        }    
        if(!$this->args('id') ){ return $this->_fail(false, $function , "Invalid ID");  }
        
        self::$User = $this->cp->get_a_user((int)$this->args('id'));
        
        if(self::$User == null ){ return $this->_fail(false, $function , "Invalid User");  }
        return 1;
    }

    public function auth(){
        /* You can only create a chat with someone who is not blocked. */ 
        $post_args = $this->router->_request_args();
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ );//, array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }
 
        $socket_id = @$post_args['socket_id'];
        $channel_name = @$post_args['channel_name']; 

        $result = $this->_validate('socket_id', $post_args);
        if($result != 1 ){   return $result ; }

        $result = $this->_validate('channel_name', $post_args);
        if($result != 1 ){   return $result ; }

        // check if either blocked 
        $result = $this->cp->auth($socket_id, $channel_name);

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'result'=>$result,
                "msg" => ""
            );

    }

    /**
        shows a chat view without a message or even a chat id!
        // IN this case we use the chat-id location as the recipient_id
    */

    public function start(){
        /* You can only create a chat with someone who is not blocked. */ 
        $post_args = $this->router->_request_args();
        $user_id = $this->args('id');
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }

        // IN this case we use the chat-id location as the recipient_id
        $recipient_id = @$this->args('chat_id');

        // verify a required variable
        // IN this case we use the chat-id location as the recipient_id
        $result = $this->_validate('chat_id', $this->args());
        if($result != 1 ){   return $result ; }

        // first see if maybe start was called by accident: if a chat exists for these users then forward them to that.
        $chat = $this->cp->get_chat_by_users($user_id, $recipient_id);
        if($chat){
            // forward them to the chat URL
            $add_format = ($this->router->args['format'] == "ajax" ? '/ajax' : "");
            $this->router->redirect('/chat/'.$chat->chat_id.$add_format);
            exit;
        }else{
            ///print "NO CHAT for $user_id, $recipient_id !";
        }

        // verify no funny business
        if($user_id == $recipient_id){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'total_count'=>0,   
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "Cant create with self"
            );
        }

        //check if either blocked 
        $isblocked1 = $this->cp->user_is_blocked($user_id, $recipient_id);
        $isblocked2 = $this->cp->user_is_blocked($recipient_id, $user_id);
        
        if( $isblocked1 == false && $isblocked2 == false){
            #print "OK TO GO";  
        }else{
            // this is the response
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'total_count'=>0, 
                'status'=>'FAIL',   
                'chat'=>false,
                "msg" => "Chat create failed. One of the users is blocked."
            );
        }

        // !! start_empty_chat 
        $recipient = $this->cp->get_a_user((int)$recipient_id);

        // get a list of chats so we can get the unread count 
        $chat_list = $this->cp->get_chat_list($this->args('id'));

         return array(
                'arguments' => $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'recipient'=> $recipient, 
                'total_count'=>0, 
                'total_unread_message_count'=>self::_get_unread_count($chat_list),
                'status'=>'OK',   
                'chat'=>'empty',
                "msg" => "New, non-initialized Chat View. Empty."
            );

    }


    /**
        shows a chat 
    */

    public function view(){

        $post_args = $this->router->_request_args();

        // verify user id is the logged in user
        $user_id = $this->args('id');
        $result = $this->_before(__FUNCTION__, array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }

        // verify a required variable
        $result = $this->_validate('chat_id', $this->args());
        if($result != 1 ){ return $result; }

        // verify user has access
        $result = $this->_validate_user_access((int)$this->args('id'), (int)$this->args('chat_id') );
        if($result != 1 ){ return $result; }

       
        // mark all the messages sent to this user in this chat as read
        $this->cp->update_messages_for_user( (int)$user_id, (int)$this->args('chat_id'));
        
        // now get the chat
        $chat = $this->cp->get_chat($this->args('chat_id'), $user_id);

        // get a list of chats so we can get the unread count 
        $chat_list = $this->cp->get_chat_list($this->args('id'));
       
        return array(
                'arguments' =>  $this->args(),
                'requested_action' => $this->requested_action,  
                'total_count' => (isset($chat[0]->message_count) ? $chat[0]->message_count : 0 ), 
                'total_unread_message_count' => self::_get_unread_count($chat_list),
                'total_conversation_count' => count($chat_list),
                'user' => self::$User, 
                'status' => (($chat == false)?'FAIL':'OK'),   
                'result' => $chat,
                "msg" => ""
        );      

    }



    /**
        create a chat without a message
    */ 
    public function create(){
        /* You can only create a chat with someone who is not blocked. */ 
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }

        $recipient_id = @$post_args['recipient_id'];

        // verify a required variable
        $result = $this->_validate('recipient_id', $post_args);
        if($result != 1 ){   return $result ; }

        // verify no funny business
        if($user_id == $recipient_id){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "Cant create with self"
            );
        }

        //check if either blocked 
        $isblocked1 = $this->cp->user_is_blocked($user_id, $recipient_id);
        $isblocked2 = $this->cp->user_is_blocked($recipient_id, $user_id);
        
        if( $isblocked1 == false && $isblocked2 == false){
            #print "OK TO GO";  
        }else{
            // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'chat'=>false,
                "msg" => "Chat create failed. One of the users is blocked."
            );
        }

        // check for existing chat
        $result = self::$Chat = $this->cp->get_or_create_chat((int)$user_id, (int)$recipient_id); 
        
        // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'chat'=>self::$Chat,
                "msg" => ""
            );
        
    }
    

    /** 
    get all the messages for a chat
    */ 
    public function history() { 

        $post_args = $this->router->_request_args();
        
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }
        $chat_id = @$this->args('chat_id');
        
        // verify a required variable
        $result = $this->_validate('chat_id', $this->args());
        if($result != 1 ){   return $result ; }

        if( $this->args('set_num') !== null ){
            $set_num = $this->args('set_num');
        }
        
        // override with value set in post
        if(isset($post_args['set_num']) ){
            $set_num = $post_args['set_num'];
            $this->router->args['set_num'] = $set_num ;
        }

        // check if user has access        
        $result = $this->_validate_user_access((int)$this->args('id'), (int)$chat_id );
        if($result != 1){ return $result; }

        $history = $this->cp->get_message_list((int)$chat_id, $set_num, array('offset_increment'=>$this->args('offset_increment')));
        
        // get a list of chats so we can get the unread count 
        $chat_list = $this->cp->get_chat_list($this->args('id'));

         // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=> (($history == array())?'FAIL':'OK'),   
                'set_size'=>count($history->messages),
                'total_count'=>$history->total_count,

                'total_unread_message_count' => self::_get_unread_count($chat_list),
                'total_conversation_count' => count($chat_list),

                'history'=>$history->messages,
                "msg" => (($history->messages == array())?'Chat does not exist or is empty':'success')
        );
        
    }

    /**
        call the Pusher functionality
    */
    public function start_session(){
        /* You can only create a chat with someone who is not blocked. */ 
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__, array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }
        
        $username = @$post_args['username'];
        $recipient_id = @$post_args['recipient_id']; 

        // verify a required variable
        $result = $this->_validate('username', $post_args);
        if($result != 1 ){   return $result ; }
        // verify a required variable
        $result = $this->_validate('recipient_id', $post_args);
        if($result != 1 ){   return $result ; }

        // verify no funny business
        if($user_id == $recipient_id){
            // start_session always requested over ajax, expects json
            $this->router->args['format'] = 'json';
                return
                    array(
                    'arguments' =>  $this->args(),
                    'requested_action'=>$this->requested_action,  
                    //'user'=> self::$User, 
                    'status'=>'OK',   
                    'result'=>"FAIL",
                    "msg" => "Can't start with self: ".print_r($post_args, true)
                ) ;
        } 

        // check if either blocked 
        $result = $this->cp->start_session($username); 
        //check if either blocked 
        $isblocked1 = $this->cp->user_is_blocked($user_id, $recipient_id);
        $isblocked2 = $this->cp->user_is_blocked($recipient_id, $user_id);

        if( $isblocked1 == false && $isblocked2 == false){
            #print "OK TO GO";  
        }else{
            // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'chat'=>false,
                'result'=>'FAIL',
                "msg" => "Chat create failed. One of the users is blocked."
            );
        }

        // check/get for existing chat
        $result = self::$Chat = $this->cp->get_or_create_chat((int)$user_id, (int)$recipient_id); 
        // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'chat'=>self::$Chat,
                'result'=>$result,
                "msg" => "OK"
            ); 
    }
    /**
    NOT REALLY IN USE YET
    */
    public function presence(){
        $post_args = $this->router->_request_args();
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        print __FUNCTION__.PHP_EOL;

        $is_online = $this->cp->check_presence($post_args['user_id']);
         // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'online'=>$is_online,
                "msg" => ""
            );
        
    }
    
    /**
        send a message!
        gets or creates a chat if needed
    */
    public function send_message(){
        /* You can only create a chat with someone who is not blocked. */ 
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        $channel_name = @$post_args['channel'];

        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }
        
       
        // verify required value
        $result = $this->_validate('recipient_id', $post_args);
        if($result != 1 ){   return $result ; }

        // verify required value
        $result = $this->_validate('message', $post_args);
        if($result != 1 ){   return $result ; } 

        // verify required value
        $result = $this->_validate('channel', $post_args);
        if($result != 1 ){   return $result ; }

        /** 
            no need to check if user has access,
            becuase  cp->send_message will get or create a new one
        */       

        
        $result = $this->cp->send_message($user_id, $post_args['recipient_id'], @$post_args['chat_id'], $channel_name, $post_args['message']);
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>(($result == false)?'FAIL':'OK'),   
                'result'=>$result,
                "msg" => ""
        );

    }
    
    /** 
        index maps to this
        gets an array of chats
    */
    public function view_chat_list($args = array(), $target_chat = array()){
       
        // verify user id is the logged in user 
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        // get list of chats
        $chat_list = $this->cp->get_chat_list($this->args('id'));
 
        // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'total_count'=>count($chat_list),
                'total_conversation_count' => count($chat_list),
                'total_unread_message_count'=>self::_get_unread_count($chat_list),
                'user'=> self::$User, 
                'status'=>'OK', 
                'target_chat'=>$target_chat, //used for desktop chat pop-up
                'chat_list'=>$chat_list,
                "msg" => ""
            );
    } 


    // mask one function to another
    public function chat_list(){
        return self::view_chat_list(func_get_args());
    } 
    

    public function block(){
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }

        $result = $this->_validate('blockable_user_id', $post_args);
        if($result != 1 ){   return $result ; }
        
        if($user_id == $post_args['blockable_user_id']){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "Cant block self"
            );
        }

        $result = $this->cp->block_user($user_id, $post_args['blockable_user_id']);
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>(($result == false)?'FAIL':'OK'),   
                'result'=>"BLOCKED",
                "msg" => ""
            );

    }
    
    public function unblock(){
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }

        $result = $this->_validate('blockable_user_id', $post_args);
        if($result != 1 ){   return $result ; }

       if($user_id == $post_args['blockable_user_id']){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "Cant block self"
            );
        }
            
        $result = $this->cp->unblock_user($user_id, $post_args['blockable_user_id']);
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>(($result == false)?'FAIL':'OK'),   
                'result'=>"UNBLOCKED",
                "msg" => ""
            );
    }
    
    public function checkblock(){
        $post_args = $this->router->_request_args();
        $user_id = @$post_args['user_id'];
        // verify user id is the logged in user
        $result = $this->_before(__FUNCTION__ , array('user_id'=>$user_id));
        if($result != 1 ){   return $result ; }
        
        $result = $this->_validate('blockable_user_id', $post_args);
        if($result != 1 ){   return $result ; }

       if($user_id == $post_args['blockable_user_id']){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "Cant check block on self"
            );
        }
            
        $result = $this->cp->user_is_blocked($user_id, $post_args['blockable_user_id']);
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'OK',   
                'result'=>(($result == false)?'NOTBLOCKED':'BLOCKED'),
                "msg" => ""
            );
    }

    /***** Private methods *****/

    private function _get_unread_count($chat_list){
        $total = 0;
        foreach($chat_list as $chat){
            if(isset($chat->unread_count)){
                $total += $chat->unread_count;
            }
        }
        return $total;
    }

    private function _validate_user_access($_user_id, $_chat_id){
    
        // prevent logged in user from seeing chats that dont belong to her
        $result = $this->cp->verify_chat_participant((int)$_user_id, (int)$_chat_id );        


        if($result == false){
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "You are not a member of this chat!"
            );
        }
        return 1;
    }

    private function _validate($varname, $location){
        if(!isset($location[$varname]) || $location[$varname]==""){

            return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,  
                'user'=> self::$User, 
                'status'=>'FAIL',   
                'result'=>"",
                "msg" => "No $varname sent"
            );
        }
        return 1;
    } 


}
