<?php
	

	namespace app\Services;

	require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/app_global.php';  

	
	use app\Services\Mailer\Mailer;


	//SEND TEST EMAILS
	/*$mail = Mailer::sendMail("comment_on_proposal", array(array('email' => "iaspangler@yahoo.com", 'name' => "Ian Spangler", 'type' => "to")), 
						array('posterName' => "Benny Hung", 
								'posterPic' => "https://graph.facebook.com/511977955/picture?width=30&height=30", 
								'leftImage' => "https://d7ojlivkn76yb.cloudfront.net/images/talent/482/SM_Queen_Igraine_1372886733.59.jpg",
								'rightImage' => "https://d7ojlivkn76yb.cloudfront.net/images/roles/67/SM_demeter-statue_1372882366.32.jpg", 
								'talentName' => "Claire Forlani", 
								'roleName' => "Demeter", 
								'titleName' => "", 
								'titleFormat' => "",
								'proposalUrl' => $_SERVER['SERVER_NAME']."/proposals/647",
								'proposalType' => "casting"));*/


	$mail = Mailer::prepareNotificationMail("message_notification",
				array( 'poster_id' => 1,
						'poster_name' => "Benny Hung",
						'poster_pic' => "https://graph.facebook.com/511977955/picture?width=50&height=50"
					));



	echo $mail;