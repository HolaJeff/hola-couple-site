<?php

/**
 * enqueue scripts
 *
 * @return void
 */
function hw_theme_elegant_enqueue_scripts(){

    wp_enqueue_style( 'hw-theme-elegant', HOLA_THEME_ELEGANT_ASSETS_URL . '/dist/css/theme.min.css' );
}
add_action('wp_enqueue_scripts', 'hw_theme_elegant_enqueue_scripts', 20 );