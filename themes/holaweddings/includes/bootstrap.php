<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_THEME_URL' , get_template_directory_uri() );
define( 'HW_THEME_PATH', dirname(__DIR__) );
define( 'HW_THEME_INCLUDES_PATH', HW_THEME_PATH . '/includes' );

if(!class_exists('Psr4AutoloaderClass')){
	
	require_once( HW_THEME_INCLUDES_PATH . '/class-autoloader.php' );
}

// instantiate the loader
$loader = Psr4AutoloaderClass::getInstance();

// register the autoloader
$loader->register();
  
// register the base directories for the namespace prefix
$loader->addNamespace( 'HWTheme', HW_THEME_INCLUDES_PATH );

//require_once( HW_THEME_PATH . '/vendor/autoload.php' );

//require_once( HW_THEME_INCLUDES_PATH . '/template-functions.php' );

function hw_theme(){
    return \HWTheme\App::getInstance();
}
hw_theme();