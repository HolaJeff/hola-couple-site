<?php

use HWZoho\AsyncRequests\UpdateRecord;

/**
 * change the API root
 */
add_filter( 'rest_url_prefix', function(){
	return 'api';
});

/**
 * on payment updates, push che changes to zoho 
 */
add_action('save_post', function($post_id, $post, $update){

    $logger = hwzi_get_logger();

    if($post->post_type != 'invoices' || !$update || !isset($_POST['hwzi_update_on_zoho']) || $_POST['hwzi_update_on_zoho'] != 1){
        return;
    }

    $payment_id = hwzi_get_invoice_zoho_payment_id($post_id);

    $logger->info(sprintf('Initialize invoice %d Zoho sync', $post_id));

    // if is an update and zoho payment id is missing stop
    if(empty($payment_id)){
        
        $logger->error( 'Missing Zoho Payment ID' );
        return;
    }

    $request = new UpdateRecord();

    $fields = [
        'hw_invoice_status',
        'hw_amount',
        'hw_due_date',
        'hw_type',
        'hw_paid_date'
    ];

    $request->data( [ 'post_id' => $post_id, 'fields' => $fields ] )->dispatch();

    $logger->info( "Request dispatched - Invoice ID: " . $post_id );

}, 999, 3);

/**
 * update zoho on metadata update
 */
add_action('hw_model_invoices_metadata_updated', function( $name, $new_value, $old_value, $model ){

    $logger = hwzi_get_logger();

    if( in_array($name, ['hw_amount', 'hw_due_date', 'hw_invoice_status', 'hw_type', 'hw_paid_date'])){

        $logger->info( sprintf('Async Request: Invoice %d field %s update received', $model->getID(), $name ));

        $payment_id = hwzi_get_invoice_zoho_payment_id($model->getID());

        // if is an update and zoho payment id is missing stop
        if(empty($payment_id)){

            $logger->info('Payment id not found for invoice ' . $model->getID(), 'hw_model_metadata_updated');
            return;
        }

        $request = new UpdateRecord();

        $request->data( [ 'post_id' => $model->getID(), 'fields' => [ $name ] ] )->dispatch();

        $logger->info("Request dispatched for field: {$name} - Invoice ID: " . $model->getID(), 'hw_model_metadata_updated');
    }
}, 10, 4);
