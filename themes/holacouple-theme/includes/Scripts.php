<?php

namespace HWTheme;

class Scripts{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
        
       add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts(){
        
        wp_enqueue_style('hw-theme-main', HW_THEME_URL . '/assets/dist/css/main.min.css');

        // add the classic theme, this style is specific to the main theme and should to apply to the child themes
        if(!is_child_theme()){
            wp_enqueue_style('hw-theme-classic', HW_THEME_URL . '/assets/dist/css/theme.min.css');
        }
        
        wp_enqueue_script( 'jquery');
        wp_enqueue_script('hw-theme', HW_THEME_URL . '/assets/js/main.js');
    }
}