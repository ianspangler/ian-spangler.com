<?php

namespace app\Controllers\Proposals;
use app\Controllers\AbstractController;
use app\Views\Proposals\ProposalsProfile;


class ProposalsController extends AbstractController{
    
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
        $this->plview = new ProposalsProfile(self::$set_size, self::$set_num);
    }

    /*
        returns data using the "info" model 
        used on desktop & mobile single pages

        stdClass Object
        (
            [id] =&gt; 1194
            [talent_id] =&gt; 1992
            [title_id] =&gt; 1949
            [talent_pic_id] =&gt; 1281
            [role_pic_id] =&gt; 0
            [proposal_type] =&gt; casting
            [proposal_image] =&gt; proposal_image_1434050202.6133.jpg
            [activity_type] =&gt; proposal
            [type] =&gt; 
            
            ...etc

            [supporters_row_fan_results] =&gt; Array
                (
                    [fans_array] =&gt; Array
                        (
                            [0] =&gt; stdClass Object
                                (
                                    [user_pic] =&gt; tessa_1406358927.3665.jpg
                                    [user_id] =&gt; 883
                                    [oauth_uid] =&gt; 100004773558454
                                    [first_name] =&gt; Tessa
                                    [last_name] =&gt; Winters
                                    [proposal_count] =&gt; 210
                                )

                           ...etc

                        )

                    [num_remaining] =&gt; 2
                )

            [supporters_row_fans] =&gt; Array
                (
                    [0] =&gt; stdClass Object
                        (
                            [user_pic] =&gt; tessa_1406358927.3665.jpg
                            [user_id] =&gt; 883
                            [oauth_uid] =&gt; 100004773558454
                            [first_name] =&gt; Tessa
                            [last_name] =&gt; Winters
                            [proposal_count] =&gt; 210
                        )
                    ...etc

                )

            [info] =&gt; Array
                (
                    [post_id] =&gt; 1194
                    [post_type] =&gt; casting
                    [title_format] =&gt; 23
                    [format_name] =&gt; Novel Series
                    [title_genre] =&gt; 14,13
                    [talent_id] =&gt; 1992
                ...etc
                )

            [liked] =&gt; N
            [disliked] =&gt; Y
            [title] =&gt; Ashley Benson as Keatyn in The Keatyn Chronicles
        ) 
    */

    public function view() {
    
        $get_vars = self::_get_qs_vars();    
        $extras = $this->plview->get_extras($_GET);

        $result = $this->plview->get_one((int)$this->args('proposal_id')); 

        return array(
                'arguments' =>  $this->args(),
                'qs_vars' =>  $get_vars,
                'extras' => $extras,
                'requested_action'=>$this->requested_action,
                'status'=>'OK',  
                'proposal_item'=>$result,
                'page_title' => "Proposals for Film & TV",
                "msg" => ""
        );        
    }
    /*
        returns data using the "flat" model 
        used on desktop list pages
    */
    public function proposals() {
        
        $get_vars = self::_get_qs_vars();
        $extras = $this->plview->get_extras($_GET);
        if(isset($_GET['request']) && $_GET['request'] == 'featured-proposals'){
            return self::featured_proposals();  
        }
         
        $proposals = $this->plview->get_all_proposals($this->args('set_num'), $extras);
        $proposals_list_count = $this->plview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'qs_vars' =>  $get_vars,
                'extras' => $extras,
                'requested_action'=>$this->requested_action,
                'status'=>'OK', 
                'list_count' => $proposals_list_count,
                'list_count_display' => '99999',
                'proposals_list'=>$proposals,
                'page_title' => "Proposals for Film & TV",
                "msg" => ""
        );        
    }

    /*
        reurns data using the "more" model like: 
        (used on mobile list pages)
        stdClass Object
        (
            [talent_id] =&gt; 6877
            [role_id] =&gt; 213
            [title_id] =&gt; 1232
            [comment_talent_id] =&gt; 
            [proposal_type] =&gt; casting
            [user_id] =&gt; 93419
            [id] =&gt; 67717
            ...
            [more] =&gt; stdClass Object
                (
                    [id] =&gt; 67717
                    [talent_id] =&gt; 6877
                    [title_id] =&gt; 1232
                    [talent_pic_id] =&gt; 0
                ... etc
        */
    public function featured_proposals() {

        $featured_proposals = $this->plview->get_featured($this->args('set_num'));
        $proposals_list_count = $this->plview->get_list_count();

        return array(
                'arguments' =>  $this->args(),
                'requested_action'=>$this->requested_action,
                'status'=>'OK', 
                'list_count' => $proposals_list_count,
                'list_count_display' => '99999',
                'proposals_list'=>$featured_proposals,
                'page_title' => "Featured Proposals for Film & TV",
                "msg" => ""
        );        
    }

    public function set_size(){
        return self::$set_size;
    }

    
 }


