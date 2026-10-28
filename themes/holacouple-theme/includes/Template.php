<?php

namespace HWTheme;

class Template{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
                
        add_action( 'wp_footer', [ $this, 'offcanvas' ] );
        add_action( 'hw_theme_header', [ $this, 'display_header' ] );
        add_action( 'hw_header_content', [ $this, 'display_main_navigation_menu'], 10);
        add_action( 'hw_header_content', [ $this, 'display_user_login'], 20);
    }
    
    public function offcanvas(){
        
        get_template_part( 'template-parts/offcanvas' );
    }

    /**
     * display the header
     *
     * @return void
     */ 
    public function display_header(){

        get_template_part( 'template-parts/header/default' );
    }

    /**
     * display main navigation menu
     *
     * @return void
     */
    public function display_main_navigation_menu(){

        if(!hw_is_pos()){
            get_template_part( 'template-parts/header/content/main-menu' );
        }
    }

    /**
     * display user login
     *
     * @return void
     */
    public function display_user_login(){

        get_template_part( 'template-parts/header/content/user-login' );
    }
}