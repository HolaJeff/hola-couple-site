<div class="menus-wrapper d-flex flex-row flex-nowrap">
        
    <nav class="main-menu clearfix">
    
        <?php
        if( has_nav_menu( 'main-menu' ) ):
                    
            wp_nav_menu([
                'theme_location' => 'main-menu',
                'menu_class' => 'd-none d-lg-block',
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