<!-- Footer Start -->
<div id="account-footer" class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <span>© Hola Weddings</span>
            </div>
            <div class="col-md-6">
                <?php
                if( has_nav_menu( 'account-footer-menu' ) ):
                            
                    wp_nav_menu([
                        'theme_location' => 'account-footer-menu',
                        'menu_class' => 'flex-column flex-md-row text-center text-md-left',
                        'container_class' => ''
                    ]);
                    
                endif;
                ?>
            </div>
        </div>
    </div>
</div>
<!-- end Footer -->