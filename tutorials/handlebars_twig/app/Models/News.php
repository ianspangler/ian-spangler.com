<?php
namespace app\Models;
use app\Models\Activity;
use StdCLass;


class News extends Activity{

	/*
	News does not represent a table, its an abstraction of a bunch of data we put together in code and call an 'news' item
	*/
	protected static $table = null;
	/* 
		only these columns will be pulled from user table
	*/	
	private static $allowed_fields = null;
		
	/* 
		primary id column name in user table
	*/	
	protected static $primary_id = 'user_id';
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'news_';
	protected static $count = 0;

	protected static $total_query_limit = 2000;


	public function __construct($config = null){ 

		if(is_array($config) && isset($config['set_size'])){
			self::set_size($config['set_size']);			
		}
		//
		parent::__construct();
	}	

	public static function Instance(){
		static $inst = null;
		if ($inst === null) {
			$inst = new News();
		}
		return $inst;
	}
	

	public function get_result($_mixed, $intent = "", $type = null, $options = array()){		
		$followeds_list = self::_get_user_following($_mixed);
		$followeds_ids_list = $followeds_list;/*array_map(function($item) { 
			return $item['user_id'] ;
		}, $followeds_list );*/
		$type = null;
		$set = null;
		$limit = null;
		$date = null;
		if(isset($options['type'])){ $type = $options['type']; }
		if(isset($options['set'])){ $type = $options['set']; }
		if(isset($options['limit'])){ $type = $options['limit']; }
		if(isset($options['date'])){ $type = $options['date']; }

		$sql = self::_get_user_news_ids_sql((int)$_mixed, implode(",",$followeds_ids_list));
 		
 		/****** CACHING *******/
		$key_name = self::$cache_key_prefix.$intent."allids_".$_mixed.(($type)?"_".$type:"");		
		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		
		try{
			$result = self::$myDBHandler->get($sql, $key_name, $expires);

		 } catch (Exception $e) {
			print self::$myDBHandler->error(DEBUGGING);
			if(DEBUGGING) print $e->getMessage();
        	throw $e;
			return array();
    	}

		if (is_array($result)) {
			/*
				due to the huge size of this result, we need to truncate it now 
			*/
			self::$count = count($result);
			///print(self::$count);
			///exit;
			//sort the result
			/////usort($result, array(__CLASS__, "sort_news_ids"));

			$offset = 0; 				
			if((int)static::set_num() > 1){
				$offset = (static::set_size() * (static::set_num() -1));
			}

			$sliced = array_slice($result, $offset, static::set_size());
			//print_r($sliced);

			return $sliced;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}

	}

	/*
	private static function sort_news_ids($a, $b) {

		if ($a['sortable_timestamp'] < $b['sortable_timestamp']) {

		    return 1;
		} elseif ($a['sortable_timestamp'] > $b['sortable_timestamp']) {

		    return -1;
		} else {

			return 0;
		
		}
	}*/


	public static function get_news_count(){
		//print(self::$count);
		//exit;
		return self::$count;
	}

	
	
