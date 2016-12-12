<?php

namespace app\Controllers\Proposals;
use app\Controllers\AbstractController;
use app\Views\Proposals\ProposalsList;

class ProposalsListController extends AbstractController{
    
    /* */
    protected $router = null;
    /* 
        size of list to return to user
    */  
    public static $set_size = 30;
    public static $set_num = 1;

    var $default_action = "proposals";
    var $plview = null; //instance of proposals list view

    public function __construct() {
        // get info from ProposalsList
        $this->plview = new ProposalsList(self::$set_size, self::$set_num);
    }

    public function proposals() {

        $stories = $this->plview->get_all_proposals($this->args('set_num'));
        $stories_list_count = $this->plview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'status'=>'OK', 
                'proposals_list_count' => $proposals_list_count,
                'proposals_list'=>$proposals,
                'page_title' => "Proposals",
                "msg" => ""
        );        
    }

    public function featured_proposals() {

        $featured_proposals = $this->plview->get_featured($this->args('set_num'));
        $proposals_list_count = $this->plview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'status'=>'OK', 
                'proposals_list_count' => $proposals_list_count,
                'proposals_list'=>$featured_proposals,
                'page_title' => "Featured Proposals for Film & TV",
                "msg" => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

    
 }


