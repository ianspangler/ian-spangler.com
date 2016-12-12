<?php

namespace app\Controllers\Credits;
use app\Controllers\AbstractController;
use app\Views\Credits\CreditsProfile;

class CreditsController extends AbstractController{
    
    protected static $credit;
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "view";

    var $profile = null;

    public function __construct() {
        // get info from Profile
        $this->profile = new CreditsProfile(self::$set_size, self::$set_num);
    }

    /*
        called at beginning of most or all of the action functions
    */
    private function _before($function){

        #if(!$this->args('id') ){ return $this->_fail(false, $function , "Invalid ID");  }
        // gets the user and its news without the news details
        // news feed is gigantic, so we need to get it by sets;
        if((int)$this->args('set_num') > 0){
            $this->profile->set_num($this->args('set_num'));
        }
        #if(self::$credit == null ){ return $this->_fail(false, $function , "Invalid Credit ID");  }
        return 1;
    }
      
    public function view(){
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        $item = self::$credit = $this->cp->get_one((int)$this->args('id'));
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'credit'=> $item,
                'status'=> ( ($item != false )? "OK":"FAIL"), 
                'page_title' => "Credits",
                "msg" => ""
        );       

    }
    /*

    */    
    public function acting_credits() { 
 
        $credit_type = self::_get_credit_type(); 

        $list_items = $this->profile->get_all($this->args('set_num'), $credit_type );
        $list_count = $this->profile->get_list_count($credit_type );
        
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'credit_type'=>$credit_type,    
                'status'=>'OK', 
                'list_count' => $list_count,
                'list'=> $list_items,
                'page_title' => "Casting Credits",
                'msg' => ""
        );        
    }

    public function filmmaker_credits() {
 
        $credit_type = self::_get_credit_type(); 

        $list_items = $this->profile->get_all($this->args('set_num'), $credit_type );
        $list_count = $this->profile->get_list_count($credit_type );
        
        #print_r( $list_items[0]);

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'credit_type'=>$credit_type,    
                'status'=>'OK', 
                'list_count' => $list_count,
                'list'=> $list_items,
                'page_title' => "Filmmaker Credits",
                'msg' => ""
        );        
    }

    public function featured_credits() { 
 
        $credit_type = self::_get_credit_type();

        $list_items = $this->profile->get_featured($this->args('set_num'), $credit_type);
        $list_count = $this->profile->get_featured_list_count($credit_type);

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'credit_type'=>$credit_type,    
                'status'=>'OK', 
                'list_count' => $list_count,
                'list'=> $list_items,
                'page_title' => "Featured Film & TV Credits",
                'msg' => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

    private function _get_credit_type(){
        if(preg_match('/acting-credits/', $this->args('action')) ){
            return 'casting';
        }else if(preg_match('/filmmaker-credits/', $this->args('action')) ){
            return 'crew';            
        }
        return 'casting';
    }
   
 }
