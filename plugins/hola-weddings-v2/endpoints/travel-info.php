<?php

$hotel = (object) hw_get_hotel();

get_header();
?>
<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="inner">

                <?php hw_get_template('template-parts/travel-info/hero', ['hotel' => $hotel]); ?>
                
                <?php hw_get_template('template-parts/travel-info/quick-links'); ?>
                
                <?php hw_get_template('template-parts/travel-info/hotel-overview', ['hotel' => $hotel]); ?>

                <?php hw_get_template('template-parts/travel-info/hotel-rooms', ['hotel' => $hotel]); ?>

                <?php hw_get_template('template-parts/travel-info/hotel-contact', ['hotel' => $hotel]); ?>

                <?php hw_get_template('template-parts/travel-info/things-to-know', ['hotel' => $hotel]); ?>
            </div>
		</main><!-- #main -->
	</div><!-- #primary -->
<?php
get_footer();