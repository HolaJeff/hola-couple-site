<?php

use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\fields\FieldsOperations;
use com\zoho\crm\api\fields\GetFieldParam;
use com\zoho\crm\api\fields\BodyWrapper;
use HW\Models\RoomBlock;
use HWZoho\App;

/**
 * returun the plugin logger
 *
 * @return HW\Utils\Logger
 */
function hwzi_get_logger(){

    return App::getInstance()->getLogger();
}

function zoho_log($message, $type = 'INFO'){

    $content = '';

    if(file_exists( HW_ZOHO_PLUGIN_PATH . '/logs/log.php')){
        $content = file_get_contents(HW_ZOHO_PLUGIN_PATH . '/logs/log.php');
    }

    $content .= sprintf('%s - [%s] - %s \n', date('Y-m-d H:m:i'), $type, $message);

    file_put_contents(HW_ZOHO_PLUGIN_PATH . '/logs/log.php', $content);
}

/**
 * return the quote zoho reservation id
 *
 * @param int $post_id
 * @return string
 */
function hwzi_get_quote_zoho_reservation_id($post_id){
    return get_post_meta($post_id, 'zoho_reservation_id', true);
}


/**
 * return the quote zoho contact id
 *
 * @param int $post_id
 * @return string
 */
function hwzi_get_quote_zoho_contact_id($post_id){
    return get_post_meta($post_id, 'zoho_contact_id', true);
}

/**
 * return the invoice zoho payment id
 *
 * @param int $post_id
 * @return string
 */
function hwzi_get_invoice_zoho_payment_id($post_id){
    return get_post_meta($post_id, 'zoho_payment_id', true);
}

/**
 * return an invoice by zoho payment ID
 *
 * @param string $recordID
 * @return \WP_Post|false
 */
function hwzi_get_post_by_zoho_record_id(string $recordID, string $field_name, string $post_type){

    $query = new \WP_Query([
        'post_type' => $post_type,
        'post_status' => [ 'publish', 'draft' ],
        'meta_query' => [
            [
                'key' => $field_name,
                'value' => trim($recordID),
            ]
        ],
        'posts_per_page' => 1
    ]);

    if(!$query->have_posts()){
        return false;
    }

    return $query->posts[0];
}

/**
 * return a quote by zoho id
 *
 * @return \WP_Post|false
 */
function hwzi_get_quote_by_zoho_id($id){

    return hwzi_get_post_by_zoho_record_id($id, 'zoho_reservation_id', 'quote');
}

/**
 * return a payment by zoho id
 *
 * @return \WP_Post|false
 */
function hwzi_get_invoice_by_zoho_id($id){

    return hwzi_get_post_by_zoho_record_id($id, 'zoho_payment_id', 'invoices');
}

/**
 * return a room block by zoho id
 *
 * @return RoomBlock|false
 */
function hwzi_get_room_block_by_zoho_id($id){

    $post = hwzi_get_post_by_zoho_record_id($id, 'hwzi_room_block_id', 'room_blocks');

    if($post instanceof WP_Post){
        
        return new RoomBlock($post);
    }

    return false;
}

function hwzi_get_tour_operator_field_options(){

    $fieldOperations = new FieldsOperations();
    $paramInstance = new ParameterMap();
    $paramInstance->add(GetFieldParam::module(), 'Reservations');
    $options = [];

    //Call getField method which takes fieldId as parameter
    $response = $fieldOperations->getField('5170711000094294002', $paramInstance);
    
    if ($response != null) {

        if (in_array($response->getStatusCode(), array(204, 304))) {
            echo ($response->getStatusCode() == 204 ? "No Content\n" : "Not Modified\n");
            return;
        }

        $responseHandler = $response->getObject();
        if ($responseHandler instanceof BodyWrapper) {

            $responseWrapper = $responseHandler;
            $fields = $responseWrapper->getFields();
            $field = $fields[0];
            
            if($field->getDataType() == 'picklist'){
                $pickListValues = $field->getPickListValues();
                if ($pickListValues != null) {
                    foreach ($pickListValues as $pickListValue1) {
                        $options[$pickListValue1->getId()] = $pickListValue1-> getDisplayValue();
                    }
                }
            }
            
        }
    }

    return $options;
}