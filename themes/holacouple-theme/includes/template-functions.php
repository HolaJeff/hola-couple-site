<?php

if( !function_exists( 'hw_theme_get_svg_icon' ) ){
    
    function hw_theme_get_svg_icon( $id ){
        
        $icons_path = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'svg' . DIRECTORY_SEPARATOR;
        
        if( $id && file_exists( $icons_path . $id . '.svg' ) ){
            
            echo file_get_contents( $icons_path . $id . '.svg' );
        }
    }
}