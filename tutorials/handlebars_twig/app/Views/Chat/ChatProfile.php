<?php

namespace app\Views\Chat;
use StdClass;
use Exception;

use app\Views\Shared\AbstractProfile;

use app\Services\RealTime\RealTimeManager;
use app\Services\UserOnline;

use app\Models\Users;
use app\Models\Chats;
use app\Models\Messages;
use app\Models\User_Blocks;
use app\Services\Auth\Auth;

use shared\Services\CacheClearer;

class ChatProfile extends AbstractProfile{
    /*
        
    */
    protected static $cache_key_prefix = 'chat_';


    public function __construct($set_size = 30, $set_num = 1){

        self::set_size($set_size);
        self::set_num($set_num);

        /*
            create the models that will be needed for this profile
        */
        $config = array(
            'set_size'=>self::set_size()
        );
        /*
            lets name all our DB based vars
            []_model to make it more clear
        */

        self::$users_model = new Users($config);

        parent::__construct();

    }
    
    public static function auth($_socketid, $_channelid){
        return RealTimeManager::auth($_socketid, $_channelid);
    }

    public static function start_session($_username){
        return RealTimeManager::start_session($_username);
    }

    /*
        get a user by id or (FUTURE) url_handle
    */
    public static function get_a_user($_mixed){
        self::$user = self::$users_model->get_result((int)$_mixed, array(), array(
            "what"=>array(
                "user_id",
                "last_name",
                "first_name",
                "username",
                "email",
                "oauth_provider",
                "oauth_uid",
                "main_pic", 
                "main_pic_geom"
                )  
        ) );//, self::$related_data);
        if(self::$user == null){ return null; }
        self::$user->pic = self::_get_user_image( self::$users_model->object_to_array(self::$user) );

        return self::$user;
    }

    /*
        get a list of chats
    */
    public static function get_chat_list($_user_id){
        if((int)$_user_id < 1){ return null;}
        $chats = Chats::get_result((int)$_user_id );
        if(!$chats){  return array(); }
      #print_r($chats[0]);
        // get the excerpt for each chat
        $chats = self::_filter_blocked_chats($chats);
       
        // add user data to each chat
        $chats = self::_get_related_user_data($chats, true);

        /**
            add an array of users. 
            @TODO: clean up/remove user_1, user_2, since we have this array and self, other
        */
        foreach($chats as $c){
           if(!isset($c->user_1)){
                #print "Missing User_id_1";
                #print_r($c);
           }
           else if(!isset($c->user_2)){
                #print "Missing User_id_2";
                #print_r($c);
            
           }else{
               $c->users = array($c->user_1, $c->user_2);
           }
        }

         // add the count to the result
        $chats = self::_get_chats_unread_count($chats, $_user_id);

        // convert user_1 , user_2 to self, other
        $chats = self::_convert_to_selfother($chats, $_user_id);
        
        // get the excerpt for each chat
        $chats = self::_get_chats_excerpt($chats);
        
        // format the dates for the chats and messages
        $chats = self::_get_formatted_dates_for_chat($chats);
                
        return $chats;
    }

    /*
        get a list of messages for a chat
    */
    public static function get_message_list($_chat_id, $set_num = 1, $options = array()){ 
        $total_count = 0;
        $history = new StdClass();
        $history->total_count = $total_count;
        $history->messages = array();

        if(isset($options['offset_increment'])){
            Messages::set_offset_increment($options['offset_increment']);
        }
        // ensure the model knows which set to get
        Messages::set_num($set_num);

        if((int)$_chat_id < 1){ return $history; }
        
        $messages = Messages::get_result((int)$_chat_id, array(), array('set_num'=>(int)$set_num) );
        
        if(isset($messages[0])){
            $total_count = $messages[0]->total_count;
            $history->total_count = $total_count;
        }  
        
        // if there were no messages ( in the case of a new chat )
        if(!is_array($messages) || count($messages) < 1){ return $history; }

        $messages = self::_get_related_user_data($messages);
        
        // convert user_1 , user_2 to self, other
        // $messages = self::_convert_to_selfother($messages, $_user_id);

        // format the dates for the chats and messages
        $messages = self::_get_formatted_dates_for_message($messages);
        
        self::message_to_url($messages);
        
        if(!$messages){ return $history; }
        $history->messages = $messages;
        return $history;

    }

