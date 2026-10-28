<?php

namespace HW\CLI;

use HW\Models\Invoice;

class InvoiceTests{

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
     * --status=<status>
     * : The status to set
     *
     * @return void
     */
    public function test_status_change( $args, $assoc_args ){

        $site_id    = isset( $assoc_args['site'] ) ? intval( $assoc_args['site'] ) : null;
        $invoice_id = isset( $assoc_args['invoice'] ) ? intval( $assoc_args['invoice'] ) : null;
        $status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : null;

        $allowed_statuses = [
            Invoice::$STATUS_CANCELED,
            Invoice::$STATUS_NOTPAID,
            Invoice::$STATUS_PAID,
            Invoice::$STATUS_PARTIALLY_PAID
        ];

        if ( ! $site_id || ! $invoice_id || ! $status ) {
            \WP_CLI::error( 'All --site_id, --invoice_id and --status are required.' );
        }

        if ( ! in_array( $status, $allowed_statuses ) ) {
            \WP_CLI::error( 'Invalid status value' );
        }

        switch_to_blog($site_id);

        $invoice = hw_get_invoice($invoice_id);

        \WP_CLI::line( sprintf( 'Invoice %d Status %s', $invoice->getID(), $invoice->getStatus() ) );

        $invoice->setStatus($status);

        \WP_CLI::line( sprintf( 'New Status: %s', get_post_meta( $invoice->getID(), 'hw_invoice_status', true) ) );

        restore_current_blog();

    }
}