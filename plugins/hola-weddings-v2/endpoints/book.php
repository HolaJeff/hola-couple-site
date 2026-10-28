<?php

/**
 * This template renders Legacy booking form
 */
get_header();
?>
    <div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="inner">
                <div class="container">
                    <?php require_once( HW_TEMPLATE_PARTS . '/booking-form/form.php' ); ?>
                </div>
            </div>
        </main>
    </div>
<?php

get_footer();