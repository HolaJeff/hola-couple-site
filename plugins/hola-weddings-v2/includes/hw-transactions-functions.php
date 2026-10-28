<?php

use HW\Models\Transaction;

/**
 * create a new Transaction instance
 *
 * @param int $id
 * @return HW\Models\Transaction
 */
function hw_get_transaction($id){
    return new Transaction($id); 
}

/**
 * return a list of transactions instances
 *
 * @return array
 */
function hw_get_transactions( $args = [] ){

    $instances = [];

    $default_args = [
        'post_type' => 'transactions',
        'posts_per_page' => -1,
        'post_status' => [ 'publish', 'draft' ],
    ];

    $args = wp_parse_args($args, $default_args);

    $query = new \WP_Query($args);

    if($query->have_posts()){
        foreach($query->posts as $post){
            $instances[$post->ID] = new Transaction($post);
        }
    }

    return $instances;
}

/**
 * return the label for an invoice status
 *
 * @param string $slug
 * @return string
 */
function hw_get_transaction_status_label($slug){

    $labels = [
        'processing' => __('Processing', 'hola-weddings'),
        'confirmed' => __('Confirmed', 'hola-weddings'),
		'pending' => __('Pending', 'hola-weddings'),
		'canceled' => __('Canceled', 'hola-weddings'),
        'failed' => __('Failed', 'hola-weddings'),
    ];

    return isset($labels[$slug]) ? $labels[$slug] : '';
}

/**
 * return a list of invoice columns
 *
 * @return array
 */
function hw_get_transaction_columns(){
        
    $columns = array(
        'amount' => __('Amount', 'hola-weddings'),
        'status' => __('Status', 'hola-weddings'),
        'gateway' => __('Gateway', 'hola-weddings'),
        'transaction_id' => __('Transaction ID', 'hola-weddings'),
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
function hw_display_transaction_columns( $item, $column_name ) {
    switch($column_name){
        case 'id':
            echo $item->ID;
            break;
        case 'amount':
            $amount = get_field('hw_amount', $item->ID);
            echo $amount ? hw_format_price($amount) : '';
            break;
        case 'status':
            $date = get_field('hw_status', $item->ID);
            echo $date ? hw_get_transaction_status_label($date) : '';
            break;
        case 'gateway':
            $gateway = get_field('hw_gateway', $item->ID);
            echo $gateway ?: '';
            break;
        case 'transaction_id':
            $t_id = get_field('hw_transaction_id', $item->ID);
            echo $t_id ?: '';
            break;
        case 'options':
            echo sprintf('<a href="%s" target="_blank">View</a>', get_edit_post_link($item->ID));
            break;
    }
}

/**
 * return transaction status
 *
 * @param int $id
 * @return mixed
 */
function hw_get_transation_status($id){

    if(empty($id)){
        $id = get_the_ID();
    }

    return get_post_meta($id, 'hw_status', true);
}

/**
 * search if a transaction with the same reference id exists
 *
 * @param string $ref_id
 * @return bool
 */
function hw_transaction_ref_id_exists($ref_id){

    $args = [
        'post_type' => 'transactions',
        'posts_per_page' => 1,
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => 'hw_transaction_id',
                'value' => $ref_id,
            ]
        ]
    ];

    $query = new WP_Query($args);

    return $query->have_posts();
}

/**
 * search if a transaction with the same reference id exists
 *
 * @param string $ref_id
 * @return array
 */
function hw_get_transactions_by_invoice($invoice_id, $args = []){

    $default_args = [
        'post_type' => 'transactions',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'hw_invoice_id',
                'value' => $invoice_id,
            ]
        ]
    ];

    $args = wp_parse_args($args, $default_args);

    $result = [];
    $query = new WP_Query($args);

    if($query->have_posts()){
        foreach($query->posts as $post){
            $result[$post->ID] = new Transaction($post);
        }
    }

    return $result;
}