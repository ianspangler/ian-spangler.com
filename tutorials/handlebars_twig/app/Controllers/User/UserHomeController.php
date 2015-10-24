<?php

namespace app\Controllers\User;
use app\Controllers\AbstractController;
use app\Controllers\ChatController;
use app\Views\User\UserHome;

use shared\Services\CacheClearer;


class UserHomeController extends AbstractController{
    protected static $User;
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "newsfeed";

    var $uh = null;

    public function __construct() {
        // get info from UseHome
        $this->uh = new UserHome(self::$set_size, self::$set_num);
    }

    /*
        called at beginning of most or all of the action functions
    */
    private function _before($function){

        if(!$this->args('id') ){ return $this->_fail(false, $function , "Invalid ID");  }
        
        // gets the user and its news without the news details
        // news feed is gigantic, so we need to get it by sets;
        if((int)$this->args('set_num') > 0){
            $this->uh->set_num($this->args('set_num'));
        }

        switch ($function) {
            case 'notifications':
                self::$User = $this->uh->get_user_notifications((int)$this->args('id'));
                break;
            
            case 'newsfeed':
                self::$User = $this->uh->get_user_news((int)$this->args('id'));
                break;
            
            case 'deactivate':
                self::$User = $this->uh->get_a_user((int)$this->args('id'));
                break;

            default:    
                //
                return $this->_fail(false, $function , "Invalid Function");
                break;
        }
        
        if(self::$User == null ){ return $this->_fail(false, $function , "Invalid User");  }

        return 1;
    }
    /*
        called as a sub-action,aka  "action/subaction"
        to use the below, call /notifications/deactivate/ (+ post parameter ID)
    */
    public function _secondary_deactivate($args){
                 
        /*
            go away, no id supplied
        */
        if ((int)$_REQUEST['id'] < 1) {
            return false;//$this->{$action}($this->args()); 
        } 

        $result = $this->uh->set_active( 
            array( // where clause
                'notification_id'=>$_REQUEST['id']
            ),
            array(  // what is to be updated
                'active'=>0
            ) 
        ); 


        //clear the global notifications count
        CacheClearer::clear_cache_for_notifications(self::$User->user_id);

        return $result;
    }

    /* 
        see if there is a secondary action 
        and if so return the string name of the function
    */      
    private function _check_secondary() {
        $action = "_secondary_".@$this->router->get_uri_parts()['secondary_action'];
        if ((int)method_exists($this, $action) > 0) {
            return $action;
        } 
        return false;
    }

    public function notifications(){
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }
        
        /* 
            see if there is a secondary action 
            and if so hit it
        */      
        if( $action = self::_check_secondary() ) {            
            /*
                this secondary action wants json back
            */
            $this->router->args['format'] = 'json';
            
            return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                "status" => ( ($this->{$action}($this->args()) > 0) ? "OK" : "FAIL"), 
                "msg" => "",
                'news_count' => '',
                'notifications' => '',
                'notification_messages' => '',
                'notification_count' => ''
            );    
         }  

        /********** primary action ********/
        try{
            $notifications = self::$User->notifications;
            $notification_messages = $this->uh->get_notifications_details((int)$this->args('id'), $this->args('set_num'));
         
            // clear viewed messages 
                $this->uh->clear_unread_message_count((int)$this->args('id'));
            // 

        }catch (Exception $e){
            if(DEBUGGING) print __METHOD__ . " ERROR ".$e->getMessage();
            error_log(          __METHOD__ . " ERROR ".$e->getMessage());
            return  $this->_fail(false, __FUNCTION__ , "Error Processing Request");
        } 

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'user' => self::$User,  
                'status'=>'OK', 
                "msg" => "",
                'news_count' => '',
                'notifications' => self::$User->notifications,
                'notification_messages' => $notification_messages,
                'notification_count' => $this->uh->get_total_message_count((int)$this->args('id'))
        );    

    }

    
    /*

    */
    public function chat_list() {
        //return array();
        return chat_list( func_get_args() );
    }


    /*

    */
    public function newsfeed() {
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        $news_details = $this->uh->get_user_news_set($this->args('set_num'));
        $news_count = $this->uh->get_user_news_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'user'=> self::$User,  
                'status'=> 'OK', 
                'news_count' => $news_count,
                'notification_count' => 0,
                //'news'=>$news_details,
                "msg" => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

    

   
 }
