<?php

namespace HWZoho\CLI;

use HW\Models\Invoice;
use HWZoho\AsyncRequests\UpdateRecord;

class TestAsyncRequest{

    /**
     * test migrating single site by id
     * 
     * ## OPTIONS
     *
     * --site=<site>
     * : The ID of the site to process.
     *
     * --invoice=<invoice>
     * : The ID of the invoice to process.
     * 
     * --fields=<fields>
     * : The fields that are going to be updated
     *
     * @return void
     */
    public function test_invoice( $args, $assoc_args ){

        $site_id    = isset( $assoc_args['site'] ) ? intval( $assoc_args['site'] ) : null;
        $invoice_id = isset( $assoc_args['invoice'] ) ? intval( $assoc_args['invoice'] ) : null;
        $fields = isset( $assoc_args['fields'] ) ? explode( ',',  $assoc_args['fields'] ) : [];

        if ( ! $site_id || ! $invoice_id || empty($fields) ) {
            \WP_CLI::error( 'All --site, --invoice and --fields are required.' );
        }

        switch_to_blog($site_id);

        $invoice = hw_get_invoice($invoice_id);

        \WP_CLI::line( sprintf( 'Invoice %d Fields %s', $invoice->getID(), json_encode($fields) ) );

        $payment_id = hwzi_get_invoice_zoho_payment_id($invoice->getID());

        // if is an update and zoho payment id is missing stop
        if(empty($payment_id)){

            \WP_CLI::error( 'Payment id not found for invoice ' . $invoice->getID() );
            return;
        }

        $request = new UpdateRecord();

        $request->data( [ 'post_id' => $invoice->getID(), 'fields' => $fields ] )->dispatch();

        \WP_CLI::line( 'Request dispatched' );
        restore_current_blog();

    }
}