    public static function message_to_url($messages){
        foreach($messages as $m){
            $m->body = convert_to_link_in_text($m->body);
        } 
    }
    /*
        get chat details
    */
    public static function get_chat($_chat_id, $_user_id = ""){

        if((int)$_chat_id < 1){ return null;}

        // need to tell the BaseModel::Get to use the alt db
        $chats = Chats::get((int)$_chat_id, array('alt_db'=>true) );
        if(!$chats || (count($chats) < 1)){
            return array();
        } 

        if($_user_id != ""){
            $chats = self::_get_chats_unread_count(array(Chats::array_to_object($chats[0])), $_user_id);
        } 
        if($_user_id != ""){
            $chats = self::_get_chat_message_count(array(Chats::array_to_object($chats[0])), $_user_id);
        } 

        $new_chats = array();
        foreach($chats as $c){

            $user1 = new StdClass();
            $user2 = new StdClass();
            
            $user1->user_id = $c->user_id_1;
            $user2->user_id = $c->user_id_2;
            
            $users = self::_get_related_user_data(
                array($user1, $user2)
                );
            $c->users = $users;

           $new_chats[] = $c;
        } 

        // convert user_1 , user_2 to self, other
        $new_chats = self::_convert_to_selfother($new_chats, $_user_id);

        return $new_chats;
    }

    /* 
        update all the messages for a user in a chat to be 'read'
    */
    public static function update_messages_for_user($_user_id, $_chat_id){

        $result =  Messages::update_chat_messages((int)$_user_id, (int)$_chat_id); 
        //print PHP_EOL."RESULT: $result";
        return $result;
    }
    /* 
        takes three optional arguments and will create a chat id if one is not found
    */
    public static function get_or_create_chat($user_id = "", $recipient_id = "", $_chat_id = ""){

        // first check to see if theres an existing chat for these 2 people
        if($user_id != "" && $recipient_id != ""){
            $result = self::get_chat_by_users((int)$user_id, (int)$recipient_id) ; 
        }

        if(isset($result) && $result != false && count($result) > 0){ 
            return $result;
        }else{ 
            // if not check to see if the chat id supllied is valid
            if((int)$_chat_id > 0 ){  
                return self::get_chat((int)$_chat_id, $user_id); 
            }else{             
                if(isset($result) && $result != false && count($result) > 0 ){
                    // if so use that one 
                    return $result;
                }else{
                    // if not create a new one
                    //clear the chats list cache
                    CacheClearer::clear_cache_for_chats_list((int)$user_id);

                    return Chats::create((int)$user_id, (int)$recipient_id); 
                } 
            }
        }
        return false;

    }
    /*
        use the users' ids to get a chat out of the db
    */
    public static function get_chat_by_users($user_id, $recipient_id){

        $result = Chats::get_a_chat((int)$user_id, (int)$recipient_id) ; 
        if(count($result) > 0){ 
            return $result;
        }  
        return false;
    }

    /* 
        create a message on a new or existing chat
    */
    public static function send_message($user_id, $recipient_id, $chat_id = "", $channel_name, $message){

        $Chat = null; 
        $Chat = self::get_or_create_chat($user_id, $recipient_id, $chat_id);

        $insert_result = Messages::insert_a_message(
            array(
            'body'=>$message, 
            'user_id'=>$user_id, 
            'recipient_id'=>$recipient_id,  
            'chat_id'=>$Chat->chat_id
            ), array('alt_db'=>true)
        );

        if($insert_result){
            // if the db insert fails, do we still want to send the message to the recipient?
 
            // update the chat with the date of the message
            $updated_at = $insert_result->updated_at;
            $result = Chats::update( array("chat_id"=>$Chat->chat_id), array(
                'updated_at'=>$updated_at,
            )); 
        }

        try {
            // send the message to the recipient
            RealTimeManager::send_message($user_id, $recipient_id, $message, $chat_id, $channel_name);
        }
        catch (Exception $e) {  
            if(DEBUGGING) print __METHOD__. " ".$e->getMessage();   
            error_log($e->getMessage(), 0);
            return false;
        }

        return $result;

    }

