<?php

namespace HWTheme;

class NavWalker extends \Walker_Nav_Menu{
    
    public function __construct(){
        
        $this->init();
    }
    
    protected function init(){
        
        add_filter('nav_menu_submenu_css_class', [$this, 'menu_submenu_css_class'], 10, 3);
        add_filter('nav_menu_link_attributes', [$this, 'menu_link_attributes'], 10, 4);
        add_filter('nav_menu_css_class', [$this, 'menu_css_class'], 10, 4);
    }
    
    public function menu_css_class($classes, $item, $args, $depth){
        
        $classes[] = 'nav-item';
        
        if($this->has_children){
            
            $classes[] = 'dropdown';
        }
        
        return $classes;
    }
    
    public function menu_link_attributes($atts, $item, $args, $depth){
        
        if($this->has_children){
            
            //$atts['class'] .= ' dropdown-toggle';
        }
        
        $atts['class'] = ( $atts['class'] ?? '' ) . ' nav-link';
        
        return $atts;
    }
    
    public function menu_submenu_css_class($classes, $args, $depth){
        
        $classes[] = 'dropdown-menu';
        
        return $classes;
    }
    
}