<?php

use HW\App;

/**
 * display the logo
 *
 * @return void
 */
function hw_theme_display_logo(){

    if(!hw_theme_header_display_logo()){
        return;
    }
    
    get_template_part('template-parts/header/content/logo');
}
add_action('hw_header_content', 'hw_theme_display_logo', 5);

/**
 * display the logo decoration
 *
 * @return void
 */
function hw_display_header_logo_decoration(){

    if(!hw_theme_header_display_logo_decoration()){
        return;
    }

    $image_url = hw_theme_header_get_logo_decoration_url();
    echo sprintf('<img class="logo-decoration" src="%s" alt="logo decoration" />', $image_url );
}
add_action( 'hw_theme_before_logo', 'hw_display_header_logo_decoration', 10);

/**
 * add body classes
 *
 * @param array $classes
 * @return array
 */
function hw_theme_classic_add_body_classes($classes){

    $theme = wp_get_theme();

    $classes[] = strtolower(str_replace(' ', '-', $theme->get('Name')));

    return $classes;
}
add_filter('body_class', 'hw_theme_classic_add_body_classes');