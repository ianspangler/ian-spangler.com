<?php

namespace app\Services\Mailer;

//use app\lib\vendor\MandrillApiPHP\src\Mandrill;
//use app\Models\LikeUnlike;
require_once DOCROOT . '/app/lib/vendor/MandrillApiPHP/src/Mandrill.php';

//ini_set('error_reporting', E_ALL);
//ini_set("display_errors", 1); 

class Mailer {

	private static $api_key = 'Psxrf8PQRKpK6XLWEx-lfg';
	private static $template_name = 'iflist_notification';
	private static $footer_template_name = null;

	//set defaults
	private static $from_name = 'The IF List';
	private static $from_email = 'notifications@iflist.com';

	private static $allowed_admin_emails = array('ian@iflist.com', 
												'iaspangler@yahoo.com',
												'benny@iflist.com', 
												'benny.hung@hotmail.com', 
												'noelspangler@yahoo.com',
												'noel@iflist.com',
												'info@iflist.com',
												'luke@iflist.com', 
												'luke@lucasmonaco.com', 
												'lmonaco@lucasmonaco.com');


	private static function prepareNotificationMail($notification_type, $params = NULL) {

		if (!ENABLE_NOTIFICATIONS && !ENABLE_NOTIFICATIONS_ADMINS) {
			return false;
		}


		//prepare data based on type of email to send
		switch ($notification_type) {

			case "comment_on_proposal": //email to be sent to the proposer when a comment is posted on a proposal

				//gather all proposal data for the given proposal
				$proposal_data = self::getProposalData($params['proposal_id']);
		
				//check that the proposer has not opted out of receiving this type of notification
				if ($proposal_data['notify_on_comments'] == 'N') { return false; }

				//check that the comment poster is not the proposer
				if ($params['poster_id'] == $proposal_data['user_id']) { return false; }

				//handle silhouette image (no picture)
				$params['poster_pic'] = self::filter_email_silhouette_img($params['poster_pic']);

				//organize recipients from proposal data
				$recipients = array(array('email' => $proposal_data['email'], 'name' => $proposal_data['u_name'], 'type' => 'to'));

				//set subject line
				$subject_line = $params['poster_name']." commented on your proposal";

				//prepare options vars for twig template
				$options = array( 'posterName' => $params['poster_name'],
									'posterPic' => $params['poster_pic'],
									'talentName' => $proposal_data['talent_name'],
									'roleName' => $proposal_data['role_name'],
									'titleName' => $proposal_data['title_name'],
									'leftImage' => $proposal_data['talent_pic'],
									'rightImage' => $proposal_data['role_pic'],
									'caption' => self::getProposalSlug($proposal_data['proposal_type'], $proposal_data['title_name'], $proposal_data['format_name']),
									//'proposalUrl' => "https://" . $_SERVER['SERVER_NAME']. "/proposals/" . $proposal_data['proposal_id']
									'proposalUrl' => DOMAIN . "proposals/" . $proposal_data['proposal_id']
								);

				///print("<br>Sending notification email...<br>");
		
				break;
			
			/*case "proposal_on_favorite": //email to be sent to all fans of a profile when a proposal is published

				//gather all proposal data for the given proposal
				$proposal_data = self::getProposalData($params['proposal_id']);
		
				//check that the proposer has not opted out of receiving this type of notification
				if ($proposal_data['notify_on_proposals'] == 'N') { return false; }

				//get all fans data
				$fan_list = getFanList($proposal_data['talent_id'], $proposal_data['role_id'], $proposal_data['title_id'], $poster_id);
			
				//organize list of recipients from fans data
				$recipients = array();
				
				if (!empty($fan_list)) {
						
					for ($n = 0; $n < count($fan_list); $n++) {
					
						/////echo $fan_list[$n]['name'].': '.$fan_list[$n]['email'].', \n';
						
						if ($fan_list[$n]['email'] != "") {
							$recipient = array(
											'fan_type' => $fan_list[$n]['type'],
											'email' => $fan_list[$n]['email'],
											'name' => $fan_list[$n]['name'],
											'type' => 'to'
										);
										
							array_push($recipients, $recipient); 
						}
					}
				}

				if (!empty($recipients)) {
	
					if (strpos($poster_pic, 'https://') === false && strpos($poster_pic, 'http://') === false) {
						///echo 'use silhouette';
						$poster_pic = 'https://iflist.com/email_images/tiny_silhouette.jpg';
					}
					if ($proposal_data['anonymous'] == 'Y') {
						$poster_name = 'Anonymous';
						$poster_pic = 'https://iflist.com/email_images/tiny_silhouette.jpg';
					}
				
				}
			

				$subject_line = getSubjectLine($recipients[$i]['fan_type'], $options['proposalType'], 
								$options['talentName'], $options['roleName'], $options['titleName']);

				$intro_statement = getIntroContent($recipients[$i]['fan_type'], $options['proposalType'], 
									$options['talentName'], $options['roleName'], $options['titleName']);


				//prepare options vars for twig template
				$options = array( 'posterName' => $params['poster_name'],
									'posterPic' => $params['poster_pic'],
									'talentName' => $proposal_data['talent_name'],
									'roleName' => $proposal_data['role_name'],
									'titleName' => $proposal_data['title_name'],
									'leftImage' => $proposal_data['talent_pic'],
									'rightImage' => $proposal_data['role_pic'],
									'caption' => self::getProposalSlug($proposal_data['proposal_type'], $proposal_data['title_name'], $proposal_data['format_name']),
									'proposalUrl' => $proposal_data['proposal_url']
									);

				print("<br>Sending notification email...<br>");
				
				////$intro_statement = "*|INTRO_STATEMENT|*";
				////$subject_line = "*|SUBJECT_LINE|*";

			
				break;
			*/

			case "message_notification": //email to be sent to user when another user sends a chat message

				$user_data = self::getUserData($params['recipient_id']);

				//check that the user has not opted out of receiving this type of notification
				if ($user_data['notify_on_comments'] == 'N') { return false; }

				//organize recipients from user data
				$recipients = array(array('email' => $user_data['email'], 'name' => $user_data['username'], 'type' => "to"));

				//form subject line
				$subject_line = $params['poster_name']." sent you a message";

				//handle silhouette image (no picture)
				$params['poster_pic'] = self::filter_email_silhouette_img($params['poster_pic']);
				

				//prepare options vars for twig template
				$options = array( 'posterId' => $params['poster_id'],
									'posterName' => $params['poster_name'],
									'posterPic' => $params['poster_pic'],
									//'chatUrl' => 'https://'.$_SERVER['SERVER_NAME'].'/chat/'.$params['chat_id']
									'chatUrl' => DOMAIN.'chat/'.$params['chat_id']
								);


				///print("<br>Sending notification email - chatURL: ".$options['chatUrl'].'<br>');
				///exit;

				break;

			case "reset_password":

				$subject_line = "Your Password Reset Request";

				$recipients = array(array('email' => $params['email'], 'name' => $params['first_name'].' '.$params['last_name'], 'type' => "to"));

				self::$from_name = "IF List Support";
				self::$from_email = "support@iflist.com";

				//prepare options vars for twig template
				$options = array('email' => $params['email'],
								'first_name' => $params['first_name'],
								'last_name' => $params['last_name'],
								'reset_link' => $params['reset_link']
								);

				self::$footer_template_name = "reset_password_footer";
				////print("<br>Sending password reset email...<br>");

				break;

		}

	
		//run filter on recipients if we are in admin testing mode
		if (ENABLE_NOTIFICATIONS_ADMINS) {
			$recipients = array_filter($recipients, array(__CLASS__, 'filter_admins_only'));
		}

		return array('recipients'=>$recipients, 'subject_line'=>$subject_line, 'options'=>$options);
		
		
	}


