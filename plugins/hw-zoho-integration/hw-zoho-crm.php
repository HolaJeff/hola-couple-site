<?php
/**
 * Plugin Name: Zoho CRM for hola Weddings
 * Plugin URI:  https://mcweb.mx
 * Description: Zoho CRM for hola Weddings
 * Author:      Marco Cariolato
 * Author URI:  https://mcweb.mx
 * Version:     1.2.10
 * Text Domain: hw-zoho
 * Domain Path: languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if the main plugin is not activated exit
if(!class_exists(HW\App::class)){
    return;
}

define( 'HW_ZOHO_URL' , plugin_dir_url( __FILE__ ) );
define( 'HW_ZOHO_ASSETS_URL' , HW_ZOHO_URL . 'assets' );
define( 'HW_ZOHO_ADMIN_ASSETS_URL' , HW_ZOHO_ASSETS_URL . '/admin' );
define( 'HW_ZOHO_VENDOR_URL' , HW_ZOHO_ASSETS_URL . '/vendor' );

define( 'HW_ZOHO_PLUGIN_PATH', __DIR__ );
define( 'HW_ZOHO_INCLUDES_PATH', HW_ZOHO_PLUGIN_PATH . '/includes' );
define( 'HW_ZOHO_ASSETS_PATH', HW_ZOHO_PLUGIN_PATH . '/assets' );
define( 'HW_ZOHO_TEMPLATE_PARTS', HW_ZOHO_PLUGIN_PATH . '/template-parts' );

if( !class_exists( 'Psr4AutoloaderClass' ) ){
    
    require_once( HW_ZOHO_PLUGIN_PATH . '/class-autoloader.php' );
}

// instantiate the loader
$loader = Psr4AutoloaderClass::getInstance();

// register the autoloader
$loader->register();
  
// register the base directories for the namespace prefix
$loader->addNamespace( 'HWZoho', HW_ZOHO_INCLUDES_PATH );

require_once( HW_ZOHO_PLUGIN_PATH . '/vendor/autoload.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-notice-functions.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-template-functions.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-functions.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-hooks.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-ajax-functions.php' );
require_once( HW_ZOHO_INCLUDES_PATH . '/hwzi-payments-functions.php' );

/**
 * Init updater
 */
//$updater = new \HW\Updater\Updater(__FILE__);

function hwzoho(){
    return \HWZoho\App::getInstance();
}
hwzoho();
