<?php

/**
 * return menu alignment
 *
 * @return string
 */
function hw_theme_get_main_menu_alignment(){    

    global $hw_theme;

    return $hw_theme['main_menu_alignment'] ?? 'center';
}

/**
 * return if header has container
 *
 * @return boolean
 */
function hw_theme_header_has_container(){

    global $hw_theme;

    return hw_evaluate_string_to_boolean($hw_theme['header_display_container'] ?? true);
}

/**
 * return if header display the logo
 *
 * @return boolean
 */
function hw_theme_header_display_logo(){

    global $hw_theme;

    return hw_evaluate_string_to_boolean($hw_theme['show_site_logo'] ?? true);
}

/**
 * return if header display the logo separator
 *
 * @return boolean
 */
function hw_theme_header_display_logo_separator(){

    global $hw_theme;

    return hw_evaluate_string_to_boolean($hw_theme['header_logo_display_separator'] ?? true);
}

/**
 * return if header display the logo decoration
 *
 * @return boolean
 */
function hw_theme_header_display_logo_decoration(){

    global $hw_theme;

    return hw_evaluate_string_to_boolean($hw_theme['show_site_logo_decoration'] ?? true);
}

/**
 * return if display the logo in the footer
 *
 * @return boolean
 */
function hw_theme_footer_display_logo(){

    global $hw_theme;

    return hw_evaluate_string_to_boolean($hw_theme['footer_display_logo'] ?? true);
}

/**
 * return header logo decoration url
 *
 * @return string
 */
function hw_theme_header_get_logo_decoration_url(){

    global $hw_theme;

    $image_url = apply_filters('hw_theme_header_default_logo_decoration_url', HW_THEME_ASSETS_URL . '/images/section-title.png');

    if(!empty($hw_theme['site_logo_decoration_image']['url'])){

        $image_url = $hw_theme['site_logo_decoration_image']['url'];
    }

    return $image_url;
}

/**
 * return if the header logo decoration image has been set in the options
 *
 * @return boolean
 */
function hw_theme_header_has_custom_logo_decoration(){

    global $hw_theme;

    return !empty($hw_theme['site_logo_decoration_image']['url']);
}

/**
 * return header logo decoration height in pixels
 *
 * @param string $device mobile|desktop
 * @return int
 */
function hw_theme_header_get_logo_decoration_height( $device = 'mobile' ){

    global $hw_theme;

    $defaults = [ 'mobile' => 40, 'desktop' => 50 ];

    if( !isset($defaults[$device]) ){

        $device = 'mobile';
    }

    $value = $hw_theme["site_logo_decoration_height_{$device}"] ?? '';

    return is_numeric($value) ? (int) $value : $defaults[$device];
}
