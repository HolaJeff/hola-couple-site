<?php

use HW\App;

/**
 * display the logo
 *
 * @return void
 */
function hw_theme_display_logo(){

    global $hw_theme;

    $show = $hw_theme['show_site_logo'] ?? true;

    if(!$show){
        return;
    }
    
    get_template_part('template-parts/header/content/logo');
}
add_action('hw_header_content', 'hw_theme_display_logo', 5);

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