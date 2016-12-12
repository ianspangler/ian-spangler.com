<?php


	require_once $_SERVER['DOCUMENT_ROOT'] . '/tutorials/handlebars_twig/app/Services/TemplateManager.php'; 


	
	//users -- example dummy data
	$user_items = array( array('user_id'=>1, 
								'user_name'=>"Patty Jones", 
								'user_pic'=>'/path/to/image1.jpg', 
								'sample comment text1'),
						array('user_id'=>2, 
								'user_name'=>"John Smith", 
								'user_pic'=>'/path/to/image2.jpg',
								'comment_text'=>'sample comment text2'),
						array('user_id'=>3, 
								'user_name'=>"Barry Lewis",
								'user_pic'=>'/path/to/image3.jpg',
								'comment_text'=>'sample comment text2') );

	
	$templateMgr->render('/page_with_user_list.html', 
		array(
			'user_items' => $user_items, //object containing array of user data
		));