	public static function sendMail($notification_type, $params) {
		
		global $templateMgr;

		$mail_data = self::prepareNotificationMail($notification_type, $params);
		$subject_line = $mail_data['subject_line'];
		$options = $mail_data['options'];
		$recipients = $mail_data['recipients'];

		if (empty($recipients)) { exit; }

		//print_r($recipients);
		//exit;
		
		//connect to Mandrill and pass in API key
		$mandrill = new \Mandrill(self::$api_key);
	
		try {
		
		
			$vars = array();

			for ($i = 0; $i < count($recipients); $i++) {
				array_push($vars, array(
					'rcpt' => $recipients[$i]['email'],
					'vars' => array(
						array(
							'name' => 'to_addr',
							'content' => $recipients[$i]['email']
						)
				
					)
				));
				

			}


	
			$template_content = array(
				array(
					'name' => 'content_area',
					'content' => $templateMgr->load( '/email/'.$notification_type.'.html', $options)
				)

			);

			//check for alternate footer template
			if (self::$footer_template_name != null) {
				array_push($template_content, array(
					'name' => 'footer_area',
					'content' => $templateMgr->load( '/email/'.self::$footer_template_name.'.html', array())
				));
			}

			///return $templateMgr->load( '/email/'.$notification_type.'.html', $options);
			///exit;

			$message = array(
				///'html' => '<p>Example HTML content</p>',
				///'text' => 'Example text content',
				///'subject' => "TEST SUBJECT",
				'subject' => $subject_line,
				'from_email' => self::$from_email,
				'from_name' => self::$from_name,
				'to' => $recipients,
				'headers' => array('Reply-To' => 'no-reply@iflist.com'),
				'important' => false,
				'track_opens' => true,
				'track_clicks' => true,
				'auto_text' => false,
				'auto_html' => true,
				'inline_css' => false,
				'url_strip_qs' => false,
				'preserve_recipients' => false,
				'view_content_link' => false,
				'tracking_domain' => null,
				'signing_domain' => null,
				'return_path_domain' => null,
				'merge' => true,
				'merge_vars' => $vars

				
				/*'global_merge_vars' => array(
					array(
						'name' => 'TO_ADDR',
						'content' => 'iaspangler@yahoo.com'
					)
				)*/
				
				
				/*
				'tags' => array()
				'subaccount' => null,
				'google_analytics_domains' => array(),
				'google_analytics_campaign' => 'info@iflist.com',
				'metadata' => array('website' => 'www.iflist.com'),
				'recipient_metadata' => array(
					array(
						'rcpt' => $toAddr,
						'values' => array('user_id' => $toId)
					)
				),
				'attachments' => array(
					array(
						'type' => 'text/plain',
						'name' => 'myfile.txt',
						'content' => 'ZXhhbXBsZSBmaWxl'
					)
				),
				'images' => array(
					array(
						'type' => 'image/png',
						'name' => 'IMAGECID',
						'content' => 'ZXhhbXBsZSBmaWxl'
					)
				)*/
			);
			$async = false;
			//$ip_pool = 'Main Pool';
			
			///print_r($message);
			
			///$send_at = date('Y-m-d H:i:s'); //send immediately
			///$send_at = '2014-03-04 23:25:54';
			$result = $mandrill->messages->sendTemplate(self::$template_name, $template_content, $message, $async);
			
			///return print_r($result);
			//print_r($result);
			
			/*$id = $result[0][_id];
			echo $id.'<br>';
			$response = $mandrill->messages->info($id);
			print_r($response);*/
			
			
		} catch(Mandrill_Error $e) {
			// Mandrill errors are thrown as exceptions
			return 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
			// A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
			throw $e;
		}
	}
	

