<?php

use HW\Integrations\ClassicVacations\App;
use HW\Integrations\ClassicVacations\CRM\Utils\APIException;
use HW\Models\CRM\BillingInformation;
use HW\Models\Invoice;
use HW\Utils\Card;

/**
 * return client id
 *
 * @return string
 */
function hw_cv_get_client_id(){

    // wp-config constant wins (secrets out of the DB); network option = fallback
    if (defined('HW_CV_CLIENT_ID') && HW_CV_CLIENT_ID !== '') return HW_CV_CLIENT_ID;
    return get_network_option(null, 'hw_classic_vacation_client_id', '');
}

/**
 * return client secret
 *
 * @return string
 */
function hw_cv_get_client_secret(){

    if (defined('HW_CV_CLIENT_SECRET') && HW_CV_CLIENT_SECRET !== '') return HW_CV_CLIENT_SECRET;
    return get_network_option(null, 'hw_classic_vacation_client_secret', '');
}

/**
 * return redirect uri
 *
 * @return string
 */
function hw_cv_get_redirect_uri(){

    return get_network_option(null, 'hw_classic_vacation_client_redirect_uri', '');
}

function hw_get_curl_timeout(){
    return 0;
}

function hw_get_curl_connection_timeout(){
    return 0;
}

function hw_cv_is_api_exception($obj){
    return $obj instanceof APIException;
}

/**
 * return the logger;
 *
 * @return HW\Utils\Logger;
 */
function hw_cv_get_logger(){

    return App::getInstance()->getLogger();
}

/**
 * display the billing template
 *
 * @param BillingInformation $data
 * @return string
 */
function hw_cv_billing_template(BillingInformation $data = null){

    App::getInstance()->get_template_helper()->get_template('forms/billing', ['billing_information' => $data ]);
}

/**
 * return quote classic vacation group id
 *
 * @param int $post_id
 * @return string
 */
function hw_cv_get_quote_group_id($post_id){
    return get_post_meta($post_id, 'hw_cv_group_id', true);
}

/**
 * return quote classic vacation group id
 *
 * @param int $post_id
 * @return string
 */
function hw_cv_get_quote_booking_id($post_id){
    return get_post_meta($post_id, 'hw_cv_booking_id', true);
}

/**
 * generate an unique ref id
 *
 * @return string
 */
function hw_cv_generate_ref_id( $identifier = '' ){

    $id = time();

    if(!empty($identifier)){
        $id = $identifier . '-' . $id;
    }

    return sprintf('HC-%s', $id);
}

/**
 * convert card type for classic vacations
 *
 * @param Card $card
 * @return Card
 */
function hw_cv_convert_card_type(Card $card){

    switch($card->getType()){
        case 'visa':
            $card->setType('001');
            break;
        case 'mc':
            $card->setType('002');
            break;
        case 'amex':
            $card->setType('003');
            break;
    }

    return $card;
}

/**
 * return CV debug level
 *
 * @return string
 */
function hw_get_cv_debug_level(){

    return hw_get_option( 'hw_cv_debug_level', 'info' );
}