    public static function block_user($_user_id, $_blockable_user_id){        
        // need the alt_db option because this method uses the _handle_db_query. not optimal implementation
        return User_Blocks::block_user((int)$_user_id, (int)$_blockable_user_id, array('alt_db'=>true));
    }

    public static function unblock_user($_user_id, $_blockable_user_id){
        // need the alt_db option because this method uses the _handle_db_query. not optimal implementation
        return User_Blocks::unblock_user((int)$_user_id, (int)$_blockable_user_id, array('alt_db'=>true));    
    }

    public static function user_is_blocked($_user_id, $_blockable_user_id){
        // need the alt_db option because this method uses the _handle_db_query. not optimal implementation
        return User_Blocks::is_blocked((int)$_user_id, (int)$_blockable_user_id, array('alt_db'=>true));    
    }    

    /* 
        not so much in use
    */
    public static function check_presence($_user_id){
        return  RealTimeManager::check_presence((int)$_user_id);
    }

    /* 
        get a chat based on the user id and the known chat id, return true if the user is in there at all
    */
    public function verify_chat_participant($_user_id, $_chat_id){
        /**
            THIS IS WHERE WE MIGHT ADD THE ABILITY FOR ADMINS TO SEE CHATS
        **/
        $chat = self::get_chat((int)$_chat_id,$_user_id);
 
        if(count($chat) < 1){ 
            return false; 
        } 
        $chat = $chat[0];

        if(((int)$chat->user_id_1 == (int)$_user_id) || ((int)$chat->user_id_2 == (int)$_user_id)){
            return true;
        }
        return false;

    }
    /*****************************************************************/
   
   /*
        filter out chats where there is a user blocked
   */
    private static function _filter_blocked_chats($chats){
        $new_chats = array();
        foreach($chats as $chat){
            if( !self::user_is_blocked($chat->user_id_1, $chat->user_id_2) && !self::user_is_blocked($chat->user_id_2, $chat->user_id_1) ){
                array_push($new_chats, $chat);
            }
        }
        return $new_chats;
    }

    /*
        for easier use; copy values into a simpler name
        @TODO: remove the users array and the user_1, user_two 
    */
    private static function _convert_to_selfother($new_chats, $_user_id){
        // convert user_1 , user_2 to self, other
        foreach($new_chats as $chat){ 
            if($chat->user_id_1 == $_user_id){
                $chat->self = self::_find_user_object($chat->user_id_1, $chat->users);//$chat->user_id_1; 
                $chat->other = self::_find_user_object($chat->user_id_2, $chat->users);//$chat->user_id_2; 
            }else if($chat->user_id_2 == $_user_id){
                $chat->self = self::_find_user_object($chat->user_id_2, $chat->users);;//$chat->user_id_2; 
                $chat->other = self::_find_user_object($chat->user_id_1, $chat->users);//$chat->user_id_1; 
            }
        }
        return $new_chats;
    }

    /* 
        a little search
    */
    private static function _find_user_object($user_id, $users){
        foreach($users as $u){
            if($u->user_id == $user_id){ return $u; }
        }
        return ;
    }

    /* 
        make a call to the global function
    */
    private static function _get_user_image($_user_object){
        return get_user_profile_image($_user_object['user_id'], $_user_object['main_pic'], $_user_object['oauth_uid']);
    }

