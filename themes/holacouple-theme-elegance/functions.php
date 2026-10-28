<?php

DEFINE( 'HOLA_THEME_ELEGANT_DIR', get_stylesheet_directory());
DEFINE( 'HOLA_THEME_ELEGANT_INCLUDES', HOLA_THEME_ELEGANT_DIR . '/includes' );

define( 'HOLA_THEME_ELEGANT_URL' , get_stylesheet_directory_uri() );
define( 'HOLA_THEME_ELEGANT_ASSETS_URL' , HOLA_THEME_ELEGANT_URL . '/assets' );

require_once( HOLA_THEME_ELEGANT_INCLUDES . '/redux-framework.php' );
require_once( HOLA_THEME_ELEGANT_INCLUDES . '/hooks.php' );
require_once( HOLA_THEME_ELEGANT_INCLUDES . '/functions.php' );
require_once( HOLA_THEME_ELEGANT_INCLUDES . '/template-hooks.php' );