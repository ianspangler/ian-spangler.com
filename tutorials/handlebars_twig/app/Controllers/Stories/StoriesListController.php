<?php

namespace app\Controllers\Stories;
use app\Controllers\AbstractController;
use app\Views\Stories\StoriesList;

class StoriesListController extends AbstractController{
    
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "stories";
    var $slview = null; //instance of stories list view

    public function __construct() {
        // get info from StoriesList
        $this->slview = new StoriesList(self::$set_size, self::$set_num);

    }

    public function stories() {

        $stories = $this->slview->get_all_stories($this->args('set_num'));
        $stories_list_count = $this->slview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'status'=>'OK', 
                'stories_list_count' => $stories_list_count,
                'stories_list'=>$stories,
                'page_title' => "Stories",
                "msg" => ""
        );        
    }

    public function featured_stories() {

        $featured_stories = $this->slview->get_featured($this->args('set_num'));
        $stories_list_count = $this->slview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action' => $this->requested_action,
                'status' => 'OK', 
                'stories_list_count' => $stories_list_count,
                'stories_list'=>$featured_stories,
                'page_title' => "Featured Stories",
                "msg" => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

    
 }


