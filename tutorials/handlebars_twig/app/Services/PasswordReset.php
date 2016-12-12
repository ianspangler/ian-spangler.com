<?php

namespace app\Services;

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/app_global.php';

use app\Services\Mailer\Mailer;


class PasswordReset {

	public static function sendPasswordMail($email, $first_name, $last_name, $reset_link) {
		
		Mailer::sendMail("reset_password", array('email'=>$email,'first_name'=>$first_name,'last_name'=>$last_name,'reset_link'=>$reset_link));
	
		header("Location: ".DOMAIN."?submit_success=Y#forgot_password");
		
		exit;
	
	}

}
