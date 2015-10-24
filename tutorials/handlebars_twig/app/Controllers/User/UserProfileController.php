<?php

namespace app\Controllers\User;
use app\Controllers\AbstractController;
use app\Views\User\UserProfile;

class UserProfileController extends AbstractController{
    protected static $User;
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "activity";
    var $extra = null;

    var $up = null;

    public function __construct() {
        // get info from UseProfile
        $this->up = new UserProfile(self::$set_size, self::$set_num);
    }

    /*
    called at beginning of most or all of the action functions
    */
    private function _before($function){
        if ($this->method() != 'GET') {  return $this->_fail(false, $function , "Invalid Method"); } 
        if(!$this->args('id') ){ return $this->_fail(false, $function , "Invalid ID");  }
        
        self::$User = $this->up->get_user_activity((int)$this->args('id'));
        
        if(self::$User == null ){ return $this->_fail(false, $function , "Invalid User");  }
        return 1;
    }

    public function followers($func = __FUNCTION__){
        $result = $this->_before($func);
        if($result != 1 ){   return $result ; }


        $activity_count = $this->up->get_user_activity_count();

        $followers = $this->up->get_user_followers((int)$this->args('id'));

        $followers_count = $this->up->get_user_followers_count((int)$this->args('id'));

        $following_count = $this->up->get_user_following_count((int)$this->args('id'));


        // this is the response
         return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'user'=> self::$User, 
                'status'=>'OK', 
                'activity'=>"",
                'activity_count'=>$activity_count,
                'followers'=>$followers,
                'followers_count'=>$followers_count,
                'following_count'=>$following_count,
                "msg" => "here are your followers"
            );
    }


    public function following($func = __FUNCTION__){
        $result = $this->_before($func);

        if ( $result != 1 ){  return $result ; }

        $activity_count = $this->up->get_user_activity_count();
       

        $following = $this->up->get_user_following((int)$this->args('id'),(int)$this->args('set_num'));
        $followers_count = $this->up->get_user_followers_count((int)$this->args('id'));       
        $following_count = $this->up->get_user_following_count((int)$this->args('id'));

    
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'user'=> self::$User, 
                'status'=>'OK', 
                'activity'=>"",
                'following'=>$following,
                'activity_count'=>$activity_count,
                'followers_count'=>$followers_count,
                'following_count'=>$following_count,
                'msg' => "here are the peeps ur following"
            );
    
    }

    public function rr_followers() {
        return $this->followers(__FUNCTION__);
    }

    public function rr_following() {

        return $this->following(__FUNCTION__);
    }


    /*

    */
    public function activity() {
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        $activity = $this->up->get_user_activity_set($this->args('set_num'));

        $activity_count = $this->up->get_user_activity_count();

        $followers_count = $this->up->get_user_followers_count((int)$this->args('id'));
        
        $following_count = $this->up->get_user_following_count((int)$this->args('id'));

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action, 
                'status'=>'OK', 
                'activity_count'=>$activity_count,
                'activity'=>$activity,
                'followers_count'=>$followers_count,
                'following_count'=>$following_count,
                'user'=> self::$User, 
                "msg" => ""
        );        
    }



   
 }
