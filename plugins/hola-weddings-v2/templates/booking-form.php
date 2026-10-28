<?php

/**
 * This template renders React booking form
 */

$has_container = hw_booking_form_page_has_container();

get_header();
?>
    <div id="primary" class="content-area">
		<main id="main" class="site-main">
			<div class="inner pt-2 container<?= !hw_booking_form_page_has_container() ? '-fluid' : '' ?>">
                <?php // data-no-translation: the form translates itself (wp_set_script_translations); TranslatePress rewriting React-managed DOM breaks it. ?>
                <div id="hw-booking-form-root" data-no-translation></div>
            </div>
        </main>
    </div>
<?php

get_footer();