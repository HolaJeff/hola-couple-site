<?php

// redux options
/* $disable_hero = (bool) redux_post_meta( 'hw_plugin', get_the_ID(), 'hw-homepage-hero-disable' );
$sections = redux_post_meta( 'hw_plugin', get_the_ID(), 'hw-homepage-sections' ); */

get_header();
?>
<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="inner">
                
                <?php hw_get_template('template-parts/home/hero'); ?>

                <?php if(have_rows('hw_homepage_sections')): ?>
                    <?php while( have_rows('hw_homepage_sections') ): the_row(); ?>
                        <?php if(get_sub_field('disable')):
                            continue;
                        endif; ?>
                        <?php hw_get_template('template-parts/home/' . get_row_layout()); ?>
                    <?php endwhile; ?>
                <?php endif; ?>

                <?php /* if(!empty($sections['enabled'])): ?>
                    <?php foreach($sections['enabled'] as $layout => $label): ?>
                        <?php hw_get_template('template-parts/home/' . $layout); ?>
                    <?php endforeach; ?>
                <?php endif;  */?>
                
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->
<?php
get_footer();
