<?php

add_action('hw_account_reservation_after_title', function(){

    $CVgroupID = hw_cv_get_quote_group_id(get_the_ID());
    $CVbookingID = hw_cv_get_quote_booking_id(get_the_ID());
    $CVbookingNumber = '';

    if(!empty($CVgroupID) && !empty($CVbookingID)): 
        $CVbookingNumber = $CVgroupID . '-' . $CVbookingID;
        ?>
        <div class="text-muted">
            <?= sprintf(__('Booking ID #%s', 'hola-weddings'), $CVbookingNumber) ?>
        </div>
    <?php endif;
});