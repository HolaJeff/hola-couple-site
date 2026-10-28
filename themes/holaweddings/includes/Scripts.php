<?php

namespace HWTheme;

class Scripts{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
        
       add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts(){
        
        wp_enqueue_style('bootstrap', HW_THEME_URL . '/assets/vendor/bootstrap/bootstrap.min.css');
        wp_enqueue_style('hw-main', HW_THEME_URL . '/assets/style/main.css');
        wp_enqueue_style('hw-nav', HW_THEME_URL . '/assets/style/main-nav.css');
        
        wp_enqueue_script( 'jquery');
        wp_enqueue_script('bootstrap', HW_THEME_URL . '/assets/vendor/bootstrap/bootstrap.min.js');
        wp_enqueue_script('hw-template', HW_THEME_URL . '/assets/js/main.js');
    }
}