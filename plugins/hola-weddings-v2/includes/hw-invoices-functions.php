<?php

use HW\Models\Invoice;

/**
 * create a new Invoice instance
 *
 * @param int $id
 * @return HW\Models\Invoice
 */
function hw_get_invoice($id){
    return new Invoice($id); 
}

/**
 * return the alert type for an invoice status
 *
 * @param string $slug
 * @return string
 */
function hw_get_invoice_alert_class($slug){

    $labels = [
        'not-paid' => 'warning',
        'paid' => 'success',
        'canceled' => 'secondary',
        'partially-paid' => 'warning'
    ];

    return isset($labels[$slug]) ? $labels[$slug] : '';
}

/**
 * return the label for an invoice status
 *
 * @param string $slug
 * @return string
 */
function hw_get_invoice_status_label($slug){

    $labels = [
        'not-paid' => __('Not Paid', 'hola-weddings'),
        'paid' => __('Paid', 'hola-weddings'),
        'canceled' => __('Canceled', 'hola-weddings'),
        'partially-paid' => __('Partially Paid', 'hola-weddings')
    ];

    return isset($labels[$slug]) ? $labels[$slug] : '';
}

/**
 * return the label for an invoice status
 *
 * @param string $slug
 * @return string
 */
function hw_get_invoice_type_label($slug){

    $labels = [
        'none' => __('- None -', 'hola-weddings'),
        'first' => __('First', 'hola-weddings'),
        'second' => __('Second', 'hola-weddings'),
        'third' => __('Third', 'hola-weddings'),
        'final' => __('Final', 'hola-weddings'),
        'extra' => __('Extra', 'hola-weddings'),
        'transfers' => __('Transfers', 'hola-weddings'),
        'room_block_deposit' => __('Room Block Deposit', 'hola-weddings'),
        'insurance' => __('Insurance', 'hola-weddings'),
        'refund' => __('Refund', 'hola-weddings'),
    ];

    return isset($labels[$slug]) ? $labels[$slug] : '';
}

/**
 * return a list of invoice columns
 *
 * @return array
 */
function hw_get_invoices_columns(){
        
    $columns = array(
        'title' => __('Title', 'hola-weddings'),
        'amount' => __('Amount', 'hola-weddings'),
        'due_date' => __('Due Date', 'hola-weddings'),
        'status' => __('Status', 'hola-weddings'),
        'type' => __('Type', 'hola-weddings'),
        'options' => __('Options', 'hola-weddings'),
        'id' => __('ID', 'hola-weddings'),
    );

    return $columns;
}

/**
 * display columns content
 *
 * @param object $item
 * @param string $column_name
 * @return void
 */
function hw_display_invoices_columns( $item, $column_name ) {
    switch($column_name){
        case 'id':
            echo $item->ID;
            break;
        case 'title':
            echo sprintf('<a href="%s" target="_blank">%s</a>', get_edit_post_link($item), $item->post_title);
            break;
        case 'amount':
            $amount = get_field('hw_amount', $item->ID);
            echo $amount ? hw_format_price($amount) : '';
            break;
        case 'due_date':
            $date = get_field('hw_due_date', $item->ID);
            echo $date ? (new DateTime($date))->format('d F Y') : '';
            break;
        case 'status':
            $status = get_field('hw_invoice_status', $item->ID);
            echo is_array($status) ? $status['label'] : hw_get_invoice_status_label($status);
            break;
        case 'type':
            $type = get_field('hw_type', $item->ID);
            echo is_array($type) ? $type['label'] : hw_get_invoice_type_label($type);
            break;
        case 'options':
            echo sprintf('<a href="%s" target="_blank">View</a>', get_edit_post_link($item->ID));
            break;
    }
}

/**
 * return invoices associated with the quote
 *
 * @param int $quote_id
 * @return array
 */
function hw_get_quote_invoices($quote_id){

    $query = new \WP_Query([
        'post_type' => 'invoices',
        'posts_per_page' => -1,
        //'order' => 'ASC',
        'orderby' => [ 'meta_value' => 'ASC', 'ID' => 'ASC' ],
        'meta_key' => 'hw_due_date',
        'meta_type' => 'DATE',
        'post_status' => [ 'publish' ],
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'hw_reservation_id',
                'value' => $quote_id
            ]
        ]
    ]);

    $invoices = [];

    if($query->have_posts()){
        foreach($query->posts as $post){
            $invoices[$post->ID] = new Invoice($post);
        }
    }

    return $invoices;
}

/**
 * return a list of invoice columns
 *
 * @return array
 */
function hw_get_frontend_invoices_columns(){
        
    $columns = array(
        // 'status' => __('', 'hola-weddings'),
        'name' => __('Name', 'hola-weddings'),
        'amount' => __('Amount', 'hola-weddings'),
        //'type' => __('Type', 'hola-weddings'),
        'actions' => __('Actions', 'hola-weddings'),
        //'post_status' => 'Status'
    );

    /**
     * Let 3rd party code add their own columns
     * 
     * @param array $columns
     * @return array
     */
    $columns = apply_filters('hw_frontend_invoice_columns', $columns);

    return $columns;
}

/**
 * display columns content
 *
 * @param object $item
 * @param string $column_name
 * @return void
 */
function hw_display_frontend_invoices_columns( $item, $column_name ) {
    $invoice = hw_get_invoice($item->ID);
    switch($column_name){
        case 'name':
            $status = $invoice->getStatus();
            $date = get_field('hw_due_date', $item->ID);
            ?>
                <?php if($status): ?>
                    <span class="d-inline-block mb-1 alert alert-<?= hw_get_invoice_alert_class($status) ?> rounded-pill px-2 py-0"><?= hw_get_invoice_status_label($status) ?></span>
                <?php endif ?>
                <strong><?= $item->post_title; ?></strong>
                <div class="d-flex align-items-center" style="font-size: 12px;">
                    <i class="far fa-clock mr-1"></i>
                    <?= $date ? sprintf(__('Due %s', 'hola-weddings'), (new DateTime($date))->format('d F Y') ) : ''; ?>
                </div>
            <?php
            break;
        case 'amount':
            $amount = get_field('hw_amount', $item->ID);
            echo $amount ? hw_format_price($amount) : '';
            break;
        case 'status':
            $status = $invoice->getStatus();
            echo $status ? sprintf('<span class="d-inline-block mb-0 alert alert-%s rounded-pill p-1 py-md-2">%s</span>',hw_get_invoice_alert_class($status), hw_get_invoice_status_label($status)) : '';
            break;
        case 'type':
            $type = get_field('hw_type', $item->ID);
            echo $type ? hw_get_invoice_type_label($type) : '';
            break;
        case 'actions':
            //echo sprintf('<a href="%s" target="_blank">View</a>', get_edit_post_link($item->ID));
            ?>
            <div class="actions">
                <?php do_action('hw_frontend_quote_invoices_actions_column', $item); ?>
            </div>
            <?php
            break;
        case 'post_status':
            echo $item->post_status;
            break;
        default:
            /**
             * Let 3rd party code display their own columns content
             * 
             * @param string $column_name
             * @param WP_Post $post
             */
            do_action('hw_display_frontend_invoice_column', $column_name, $item);
            break;
    }
}

/**
 * return the invoice payment permalink
 *
 * @return string
 */
function hw_get_invoice_payment_permalink( $invoice ){

    if($invoice instanceof Invoice){
        $invoice = $invoice->getID();
    }

    return add_query_arg(['invoiceID' => $invoice], home_url('/invoice-payment'));
}