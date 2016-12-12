<?php

namespace app\Views\Proposals;
use app\Models\Proposals;
use app\Models\Activity;



use StdClass;
use Exception;
use app\Views\Shared\AbstractProfile;

class ProposalsList extends AbstractProfile {

	private static $proposals_model = null;

	protected static $cache_key_prefix = 'pl_';

	public function __construct($set_size = 30, $set_num = 1){

		self::set_size($set_size);
		self::set_num($set_num);

		/*
			create the models that will be needed for this profile
		*/
		$config = array(
			'set_size'=>self::set_size()
		);

		//create instance of proposals model
		self::$proposals_model = new Proposals();
		
		parent::__construct();
			
	}

	public function get_list_count() {
		return self::$proposals_model->get_proposals_count();
	}

	/** 
		For Regular proposals page
	*/
	public function get_all($set_num = 1) {

	}

	/** 
		For Featured Proposals page: this method calls Activity model to 
		instantiate activity-type items
	*/
	public function get_featured($set_num = 1) {

		$offset = 0;
		$prop_items = self::$proposals_model->get_featured_activity_items();
		
		$proposals_items = array();
		foreach($prop_items as $item){
			array_push($proposals_items, self::array_to_object( $item ));
		}
				
		if((int)$set_num > 1){
			$offset = (self::set_size() * ($set_num -1));
		}

		$activity_model = new Activity();

		$sliced_proposals = array_slice($proposals_items, $offset, self::set_size());
		self::set_num($set_num);
		$activity_model->set_num($set_num);

		$proposal_activity_items = $activity_model->get_activity_sub_type(
			$activity_model->get_activity_details(
				$sliced_proposals,
				0,
				array('key_name'=>'feat_prop')
			)
		);

		return $proposal_activity_items;
	
	}



}