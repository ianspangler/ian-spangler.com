<?php
namespace app\Models;
use app\Models\BaseModel;
use app\Models\Titles;
use app\Models\Likes;
use app\Models\Credits;
use app\Models\Proposals;
use app\Models\Comments;

use app\Services\Auth\Auth;
use app\Services\ItemBuilder;
use app\Views\Shared\ListItem;

use StdCLass;

class Activity extends BaseModel{

	/*
	Activity does not represent a table, its an abstraction of a bunch of data we put together in code and call an 'Activity'
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
		used to determine some interactive elements on the page
		needed for ItemBuilder
	*/
	private static $current_user_id = null;
	/* 
		object to store
	*/	
	private static $result = null;
	private static $result_num = null;
	/*
	*/
	protected static $cache_key_prefix = 'activity_';

	protected static $disliked_ids = null;

	protected static $liked_ids = null;

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
			$inst = new Activity();
		}
		return $inst;
	}

	
	/*
		@returns an array;
		all Models class should have this function. its essentially a get function when the generic get() is not sufficient
	*/
	public function get_result($_mixed, $intent = "", $type = null, $options = array()){

		$sql = self::_get_user_activity_ids_sql((int)$_mixed);
 		/******CACHING *******/
		$class_context = self::_set_context();

		$key_name = self::$cache_key_prefix.$class_context.$intent."allids_".$_mixed.(($type)?"_".$type:"");		

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
			return $result;	
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return array();	
		}

	}

	/*
		go through all the activity items (top level)
		group by type
		make the sql call for each type (grouped, not one for each activity item)
		update the activity item to include the additional data
	*/
	public function get_activity_details($activities = array(), $_user_id = 0, $options = array()){

		$types = array();
		$updated_activities = array();

		if(!isset(self::$current_user_id)){ self::$current_user_id = $_user_id; }
		$class_context = self::_set_context();
		
		/*
		 	group the ids by type
			loops up to set_size() times (once for each item in the result)
		*/
		foreach($activities as $activity){

			if(!array_key_exists($activity->activity_type, $types)){
				$types[$activity->activity_type] = array();
				$types[$activity->activity_type]['ids'] = array();
				$types[$activity->activity_type]['items'] = array();
			}
			array_push($types[$activity->activity_type]['ids'], $activity->id);
			array_push($types[$activity->activity_type]['items'], $activity);
		} 
 
		/*
		 	construct the query for each type
			loops 1 time foreach type: comment, proposal, endorsement, like_profile, title, etc
		*/
		foreach($types as $typename => $typearray){
			// build sql for each group of types
			$res = self::get_activity_by_type_sql($typename, $_user_id, $typearray['ids']);
			$typearray['sql'] = $res['sql'];
			$typearray['key'] = $res['key']."_".@$options['key_name'];
			/*
			print "KEY  = ".$typearray['key'].PHP_EOL;
			print "CONTEXT  = ".$class_context.PHP_EOL;
			print "SQL for $typename = ".$res['sql'];
			*/

			// make the sql call that gets all the details for eeach item
			$results = self::_get_activity_items_from_db($typearray['sql'], $typearray['key']);

			/*
			 loop through the results and append the data to the matching item by $item->id in the array
			 loops the count($types[$typename]['items']) AKA: n + n + n = 50 	
			*/
			foreach($types[$typename]['items'] as $item){
				if(array_key_exists($item->id, $results)){
					$item->more = $results[$item->id]; 
				}
				array_push($updated_activities, $item);
			}

		} 

		if(@$options['order_by_date'] ==  false){
			// fix order which got screwed up in the loops above
			return $updated_activities;
		}else{
			// fix order which got screwed up in the loops above
			return self::_sort_activities_by_date( $updated_activities );
		}
	}
	
	/* 
		determine sub type by looking at the other columns
		build individual items using the ItemBuilder(used on News, Activity, Featured Stories, Featured Proposals)
	*/
	public static function get_activity_sub_type($activities){
		/*
			migrated this to ItemBuilder 5/6
		*/

		/*
			get the id of the item that was liked by calling internal method to match
		*/
		$list_item_class = new ListItem();				
		$activity_ids = array_map(array($list_item_class, 'get_item_id_for_like'), $activities);

		// we have to pass a copy of this so that we can use the current_user_id 
		$item_builder = new ItemBuilder(static::Instance());

		/*
			go get the ids of everything liked by this user
		*/
		$my_liked_ids = $item_builder->get_liked_ids($activity_ids);

		/*
			go get the ids of everything disliked by this user
		*/
		$my_disliked_ids = $item_builder->get_disliked_ids($activity_ids);

		$activities = $item_builder->init($activities, $my_liked_ids, $my_disliked_ids);

		return $activities;
	}	

	public static function get_current_user_id(){
		return self::$current_user_id;
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

		$filter = array( );

    	return filter_var_array($pairs);//, $filter);

	}
	
	/*
		this class is used as activity and as news, so we need to beable to identify it in the context its being used
	*/
	private static function _set_context(){
		if(preg_match("/news/i", get_called_class()) ){ 
			return "news";
		}else{ // Activity
			return "act";
		}
	}

	private static function _sort_activities_by_date($activities){

		usort($activities,  function($a, $b) {
		    return strtotime($b->sortable_timestamp) - strtotime($a->sortable_timestamp);
		});

		return $activities;
	}

	/*
		sometimes we want the logged in user,
		sometimes we want the user were looking at

		returns a number or empty string
	*/	
	private static function _get_user_who_liked(){
		// how do we know if we are on profile or home?
		if( self::_set_context() == "news"){
			return (int)self::$current_user_id;
		}else{ // Activity
			return (int)Auth::get_logged_in_user_id();
		}

	}
 

	/*
		execute a call for all of the activity items for a given type
	*/
	private static function _get_activity_items_from_db($sql, $key_name){ 	
 		$rows = array();
		
		/******CACHING *******/		
		// pass expiration time (in seconds) for cache objects to expire 
		$expires = (60 * 5); // 5 minutes
		$result = self::$myDBHandler->get($sql, $key_name, $expires);
		
		if (is_array($result)) {
			foreach($result as $row){
				$rows[ $row['id'] ] = self::array_to_object($row);
			} 
			return $rows;
		}else{
			print self::$myDBHandler->error(DEBUGGING);
			return null;	
		}
	}


	/*
		get sql for the details each of the types of activity
	*/	
	public static function get_activity_by_type_sql($typename, $_userid, $activity_ids = array()){
		// list of item ids to look for
		$ids = implode(", ", $activity_ids);
		// the return value
		$sql = "";
		// need this mostly for the Key_Name
		$class_context = self::_set_context();
 		
 		/******CACHING *******/	
		$key = self::$cache_key_prefix.$class_context."_dts_".$typename."_".self::set_num()."_".$_userid;

		#print $key .PHP_EOL;

		switch ($typename) {
			/*case 'role':

				$sql = "SELECT r.role_id as 'id', r.name, r.url_handle,  
				r.main_pic, r.main_pic_geom, r.date_posted,
				u.first_name as user_first_name, u.last_name as user_last_name, u.user_id, u.oauth_uid, u.is_admin, u.main_pic AS user_pic   
				
				FROM roles r  
				LEFT JOIN users u ON r.user_id = u.user_id 

				WHERE r.role_id IN ($ids) GROUP BY r.role_id";
					
				break;
			*/
			case 'credit': // used on credit list. not in activity or news

				$sql = Credits::get_credit_detail_by_ids_sql($ids);
				break;

			case 'title':

				$sql = Titles::get_item_detail_by_ids_sql($ids);					
				break;
			
			case 'like_profile': 
				
				$sql = Likes::get_like_profile_detail_by_ids_sql($ids);
				break;

			case 'like':  

				$sql = Likes::get_like_detail_by_ids_sql($ids); 
				break;

			case 'like_credit':  

				$sql = Credits::get_like_credit_detail_by_ids_sql($ids);
				break;
			
			case 'proposal':

				$sql = Proposals::get_proposal_detail_by_ids_sql($ids);				
				break;

			case 'endorsement': 
 
				$sql = Comments::get_endorsement_detail_by_ids_sql($ids);
				break;

			case 'comment': 
				 
				$sql = Comments::get_comment_detail_by_ids_sql($ids);
				 
                break;

            case 'comment_credit': 
				 
				$sql = Comments::get_comment_credit_detail_by_ids_sql($ids);  
				break;	
		}

		return array('sql'=>$sql, 'key'=>$key);
	
	}
	
	/*
		makes the first call for all the ids in the activity for a user
		returns ALL ids in reverse chronological order
	*/			
	public static function _get_user_activity_ids_sql($_userid, $options = null){
		if(!is_int( $_userid )){
			return new StdClass();
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
		$proposal_cols = "talent_id, role_id, title_id, null as comment_talent_id, proposal_type ";

		$comment_cols = "proposal_id, credit_id, title_id, talent_id, null "; // 	

		// not talent credit role
		$like_cols = "item_id, item_type_id, null, null, null ";

		$title_cols = "null, null, null, null, null ";

		// NO ROLE HERE YET! 

		$sql = "
			(SELECT ".$proposal_cols." ,".self::$primary_id.", proposal_id as id, 'proposal' as activity_type, null as 'type', date_posted as timestamp, date_posted as sortable_timestamp FROM proposals 
				WHERE ".self::$primary_id." = ".$_userid." AND proposals.anonymous <> 'Y') UNION ALL

			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like_credit' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM likes 
				WHERE ".self::$primary_id." = ".$_userid." AND auto_like = 0 AND active = 1
				AND item_type_id = 2 				
				AND ( likes.item_id IN( SELECT credit_id from credits WHERE user_id <> ".$_userid." ) )
				) UNION ALL 	

			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM likes 
				WHERE ".self::$primary_id." = ".$_userid." AND auto_like = 0 AND active = 1
				AND item_type_id = 1 				
				AND ( likes.item_id IN( SELECT proposal_id from proposals WHERE user_id <> ".$_userid." ) )
				) UNION ALL 	

			(SELECT ".$like_cols." ,".self::$primary_id.", like_id as id, 'like_profile' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM likes 
				WHERE ".self::$primary_id." = ".$_userid." AND auto_like = 0 AND active = 1
				AND ( likes.item_id IN( SELECT title_id from titles WHERE user_id <> ".$_userid." ) )
				AND item_type_id IN (3,5)
				) UNION ALL 	

			(SELECT ".$title_cols." ,".self::$primary_id.", title_id as id, 'title' as activity_type, null as 'type', date_posted as timestamp, date_posted as sortable_timestamp FROM titles 
				WHERE ".self::$primary_id." = ".$_userid.") UNION ALL

			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'endorsement' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM comments 
				WHERE 
				( proposal_id = '0' OR proposal_id IS NULL )
				AND ( role_id = '0' OR role_id IS NULL )
				AND ( credit_id = '0' OR credit_id IS NULL )
				AND ".self::$primary_id."=".$_userid." AND anonymous <> 'Y') UNION ALL

			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'comment' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM comments 
				WHERE ( 
					( title_id = '0' OR title_id IS NULL )
					AND ( role_id = '0' OR role_id IS NULL )
					AND ( talent_id = '0' OR talent_id IS NULL )
					AND ( credit_id = '0' OR credit_id IS NULL )
					) 
				AND  ( comments.proposal_id IN( SELECT proposal_id from proposals WHERE user_id <> ".$_userid." ) )
				AND ".self::$primary_id."=".$_userid." AND anonymous <> 'Y') UNION ALL
			
			(SELECT ".$comment_cols." ,".self::$primary_id.", comment_id as id, 'comment_credit' as activity_type, null as 'type', datetime as timestamp, datetime as sortable_timestamp FROM comments 
				WHERE ( 
					( title_id = '0' OR title_id IS NULL )
					AND ( role_id = '0' OR role_id IS NULL )
					AND ( talent_id = '0' OR talent_id IS NULL )
					AND ( proposal_id = '0' OR proposal_id IS NULL )
					AND ( credit_id <> 0 AND credit_id IS NOT NULL ) 
					)
				AND ".self::$primary_id."=".$_userid." AND anonymous <> 'Y')
				ORDER BY timestamp DESC, activity_type = 'proposal', activity_type = 'credit', activity_type ASC 
			";
			
			//print $sql;
			///exit;

		return $sql;				
	}

 

}
