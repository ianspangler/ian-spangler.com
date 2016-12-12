<?php
namespace app\Services;
use StdClass;

// @ TODO : UPDATE THIS PATH
require_once $_SERVER['DOCUMENT_ROOT'] .'/tutorials/handlebars_twig/app/lib'.'/vendor/twig/twig/lib/Twig/Autoloader.php';
//require_once $_SERVER['DOCUMENT_ROOT'] .'/app/Router.php';

\Twig_Autoloader::register();

/* auto load the instance */
if(!isset($templateMgr)){
	$templateMgr = load_templateMgr();
	$templateMgr->debug_log("=== templateMgr loaded ==");
}


function load_templateMgr(){ 

	//set default
	$OUTPUT_FORMAT = "desktop";

	////$OUTPUT_FORMAT = ( Router::device_is_mobile() ? 'mobile' : 'desktop') ;

	//print "OUTPUT_FORMAT = $OUTPUT_FORMAT ";


	$loader = new \Twig_Loader_Filesystem( array(
			$_SERVER['DOCUMENT_ROOT'] .'/tutorials/handlebars_twig/app/twig/templates/'.$OUTPUT_FORMAT,
			$_SERVER['DOCUMENT_ROOT'] .'/tutorials/handlebars_twig/app/twig/templates/'    // find '/shared/' files in here
		)
	);
	


	$options ;
	if(DEBUGGING){
		$options= array(
		    'cache' => false, //$_SERVER['DOCUMENT_ROOT'] .'/app/twig/compilation_cache',
		    'debug' => true
		);
	}else{
		$options= array(
	    	'cache' => $_SERVER['DOCUMENT_ROOT'] .'/tutorials/handlebars_twig/app/twig/compilation_cache',
		    'debug' => false,
		);		
	}
	
	$twig = new \Twig_Environment($loader, $options);

	/* 
	globals to be accessed from each base PHP page 
	*/
	$tm = new TemplateManager();
	$tm->init($loader, $twig);
	return $tm;
}


class TemplateManager{

	var $debug_str = "";
	var $debug_template = "shared/super_debug.html";

	function init($loader, $twig){
		$this->loader = $loader;

		$this->twig = $twig;
		$this->register_functions();
	}
	
	function register_functions(){
		

		// wrapper for ucfirst
		$function = new \Twig_SimpleFunction('ucfirst', function ($string) {
		    return ucfirst($string);
		});
		$this->twig->addFunction($function);
		//
		$function = new \Twig_SimpleFunction('lcfirst', function ($string) {
		    return lcfirst($string);
		});
		$this->twig->addFunction($function);
		//
		// wrapper for str_replace
		$function = new \Twig_SimpleFunction('string_replace', function ($pattern, $string, $original_string) {
		    return str_replace($pattern, $string, $original_string);
		});
		$this->twig->addFunction($function);
		//

		$function = new \Twig_SimpleFunction('number_format', function($number, $place = 0){
			return number_format( $number , $place );
		});
		$this->twig->addFunction($function);

		//get time elapsed
		$function = new \Twig_SimpleFunction('get_time_elapsed', function($timestamp) {
			return getTimeElapsed( strtotime($timestamp) )." ago";
		});
		$this->twig->addFunction($function);

		// nl2br
		$function = new \Twig_SimpleFunction('newline2Break', function($str){
			return nl2br($str);
		});
		$this->twig->addFunction($function);



		$function = new \Twig_SimpleFunction('replaceSpecialCharacters', function($str){
			return replaceSpecialCharacters($str);
		});

		$this->twig->addFunction($function);


		//custom filter
		$filter = new \Twig_SimpleFilter('myraw', 'raw');
		$this->twig->addFilter($filter);
		

	}



	
	/* wrapper for twig render */
	function render($tpl, $options = null){
		if($options == null){ $options = array(); }
		echo $this->twig->render($tpl, $options);
		return true;
	}

	/* wrapper for twig load */
	function load($tpl, $options = null){
		if($options == null){ $options = array(); }
		return $this->twig->render($tpl, $options);
	}

	//util
	function debug_log($arr){
		if(is_array($arr)){
			$str = print_r($arr,true);
		}else{
			$str = $arr;
		}
		$this->debug_str .= $str . "\n ------------- \n";
	}
	/*
	call this to display the Div in the window 
	*/
	
	function debug_out(){
		echo $this->twig->render($this->debug_template, array('str'=>$this->debug_str) );
	}
}