    /* 
        make a call to the messages model to get the number of messages in a each of the chats
    */
    private static function _get_chat_message_count($chats, $_user_id){
        $chat_ids = array();
         
        foreach($chats as $m=>$data){
            array_push($chat_ids, $data->chat_id);
        }
        
        $messagedata = Messages::get_list(array(
            "alt_db" =>true,
            "where"=>array(
                array(
                    "name"=>"chat_id",
                    "operator"=>"IN",
                    "value"=>$chat_ids
                ) 
            ),
             "what"=>array(
                "count(chat_id) as message_count",
                "chat_id"
                ),
             
             "groupby"=>"chat_id"

            ) 
        );
                 
        foreach($chats as $m=>$data){
            foreach($messagedata as $m){
                if($data->chat_id == $m['chat_id']){
                    $data->message_count = $m['message_count'];
                } 
            }
        }
        
        return $chats;

    }

    /* 
        make a call to the messages model to get the unread count in each of the chats
    */
    private static function _get_chats_unread_count($chats, $_user_id){
        $chat_ids = array();
        
        foreach($chats as $m=>$data){
            array_push($chat_ids, $data->chat_id);
        }
        
        $messagedata = Messages::get_list(array(
            "alt_db" =>true,
            "where"=>array(
                array(
                    "name"=>"chat_id",
                    "operator"=>"IN",
                    "value"=>$chat_ids
                ),
                array(
                    "name"=>"user_id",
                    "operator"=>"<>",
                    "value"=>$_user_id
                ),
                array(
                    "name"=>"read_at",
                    "operator"=>"IS",
                    "value"=>"NULL"
                )
            ),
             "what"=>array(
                "count(chat_id) as unread_count",
                "chat_id",
                "user_id",
                "read_at"
                ),
             
             "groupby"=>"chat_id"

            ) 
        );
        
        /*
            print "USER: $_user_id";
            print "=====".__FUNCTION__."====>>".PHP_EOL;
            print_r($messagedata);
            print PHP_EOL."!!!=========".PHP_EOL;
        */
         
        foreach($chats as $m=>$data){
            foreach($messagedata as $m){
                if($data->chat_id == $m['chat_id']){
                    $data->unread_count = $m['unread_count'];
                } 
            }
        }
        
        /*print "=====".__FUNCTION__."=CHATS===>>".PHP_EOL;
        print_r($chats);
        print PHP_EOL."!!!=========".PHP_EOL;
        */
        return $chats;

    }
    /*
        gets the excerpt for each chat ( excerpt is the latest message)
        if no excerpt is found, then its an ampty chat, so hide it.
        @TODO: clean up empty chats on a regular basis?
    */
    private static function _get_chats_excerpt($chats){
        $chat_ids = array();
        $new_chats = array();
        foreach($chats as $m=>$data){
            array_push($chat_ids, $data->chat_id);
        }
        
        $messagedata = Messages::get_latest_excerpt($chat_ids);
        
        foreach($chats as $m=>$data){
            #print "looking at ".$data->chat_id. PHP_EOL;
            foreach($messagedata as $m){
                if($data->chat_id == $m->chat_id){
                    #print "found one for  ".$data->chat_id. PHP_EOL;
                    $data->excerpt = Messages::array_to_object($m);
                    // only send back ones with excerpts
                    array_push($new_chats, $data);
                } 
            }
        }
        return $new_chats;

    }

