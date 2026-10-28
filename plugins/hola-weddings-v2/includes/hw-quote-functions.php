<?php

use HW\API\Operations\Insurance;
use HW\Models\Invoice;
use HW\Models\Quote;
use HW\Quotes;

/**
 * create a new Invoice instance
 *
 * @param int $id
 * @return HW\Models\Quote
 */
function hw_get_quote($id){
    return new Quote($id); 
}

/**
 * return status label
 *
 * @param string $value
 * @return string
 */
function hw_quote_status_label($value){

    $statuses = hw_get_quote_statuses();
    return isset($statuses[$value]) ? $statuses[$value] : '';
}

/**
 * Return quote meta
 *
 * @param int $key
 * @param int $id
 * @return mixed
 */
function hw_get_quote_meta($key, $id = null){

    if(empty($id)){
        $id = get_the_ID();
    }

    return get_post_meta($id, $key, true);
}

/**
 * return the quote billing snapshot
 * DEPRECATED: use $quote->getBillingInformation() instead
 *
 * @deprecated 2.5.0
 * @param int $id
 * @return string|bool
 */
function hw_get_quote_customer($id = null){

    return hw_get_quote_meta('hw_customer_data', $id);
}

/**
 * return quote insurance
 *
 * @return void
 */
function hw_get_quote_insurance($id = null){

    return Insurance::getPlan(hw_get_quote_insurance_plan_id($id));
}

/**
 * get insurance plan id
 *
 * @return int
 */
function hw_get_quote_insurance_plan_id($id = null){
    
    return hw_get_quote_meta('hw_insurance_plan_id', $id);
}

/**
 * return insurance total
 *
 * @param int $id
 * @return int
 */
function hw_get_quote_insurance_total($id = null){

    return hw_get_quote_meta('hw_insurance_total', $id);
}

/**
 * return quote deposit
 *
 * @param int $id
 * @return array
 */
function hw_get_quote_deposit($id = null){

    return hw_get_quote_meta('hw_deposit', $id);
}

/**
 * return guests
 *
 * @param int $id
 * @return array
 */
function hw_get_quote_guests($id = null){

    return hw_get_quote_meta('hw_guests', $id);
}

/**
 * return quote checkin
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_check_in($id = null){

    return hw_get_quote_meta('hw_checkin', $id);
}

/**
 * return quote checkout
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_check_out($id = null){

    return hw_get_quote_meta('hw_checkout', $id);
}

/**
 * return quote adults
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_adults($id = null){

    return hw_get_quote_meta('hw_adults', $id);
}

/**
 * return quote teens
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_teens($id = null){

    return hw_get_quote_meta('hw_teen', $id);
}

/**
 * return quote children
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_children($id = null){

    return hw_get_quote_meta('hw_children', $id);
}

/**
 * return quote infants
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_infants($id = null){

    return hw_get_quote_meta('hw_infants', $id);
}

/**
 * return quote infants
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_room_id($id = null){

    return intval( hw_get_quote_meta('hw_room_id', $id) );
}

/**
 * return quote infants
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_room_total($id = null){

    return hw_get_quote_meta('hw_room_total', $id);
}

/**
 * return quote infants
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_total($id = null){

    return hw_get_quote_meta('hw_total', $id);
}

/**
 * return quote transfer fee
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_transfer_fee($id = null){

    return hw_get_quote_meta('hw_service_fee', $id);
}

/**
 * return quote status
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_status($id = null){

    return hw_get_quote_meta('hw_quote_status', $id);
}

/**
 * return card information
 *
 * @param int $id
 * @return bool|array
 */
function hw_get_card_information($id = null){

    return Quotes::get_card_information($id);
}

/**
 * return ignature
 *
 * @param int $id
 * @return string
 */
function hw_get_quote_signature($id = null){

    return hw_get_quote_meta('hw_signature', $id);
}

/**
 * return a list of quotes
 *
 * @return array
 */
function hw_get_quote_list(){

    $query = new \WP_Query([
        'post_type' => 'quote',
        'posts_per_page' => -1
    ]);


    $quotes = [];

    if($query->have_posts()){
        foreach($query->posts as $post){
            $quotes[$post->ID] = new Quote($post);
        }
    }

    return $quotes;
}

/**
 * return an upcoming invoice
 *
 * @param int $quote_id
 * @return Invoice
 */
function hw_get_quote_upcoming_invoice($quote_id){

    $query = new \WP_Query([
        'post_type' => 'invoices',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'hw_reservation_id',
                'value' => $quote_id,
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'hw_invoice_status',
                'value' => 'not-paid',
            ]
        ],
        'meta_key' => 'hw_due_date',
        'meta_type' => 'DATE',
        'orderby' => 'meta_value'
    ]);

    if($query->have_posts()){

        return new Invoice($query->posts[0]);
    }

    return false;
}
