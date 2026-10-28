<?php

if ( class_exists( 'Redux' ) ) {

    $opt_name = 'hw_theme';

    /**
     * register default main menu alignment
     *
     * @param array $args
     * @return array
     */
    function hw_tropical_theme_register_main_menu_alignment($args){

        $args['default'] = 'end';

        return $args;
    }
    add_filter('redux/options/'.$opt_name.'/field/main_menu_alignment/register', 'hw_tropical_theme_register_main_menu_alignment');

    /**
     * register default header typography
     *
     * @param array $args
     * @return array
     */
    function hw_tropical_theme_register_default_typography($args){

        $args['default']['font-family'] = 'Cormorant Garamond';

        return $args;
    }
    add_filter('redux/options/'.$opt_name.'/field/body_typography/register', 'hw_tropical_theme_register_default_typography');
    add_filter('redux/options/'.$opt_name.'/field/header_typography/register', 'hw_tropical_theme_register_default_typography');
}