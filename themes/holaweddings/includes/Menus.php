<?php

namespace HWTheme;

class Menus{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
        
        add_action( 'init', [ $this, 'register_menus' ]  );
        
        add_filter( 'wp_nav_menu_args', [$this, 'nav_menu_args'] );
    }
    
     public function register_menus() {
        
        register_nav_menus(
            [
                'main-menu' => __( 'Main Menu', 'hw' ),
                'footer-menu' => __( 'Footer Menu', 'hw' ),
            ]
        );
    }
    
     public function nav_menu_args( $args ){
        
        $args = array_merge( $args, [
            'menu_class' => $args[ 'menu_class' ] . ' nav',
            'container' => 'nav',
            'walker' => new NavWalker()
        ] );
        
        if( $args['theme_location'] == 'main-menu' ){
            
            $args['container'] = '';
        }
        
        return $args;
    }
}