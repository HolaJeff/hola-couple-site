<?php

namespace HWTheme;

class Widgets{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
                
         add_action( 'widgets_init', [$this, 'widgets_init'] );
        
        add_filter( 'register_sidebar_defaults', [$this, 'register_sidebar_defaults' ], 10, 1 );
        add_filter( 'widget_nav_menu_args', [$this, 'widget_nav_menu_args'], 20, 4);
    }
    
    public function widgets_init(){
        
        register_sidebar( array(
            'name'          => 'Offcanvas',
            'id'            => 'offcanvas',
        ) );
    }
    
    public function register_sidebar_defaults( $defaults ){
        
        $defaults['before_widget'] = '<div id="%1$s" class="widget %2$s">';
        $defaults['after_widget']  = '</div>';
        $defaults['before_title']  = '<h4 class="widget-title"><span>';
        $defaults['after_title']  = '</span></h4>';
        
        return $defaults;
    }
    
    public function widget_nav_menu_args($nav_menu_args, $nav_menu, $args, $instance){
        
        $custom_args =  array(
            'menu_class' => 'nav menu',
            'walker' => new NavWalker()
        );
        
        if( $args['id'] == 'footer_1' || $args['id'] == 'footer_2' ){
            
            $custom_args['menu_class'] .= ' flex-column';
        }
        
        return array_merge( $nav_menu_args, $custom_args);
    }
}