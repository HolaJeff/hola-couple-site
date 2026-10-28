<div class="menus-wrapper d-flex flex-row flex-nowrap pl-3 flex-lg-grow-1 ml-auto ml-lg-0">
        
    <nav class="main-menu clearfix d-none d-lg-block ">
    
        <?php
        if( has_nav_menu( 'main-menu' ) ):
                    
            wp_nav_menu([
                'theme_location' => 'main-menu',
                'menu_class' => 'nav justify-content-' . hw_theme_get_main_menu_alignment(),
                'container_class' => 'flex-grow-1'
            ]);
            
        endif;
        ?>
        
    </nav>
    
    <nav class="mobile-menu clearfix">
    
        <a href="#offcanvas" class="offcanvas-trigger d-lg-none">
            <span></span>
        </a>
        
    </nav>
    
</div>