	/*
		similar to whats in the AbstractProfile
	*/
	private static function _get_user_following($user_id, $set_num = 1){
		$following_model = new Following(array());
		return $following_model->get_user_followed_ids((int)$user_id, array('LIMIT'=>1000));		
	}
 
	 
	/*
		makes the first call for all the ids in the news for a user
		returns ALL ids in reverse chronological order
	*/			
	public static function _get_user_news_ids_sql($_userid, $followeds_ids = "", $options = null){
		if(!is_int( $_userid )){
			return new StdClass();
		}
	 	if(trim($followeds_ids) == ""){
	 		$followeds_ids = -1;
	 	}
	 	 

		/*
			these 4 lists roll up to the same columns.
			basically the result set columns are deifined by the 1st SELECT, so in this case
				we decide to call comments.proposal_id "talent_id",
				we decide to call comments.credit_id "role_id",
				we decide to call comments.title_id "comment_talent_id",
				we decide to call comments.null "proposal_type",
			
			its confusing but i dont see another way around it
				
		*/
		$proposal_cols = "p.talent_id, p.role_id, p.title_id, null as comment_talent_id, p.proposal_type ";

		$comment_cols = "proposal_id, credit_id, title_id, talent_id, null "; // 	

		// not talent credit role
		$like_cols = "item_id, item_type_id, null, null, null ";

		$title_cols = "null, null, null, null, null ";

		$role_cols = "null, null, null, null, null ";

		/* 
			1.	Creation of Casting or Filmmaker Proposal 
			1)	Proposal was created for a story the logged-in user supported OR auto-supported  
			2)	Proposal was created for an actor/ filmmaker the logged-in user actively supported  
			3)	Proposal was created for an (unattached) character the logged-in user actively supported
			4)	Proposal was created by a user the logged-in user is following 

			2.	Proposal of a  Character  
			1)	The  character was proposed by a user the logged-in user is following

		 */	

		//get time from 6 months ago
		///$month_ago = date('Y-m-d', strtotime("-6 month"));

		$proposals_sql = " 

			(SELECT DISTINCT ".$proposal_cols." ,".self::$primary_id.", p.proposal_id as id, 'proposal' as activity_type, null as 'type', p.anonymous as 'anonymous', 
				p.date_posted as timestamp, CASE WHEN f.date_scheduled <= CURRENT_TIMESTAMP() AND f.date_scheduled <> '' THEN f.date_scheduled ELSE p.date_posted END as sortable_timestamp, 
				CASE WHEN f.id <> '' THEN 'featured' ELSE null END as status 
				FROM proposals p 
				LEFT JOIN featured_proposals f ON p.proposal_id = f.proposal_id
				WHERE 
					((p.user_id IN (".$followeds_ids.")  /* 1.4 */
						OR 
						(
							/* #1.1 */
							( 
								p.title_id IN ( SELECT item_id from likes WHERE likes.user_id = ".$_userid." AND item_type_id = 3 AND likes.active = 1  )
							) 
							OR /* #1.2 */
							( 
								p.talent_id IN ( SELECT item_id from likes WHERE likes.user_id = ".$_userid." AND item_type_id = 5 AND likes.active = 1 AND auto_like = 0 )
							) 
							OR /* #1.3 */
							( 
								p.role_id IN ( SELECT item_id from likes WHERE likes.user_id = ".$_userid." AND item_type_id = 4 AND likes.active = 1 AND auto_like = 0 )
							)
						)
					AND p.anonymous <> 'Y')

					OR f.id <> '' AND f.date_scheduled <= CURRENT_TIMESTAMP())
					
					AND p.user_id <> ".$_userid."

				) UNION ALL
			";



		/*
			2.	Proposal of a Story  
			1)	The story was proposed by a user the logged-in user is following
		*/	
		$titles_sql = " 
			(SELECT ".$title_cols." ,".self::$primary_id.", ti.title_id as id, 'title' as activity_type, null as 'type', 
				null as 'anonymous', ti.date_posted as timestamp, 
				CASE WHEN f.date_scheduled <= CURRENT_TIMESTAMP() AND f.date_scheduled <> '' THEN f.date_scheduled ELSE ti.date_posted END as sortable_timestamp, 
				CASE WHEN f.id <> '' THEN 'featured' ELSE null END as status 
				FROM titles ti 
				LEFT JOIN featured_stories f ON ti.title_id = f.title_id
				WHERE ((".self::$primary_id." IN (".$followeds_ids."))
				OR (f.id <> '' AND f.date_scheduled <= CURRENT_TIMESTAMP())) 
				
				) UNION ALL				
		";	


		/*
			2.	Proposal of a Character  
			1)	The character was proposed by a user the logged-in user is following
		*/	
		/*
			$roles_sql = " 
			(SELECT ".$role_cols." ,".self::$primary_id.", role_id as id, 'role' as activity_type, null as 'type', date_posted as timestamp FROM roles 
				WHERE ".self::$primary_id." IN (".$followeds_ids.") 
				) UNION ALL				
			";	
		*/
		/*
			3.	Support of a Casting or Filmmaker Proposal  
			1) The proposal was supported by a user the logged-in user is following
		*/	
		$likes_sql = " 
			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status
				FROM likes 
				WHERE ".self::$primary_id." IN (".$followeds_ids.") AND auto_like = 0 AND active = 1
				AND item_type_id = 1 				
				AND ( likes.item_id IN( SELECT proposal_id from proposals WHERE ".self::$primary_id." NOT IN (".$followeds_ids.") ) )
				 ) UNION ALL				
		";