    /*
    get data for each user
    */
    private static function _get_related_user_data($list, $bTwoUsers = false){
        $user_ids = array();
        
        foreach($list as $m=>$data){

            if($bTwoUsers == true){
                array_push($user_ids, $data->user_id_1);
                array_push($user_ids, $data->user_id_2);
             }else{
                array_push($user_ids, $data->user_id);
             }   

        }

        $userdata = Users::get_list_for_user_data($user_ids);
        
        #print __FUNCTION__.PHP_EOL;
        #print_r($userdata);

        foreach($list as $m=>$data){
           /* if($bTwoUsers == true){
                print PHP_EOL."looking for $data->user_id_1, $data->user_id_2 ".PHP_EOL;
            }
            */            
            foreach($userdata as $u){
                
                if($bTwoUsers == true){
                    
                    if($data->user_id_1 == $u['user_id']){
                       #print " found ".$u['user_id'] . PHP_EOL;

                        $u['pic'] = self::_get_user_image($u); 
                        $data->user_1 = Users::array_to_object($u);
                        // dont check if its the logged in user, save money
                        $data->user_1->online = ( (Auth::get_logged_in_user_id() != $u['user_id']) ? UserOnline::is_online($u['user_id']) : true ) ;

                    }else if($data->user_id_2 == $u['user_id']){
                        #print " found ".$u['user_id'] . PHP_EOL;

                        $u['pic'] = self::_get_user_image($u); 
                        $data->user_2 = Users::array_to_object($u);                        
                        // dont check if its the logged in user, save money
                        $data->user_2->online = ( (Auth::get_logged_in_user_id() != $u['user_id']) ? UserOnline::is_online($u['user_id']) : true ) ;
                    }

                }else{
                    if($data->user_id == $u['user_id']){
                        $u['pic'] = self::_get_user_image($u);
                        $data->user = Users::array_to_object($u);
                        // dont check if its the logged in user, save money
                        $data->user->online = ( (Auth::get_logged_in_user_id() != $u['user_id']) ? UserOnline::is_online($u['user_id']) : true ) ;
                    }
                }
            }
        }
        return $list;

    }

    /* 
        maps the formatted date string to the right location in the object
    */
    private static function _get_formatted_dates_for_message($messages){

        foreach($messages as $msg){
            $msg->updated_at = self::_get_time_elapsed( $msg->updated_at, true ); 
            $msg->created_at = self::_get_time_elapsed( $msg->created_at, true );

            if(isset($msg->excerpt)){
                $msg->excerpt->created_at = self::_get_time_elapsed( $msg->excerpt->created_at, true );//xxx();// => 2015-04-20 18:02:09
            }
        }

        #print_r($messages);
        return $messages;
    }

    /* 
        maps the formatted date string to the right location in the object
    */
    private static function _get_formatted_dates_for_chat($chats){ 

        foreach($chats as $chat){
            $chat->updated_at = self::_get_time_elapsed( $chat->updated_at ); 
            $chat->created_at = self::_get_time_elapsed( $chat->created_at );

            if(isset($chat->excerpt)){
                $chat->excerpt->created_at = self::_get_time_elapsed( $chat->excerpt->created_at );//xxx();// => 2015-04-20 18:02:09
                $chat->excerpt->updated_at = self::_get_time_elapsed( $chat->excerpt->updated_at );//xxx();// => 2015-04-20 18:02:09
            }
        }

        #print_r($chats);
        return $chats;
    }

    /* 
        parses date and outputs a string formatted for Chat in particular
    */
    protected static function _get_time_elapsed($timestamp, $bool_msg = false){

        $datetime1 = new \DateTime();
        $datetime2 = new \DateTime(date('Ymd', strtotime($timestamp)));
        $interval = $datetime2->diff($datetime1);
        $datediff = $interval->format('%R%a').PHP_EOL;

        if($bool_msg){ // if its a message, just do this
            return  date('n/j/y g:i A', strtotime($timestamp)); //(e.g. 4/22/15 11:05 AM)              
        }else{
            if($datediff == 0) { // today, just the time 
                return date('g:i A', strtotime($timestamp));
            
            } else if(($datediff == 1)) { // 1
                return  "Yesterday";  

            } else if(($datediff < 7) && ($datediff > 1)) { // 1-7 days ago, the day of week
                return  date('l', strtotime($timestamp));  

            } else if($datediff > 6) { // more than a week ago
                return  date('n/j/y', strtotime($timestamp)); //(e.g. 4/22/15)

            } else if($datediff < 0) { // inconceivable!
                return 'future';
            } else {
                return date('l',  strtotime($timestamp) ) ; // day of week
            }  

        }        

    }

}