	private static function getIntroContent($fan_type, $proposalType, $talentName, $roleName, $titleName) {
		if ($fan_type == "talent") {
			if ($proposalType == "casting") {
				return "cast ".$talentName." in a role";
			}
			else {
				return "proposed ".$talentName." for a project";
			}
		}
		else if ($fan_type == "role") {
			return "cast an actor to play ".$roleName;
		}
		else if ($fan_type == "title") {
			return "made a proposal for ".$titleName;
		}
	}

	private static function getSubjectLine($fan_type, $proposalType, $talentName, $roleName, $titleName) {
		if ($fan_type == "talent") {
			if ($proposalType == "casting") {
				return $talentName." is proposed for a new role";
			}
			else {
				return $talentName." is proposed for a new project";
			}
		}
		else if ($fan_type == "role") {
			return $roleName." has a new actor proposed";
		}
		else if ($fan_type == "title") {
			return $titleName." has a new proposal";
		}
	}


	private static function getProposalSlug($proposalType, $titleName, $titleFormat) {

		if ($proposalType == "casting" && $titleName == "") {
			return "Casting Proposal";
		}
		else {
			return $titleName.' ('.$titleFormat.')';
		}

	}


	private static function getProposalData($proposal_id) {
		
		global $dbHandler;

		$sql = "SELECT u.user_id, u.notify_on_comments, u.notify_on_proposals, CONCAT(u.first_name,' ',u.last_name) AS u_name, 
					u.email, CONCAT(t.first_name,' ',t.last_name) AS talent_name, t.talent_id, 
					t.main_pic AS talent_pic, r.main_pic AS role_pic, r.role_id, t.talent_id, ti.title_id,  
					r.role_id, r.name AS role_name, ti.title_id, ti.name AS title_name, ti.main_pic AS title_pic, 
					tm.filename AS t_filename, rm.filename AS r_filename, p.proposal_id, p.proposal_type, p.anonymous,  
					i.identity_name, i.identity_id, sf.format_name 
				FROM proposals p
				 LEFT JOIN talent t ON t.talent_id = p.talent_id
				 LEFT JOIN roles r ON r.role_id = p.role_id
				 LEFT JOIN identities i ON i.identity_id = p.crew_position_id
				 LEFT JOIN titles ti ON ti.title_id = p.title_id
				 LEFT JOIN source_formats sf ON sf.format_id = ti.source_format_id 
				 LEFT JOIN media tm ON tm.media_id = p.talent_pic_id
				 LEFT JOIN media rm ON rm.media_id = p.role_pic_id
				 LEFT JOIN users u ON u.user_id = p.user_id
				 WHERE p.proposal_id = ".$proposal_id." LIMIT 1";
	

		/******CACHING *******/
		$key_name = "email_proposaldata_".$proposal_id;
		// pass expiration time (in seconds) for cache objects to expire
		$expires = (60 * 30); // 30 minutes

		////error_log("===Setting Key Name ".$key_name, 0);

		$result = $dbHandler->get($sql, $key_name, $expires);			

		if (!is_array($result)) { 
			$err = 'ERROR: Result is not an array '.print_r($result, true);
			error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
    		throw new Exception(__CLASS__.':'.__FUNCTION__ . " (1) Error Processing Request ($err. SQL: $sql)", 1);
			return false;	
		}

		$data = self::process_proposal_data($result); 

		return $data;
	}


