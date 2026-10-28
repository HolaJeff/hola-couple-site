<?php

/**
 * display the admin meta for the current reservation
 *
 * @return void
 */
function hw_cv_account_reservations_display_admin_meta(){

    $CVgroupID = hw_cv_get_quote_group_id(get_the_ID());
    $CVbookingID = hw_cv_get_quote_booking_id(get_the_ID());
    $CVbookingNumber = '';

    if(!empty($CVgroupID) && !empty($CVbookingID)): 
        ?>
        <span class="reservation__meta-item">
            <?= sprintf(__('CV Group ID #%s', 'hola-weddings'), $CVgroupID) ?>
        </span>
        <span class="reservation__meta-item">
            <?= sprintf(__('CV Booking ID #%s', 'hola-weddings'), $CVbookingID) ?>
        </span>
    <?php endif;
}
add_action('hw_account_reservations_before_admin_meta', 'hw_cv_account_reservations_display_admin_meta');