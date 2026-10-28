<?php

if ( class_exists( 'Redux' ) ) {

    $opt_name = 'hw_theme';

    /**
     * register default main menu alignment
     *
     * @param array $args
     * @return array
     */
    function hw_elegant_theme_register_main_menu_alignment($args){

        $args['default'] = 'end';

        return $args;
    }
    add_filter('redux/options/'.$opt_name.'/field/main_menu_alignment/register', 'hw_elegant_theme_register_main_menu_alignment');

    /**
     * register default header typography
     *
     * @param array $args
     * @return array
     */
    function hw_elegant_theme_register_header_typography($args){

        $args['default']['font-family'] = 'Playfair Display';

        return $args;
    }
    add_filter('redux/options/'.$opt_name.'/field/header_typography/register', 'hw_elegant_theme_register_header_typography');

    /**
     * edit color sections
     *
     * @param array $sections
     * @return array
     */
    function hw_elegance_add_extra_field_to_section($sections) {
        
        foreach ($sections as &$section) {
            
            if (isset($section['id']) && $section['id'] === 'colors') {
                
                $section['fields'][] = [
                    'id'       => 'footer_top_bg',
                    'type'     => 'color',
                    'title'    => 'Footer Top Background',
                ];
            }
        }
        return $sections;
    }
    add_filter('redux/options/'.$opt_name.'/sections', 'hw_elegance_add_extra_field_to_section');
}

/**
 * print CSS default variables from Redux
 *
 * @return void
 */
function hw_theme_elegance_add_redux_css_font_variables(){

    global $hw_theme;

    ?>
    <style>
        :root{
            <?php hw_redux_helper_print_variable('hw-footer-top-bg-color', $hw_theme['footer_top_bg']) ?>
        }
    </style>
    <?php
}
add_action('wp_head', 'hw_theme_elegance_add_redux_css_font_variables', 12);