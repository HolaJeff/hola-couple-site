<?php

namespace HWTheme;

class Template{
    
    use \HWTheme\Traits\Singleton;
    
    public function init(){
                
        add_action( 'wp_footer', [ $this, 'offcanvas' ] );
    }
    
    public function offcanvas(){
        
        get_template_part( 'template-parts/offcanvas' );
    }
}