	private static function process_proposal_data($proposal_data) {
		
		$row = $proposal_data[0];
			
		//MAIN CHARACTER THUMBNAIL
		if ($row['role_pic'] != "") {
			$role_pic_url = CLOUDFRONT . ROLE_PICS_DIR . $row['role_id'] .'/SM_'.$row['role_pic'];
		} 
		else if ($row['title_pic'] != "") {
			$role_pic_url = CLOUDFRONT . TITLE_PICS_DIR . $row['title_id'] .'/SM_'.$row['title_pic'];
		}
		else {
			$role_pic_url = "";
		}

					
		//IN-CHAR PICS
		if ($row['r_filename'] != "") {
			$role_inchar_pic_url = CLOUDFRONT . ROLE_PICS_DIR . $row['role_id'] .'/SM_'.$row['r_filename'];
		} 
		else if ($role_pic_url != "") {
			$role_inchar_pic_url = $role_pic_url;
		} 
		
		if ($row['role_name'] != "") {
			$role_name = $row['role_name'];
			$role_id = $row['role_id'];
		}
		else {
			$role_name = ucfirst($row['identity_name']);
			$role_id = "";
		}
		
		
		if ($row['t_filename'] != "") {
			$talent_inchar_pic = $row['t_filename'];
		} else {
			$talent_inchar_pic = $row['talent_pic'];
		}

		$talent_inchar_pic_url = CLOUDFRONT . TALENT_PICS_DIR . $row['talent_id'] .'/SM_' . $talent_inchar_pic;
		
		$proposal_data[0]['talent_pic'] = $talent_inchar_pic_url;
		$proposal_data[0]['role_pic'] = $role_inchar_pic_url;
		$proposal_data[0]['role_id'] = $role_id;
		$proposal_data[0]['role_name'] = $role_name;
		$proposal_data[0]['format_name'] = $row['format_name'];
		
		return $proposal_data[0];
	}


	private static function getUserData($user_id) {

		global $dbHandler;

		$sql = "SELECT u.username, u.email, u.notify_on_comments FROM users u WHERE u.user_id = ".$user_id." LIMIT 1";
	
		/******CACHING *******/
		$key_name = "email_userdata_".$user_id;
		// pass expiration time (in seconds) for cache objects to expire
		$expires = (60 * 30); // 30 minutes

		////error_log("===Setting Key Name ".$key_name, 0);

		$result = $dbHandler->get($sql, $key_name, $expires);			

		if (!is_array($result)) { 
			$err = 'ERROR: Result is not an array '.print_r($result, true);
			error_log(__CLASS__.':'.__FUNCTION__.': '.$err);
    		throw new Exception(__CLASS__.':'.__FUNCTION__ . " (1) Error Processing Request ($err. SQL: $sql)", 1);
			return false;	
		}

		return $result[0];
		
	}

	/*
	* Images must be absolute URLs for email so we need to grab the email version instead of using the "images/"" one
	*/
	private static function filter_email_silhouette_img($pic_url) {
		if (strpos($pic_url, 'https://') === false && strpos($pic_url, 'http://') === false) {
			///echo 'use silhouette';
			return 'http://iflist.com/email_images/tiny_silhouette.jpg';
		}

		return $pic_url;
	}

	/* filters out any emails that aren't in the list of allowed */
	private static function filter_admins_only($item) {
		
		if (in_array($item['email'], self::$allowed_admin_emails)) {
			return 1;
		}
	}


}