<?php

namespace app\Controllers\Comments;
use app\Controllers\AbstractController;
use app\Views\Comments\CommentsProfile;

class CommentsController extends AbstractController{
    
    protected static $User;
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "listing";

    var $co = null;

    public function __construct() {
        // get info from Profile
        $this->cp = new CommentsProfile(self::$set_size, self::$set_num);
    }

    /*
        called at beginning of most or all of the action functions
    */
    private function _before($function){

        #if(!$this->args('id') ){ return $this->_fail(false, $function , "Invalid ID");  }
        
        // gets the user and its news without the news details
        // news feed is gigantic, so we need to get it by sets;
        if((int)$this->args('set_num') > 0){
            $this->cp->set_num($this->args('set_num'));
        }

        return 1;
    }
      
    /*
    uncomment to overwrite the Base function in AbstractAPI
    public function explain() {
        print "IN EXPLAIN";
    }
    */
    /*
        checks the status of the thread
    */    
    public function check_status() {
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }

        #print __FUNCTION__;

        $post_args = $this->router->_request_args();

        $result = $this->cp->thread_is_deactivated((int)$post_args['item_id'], (int)$post_args['item_type_id']);

        $this->router->args['format'] = 'json';

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'status'=> 'OK', 
                'thread_status' => ( ($result == 1) ? 'closed': 'open'),
                "msg" => ""
        );        
    }

    /*
        get one comment
    */
    public function view() {
        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }         
    
        $itemtype = $this->args('itemtype');
        $comment_id = $this->args('comment_id');
        
        // print "IN VIEW ONE!";
        // print_r($this->args());

        $result = $this->cp->get_one((int)$comment_id);
        // print_r($result);

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'comment_item'=>$result,
                'status'=> 'OK', 
                "msg" => ""
        );        

    }
        
    /*
        get group of comments
        requires a item_id to match on (user_id / proposal_id / credit_id / title_id / talent_id / role_id etc), or a list of ids
        can take a set num
    */
    public function listing() {

        global $item_type_ids;

        $result = $this->_before(__FUNCTION__);
        if($result != 1 ){   return $result ; }         

        $itemtype = $this->args('itemtype');
        
        // print "IN LIST!";
        // print_r($this->args());
        #$this->router->args['format'] = 'json';

        if($this->args('profile_id')!=""){
            $profile_id = $this->args('profile_id');
            $list = $this->cp->get_all_for_profile((int)$itemtype, (int)$profile_id, $this->args('set_num'));

            $total_count = $this->cp->get_comments_count($profile_id, array_search($itemtype, $item_type_ids));
            
        }else if($this->args('ids')!=""){
            $ids = $this->args('ids');
            $list = $this->cp->get_all_by_ids((int)$itemtype, (int)$ids, $this->args('set_num'));

            $total_count = "";
        }


        #print_r($list);
        return array(
                'arguments' =>  $this->args(),
                'requested_action'=> $this->requested_action,
                'list_items'=>$list,
                'total_count'=>$total_count,
                'status'=> 'OK', 
                "msg" => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

   
 }
