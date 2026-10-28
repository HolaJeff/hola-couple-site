<?php

add_action('hw_quote_invoices_metabox_actions', function($post_id){

    $reservation_id = hwzi_get_quote_zoho_reservation_id($post_id);

    // do not show the sync button if there's no reservation id
    if(empty($reservation_id)){
        return;
    }

    ?>
    <button id="quote-invoices-zoho-import" class="btn btn-zoho" type="button" target="_blank" data-post-id="<?= $post_id ?>">
        <span class="dashicons dashicons-database-import"></span>
        <span class="text"><?= __('Import from Zoho', 'hw-zoho') ?></span>
    </button>
    <?php
});

/**
 * build the payment zoho url
 *
 * @param string $id
 * @return string
 */
function hwzi_get_zoho_payment_url(string $id){
    
    return sprintf('https://crm.zoho.com/crm/org769133278/tab/CustomModule7/%s', $id);
}