<?php

add_action('hw_after_booking_form', function(){

    hw_get_template('template-parts/modals/terms-conditions');
});

/**
 * display mobile summary call to action
 *
 * @return void
 */
function hw_display_mobile_cart_summary(){

    hw_get_template('template-parts/booking-form/mobile-summary');
}
add_action('hw_after_booking_form', 'hw_display_mobile_cart_summary');