		/*
			4.	Support of a Story or Actor/ Filmmaker  
			1) The proposal was supported by a user the logged-in user is following
 
		*/	
		$like_profiles_sql = " 
			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like_profile' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status 
				FROM likes 
				WHERE ".self::$primary_id." IN (".$followeds_ids.") AND auto_like = 0 AND active = 1
				AND ( likes.item_id IN( SELECT title_id FROM titles WHERE ".self::$primary_id." NOT IN (".$followeds_ids.") ) )
				AND item_type_id IN (3,5)
				 ) UNION ALL
		";


		/*
			5.	Comment on a Casting or Filmmaker Proposal  
			2)	Comment was on a proposal the logged-in user actively supported OR auto-supported (by publishing the proposal)
			3)	Comment was added by a user the logged-in user is following 
		*/	
		$comments_sql = " 
			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'comment' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status FROM comments 
				
				WHERE ( 
					( title_id = '0' OR title_id IS NULL )
					AND ( role_id = '0' OR role_id IS NULL )
					AND ( talent_id = '0' OR talent_id IS NULL )
					AND ( credit_id = '0' OR credit_id IS NULL )
				)
				AND (
					(
						(
						comments.proposal_id IN ( SELECT item_id FROM likes WHERE likes.user_id = ".$_userid." AND anonymous <> 'Y' AND likes.active = 1 AND item_type_id = 1)  
						OR
						comments.credit_id IN ( SELECT item_id FROM likes WHERE likes.user_id = ".$_userid." AND likes.active = 1 and item_type_id = 2 )  
						)

					) OR ( 
						( comments.proposal_id IN ( SELECT proposal_id FROM proposals WHERE ".self::$primary_id." NOT IN (".$followeds_ids.") AND anonymous <> 'Y' ) )
						AND ".self::$primary_id." IN (".$followeds_ids.")   
					)
				)
				AND anonymous <> 'Y' AND ".self::$primary_id." <> ".$_userid.") UNION ALL
		";

		/*
			 6.	Endorsement of a Story or Actor/ Filmmaker  
			1)	Endorsement was for a story the logged-in user supported OR auto-supported
			2)	Endorsement was for an actor/ filmmaker the logged-in user actively supported
			3)	Endorsement was added by a user the logged-in user is following
		*/	
		$endorsements_sql = " 
			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'endorsement' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status FROM comments 
				WHERE 
				( proposal_id = '0' OR proposal_id IS NULL )
				AND ( role_id = '0' OR role_id IS NULL )
				AND ( credit_id = '0' OR credit_id IS NULL )
				AND (
					(
					".self::$primary_id." IN (".$followeds_ids.") /* #6.3 */
					) OR (
						title_id IN ( SELECT item_id FROM likes WHERE likes.user_id = ".$_userid." AND item_type_id = 3 AND active = 1 ) /* #6.1 */
						OR
						talent_id IN ( SELECT item_id FROM likes WHERE likes.user_id = ".$_userid." AND item_type_id = 5 AND active = 1 AND auto_like = 0 )/* #6.2 */
						)
				)
				AND anonymous <> 'Y' AND ".self::$primary_id." <> ".$_userid.") UNION ALL
		";
		
		/*
			7.	Support of an Acting or Filmmaking Credit  
			1) The proposal was supported by a user the logged-in user is following
		*/	
		$like_credits_sql = "
			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like_credit' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status 
				FROM likes 
				WHERE ".self::$primary_id." IN (".$followeds_ids.") AND auto_like = 0 AND active = 1
				AND item_type_id = 2 				
				AND likes.item_id IN ( SELECT credit_id FROM credits WHERE user_id <> ".$_userid." )
				) UNION ALL
		";

		/*
			8.	Comment on an Acting or Filmmaking Credit “comment_credit”
			1)	Comment was on a credit the logged-in user actively liked OR auto-liked (by publishing the credit)
			2)	Comment was added by a user the logged-in user is following
		*/
		$comment_credits_sql = "
			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'comment_credit' as activity_type, null as 'type', null as 'anonymous', datetime as timestamp, datetime as sortable_timestamp, null as status FROM comments 
				WHERE ( 
					( title_id = '0' OR title_id IS NULL )
					AND ( role_id = '0' OR role_id IS NULL )
					AND ( talent_id = '0' OR talent_id IS NULL )
					AND ( proposal_id = '0' OR proposal_id IS NULL )
					AND ( credit_id <> 0 AND credit_id IS NOT NULL ) 
					)
				AND (
					(".self::$primary_id." IN (".$followeds_ids.") )  /* 8.2 */
					OR
					( credit_id IN ( SELECT item_id FROM likes where likes.user_id = ".$_userid." AND active = 1 AND item_type_id = 2 ) ) /* 8.1 */
				)
				AND anonymous <> 'Y' AND ".self::$primary_id." <> ".$_userid.")";

		//$end_sql = "";				
		//$end_sql = " LIMIT ".self::$total_query_limit;
		$end_sql = "ORDER BY sortable_timestamp DESC, activity_type = 'proposal', activity_type = 'credit', activity_type ASC LIMIT ".self::$total_query_limit;

		/* final SQL */
		$sql = $proposals_sql . $titles_sql . $likes_sql . $like_profiles_sql . $comments_sql . $endorsements_sql . $like_credits_sql . $comment_credits_sql . $end_sql;
		
		//print($sql);
		//exit;
		return $sql;				
	}

	/*
		required by interface
		to be used to attach secondary and associated data
	*/
	protected function _has_relationships(){
		//self::$has_many ;
	}
	/*
	this needs to match the schema
	*/
	protected function _get_filtered_values($pairs){

		$filter = array( 
		);

    	return filter_var_array($pairs);//, $filter);

	}	 

}
