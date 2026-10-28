<?php
global $hw_theme;
?>
<header id="header" class="header-footer-group w-100" role="banner">
				
        <div class="header-inner">
            
            <div class="clearfix">
                
                <div class="container<?= !hw_evaluate_string_to_boolean($hw_theme['header_display_container'] ?? true) ? '-fluid' : '' ?>" >

                    <div class="main-nav position-relative d-flex flex-nowrap align-items-center" role="navigation">

                        <?php do_action('hw_header_content') ?>
                                                
                    </div><!-- .main-nav-->
                    
                </div><!-- Container -->
                
            </div><!-- .top-header -->

        </div><!-- header-inner -->

</header><!-- #site-header -->