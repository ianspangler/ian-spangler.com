<?php

# load env file
define('DOCROOT','/Library/WebServer/Documents/iflist/iflist-dev.com');

/*
     this global function is called automatically whenever a class is instantiated
*/
spl_autoload_register(function($class) {
    if (strpos($class, 'app\\') === 0) { // $name = substr($class, strlen('Services')); #$file = $_SERVER['DOCUMENT_ROOT'] . '/app/'.strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php'; #print $file;
        require_once DOCROOT. '/'.strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
