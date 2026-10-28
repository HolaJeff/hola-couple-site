<?php

/**
 * enqueue scripts
 *
 * @return void
 */
function hw_theme_tropical_enqueue_scripts(){

    wp_enqueue_style( 'hw-theme-tropical', HOLA_THEME_TROPICAL_ASSETS_URL . '/dist/css/theme.min.css' );

    wp_enqueue_script( 'hw-theme-tropical', HOLA_THEME_TROPICAL_ASSETS_URL . '/dist/js/index.js' );
}
add_action('wp_enqueue_scripts', 'hw_theme_tropical_enqueue_scripts', 20 );

/**
 * display svg before logo
 *
 * @return void
 */
function hw_theme_tropical_before_logo(){

    hw_theme_get_svg_icon('shell');
}
add_action('hw_theme_before_logo', 'hw_theme_tropical_before_logo' );