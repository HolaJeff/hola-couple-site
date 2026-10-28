<?php

DEFINE( 'HOLA_THEME_TROPICAL_DIR', get_stylesheet_directory());
DEFINE( 'HOLA_THEME_TROPICAL_INCLUDES', HOLA_THEME_TROPICAL_DIR . '/includes' );

define( 'HOLA_THEME_TROPICAL_URL' , get_stylesheet_directory_uri() );
define( 'HOLA_THEME_TROPICAL_ASSETS_URL' , HOLA_THEME_TROPICAL_URL . '/assets' );

require_once( HOLA_THEME_TROPICAL_INCLUDES . '/hooks.php' );
require_once( HOLA_THEME_TROPICAL_INCLUDES . '/functions.php' );
require_once( HOLA_THEME_TROPICAL_INCLUDES . '/redux-framework.php' );