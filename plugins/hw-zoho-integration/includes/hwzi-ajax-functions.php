<?php

use com\zoho\api\logger\SDKLogger;
use com\zoho\crm\api\exception\SDKException;
use com\zoho\crm\api\record\APIException as RecordAPIException;
use com\zoho\crm\api\record\Record;
use HWZoho\API\Auth\Initializer;
use HWZoho\API\Auth\OAuth;
use HWZoho\API\CRM\Modules\Payments;
use com\zoho\crm\api\relatedrecords\APIException;
use com\zoho\crm\api\util\Choice;
use HWZoho\API\CRM\Helpers\PaymentMapper;
use HWZoho\API\CRM\Modules\Reservations;

/**
 * handle key submission
 */
add_action('wp_ajax_hwzi_update_token', function(){

    if(!isset($_POST['token']) || empty(trim($_POST['token']))){

        wp_send_json(['success' => false, 'message' => 'Missing token']);
    }

    try {
        
        Initializer::initialize($_POST['token']);

    }catch (\Exception $exception) {
        
        wp_send_json(['success' => false, 'message' => 'Error: ' . $exception->getMessage()]);
    }

    wp_send_json(['success' => true]);
});

/**
 * handle quote payments import
 */
add_action('wp_ajax_hwzi_import_quote_zoho_payments', function(){

    OAuth::initialize();

    if(!isset($_POST['post_id'])){

        wp_send_json(['success' => false, 'message' => 'Missing post id']);
    }

    $quote_id = $_POST['post_id'];
    $reservation_zoho_id = hwzi_get_quote_zoho_reservation_id($quote_id);

    if(empty($reservation_zoho_id)){

        wp_send_json(['success' => false, 'message' => 'Missing zoho reservation id on this quote']);
    }

    try{

        $records = Payments::getBy($reservation_zoho_id, 'Reservations');
    }catch(\Exception $e){

        wp_send_json(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
    }
    
    if($records instanceof APIException){

        wp_send_json(['success' => false, 'message' => sprintf('API Message - %s.', $records->getMessage()->getValue())]);
    }

    if(empty($records)){

        wp_send_json(['success' => false, 'message' => 'No records found.']);
    }

    $counter = 0;
    $already_exists = 0;
    $error_on_creating = 0;

    foreach($records as $record){

        $data = PaymentMapper::map($record);
        
        // merge meta data
        $meta = array_merge([
            'hw_reservation_id' => $quote_id
        ], $data['meta']);

        // query invoices with the record zoho id to find if was already imported
        $query = new \WP_Query([
            'post_type' => 'invoices',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'hw_zoho_payment_id',
                    'value' => $meta['hw_zoho_payment_id']
                ]
            ]
        ]);

        // the payment was already imported
        if($query->have_posts()){
            $already_exists++;
            continue;
        }

        // new invoice data
        $post_data = [
            'post_type' => 'invoices',
            'post_title' => $data['title'],
            'post_status' => 'publish',
            'meta_input' => $meta
        ];

        // create the post
        $post_id = wp_insert_post($post_data);
    
        // check if it was created
        if(is_wp_error($post_id)){
            $error_on_creating++;
        }

        $counter++;
    }
    
    wp_send_json(['success' => true, 'message' => sprintf('Found: %d - Created: %d - Did exist: %d - Error creating: %d', count($records), $counter, $already_exists, $error_on_creating)]);
});

/**
 * render the invoices table for the given post
 *
 * @return void
 */
add_action( 'wp_ajax_hwzi_update_quote_invoices_table', function(){

    $post_id = $_GET['post_id'];

    $query = new \WP_Query([
        'post_type' => 'invoices',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'hw_reservation_id',
                'value' => $post_id
            ]
        ]
    ]);

    ob_start();
    hw_get_template('templates/admin/metaboxes/quote/invoices-table', ['query' => $query]);
    $content = ob_get_clean();

    wp_send_json(['success' => true, 'content' => $content]);
} );

/**
 * export invoice to Zoho
 *
 * @return json
 */
add_action('wp_ajax_hwzi_export_invoice_to_zoho', function(){

    if(!isset($_POST['post_id']) || empty($_POST['post_id'])){

        wp_send_json(['success' => false, 'message' => 'Invalid request, post_id parameter is required.']);
    }

    $logger = hwzi_get_logger();

    $invoice_id = $_POST['post_id'];
    $invoice = hw_get_invoice($invoice_id);
    $quoteID = $invoice->getQuoteID();
    $quote_zoho_id = hwzi_get_quote_zoho_reservation_id($quoteID);
    
    if(empty($quote_zoho_id)){

        wp_send_json(['success' => false, 'message' => 'The quote is missing a valid Zoho Reservation ID.']);
    }

    OAuth::initialize();

    $zoho_reservation = Reservations::getRecord((string)$quote_zoho_id);

    if($zoho_reservation == false){

        wp_send_json(['success' => false, 'message' => 'Invalid Zoho Reservation ID, the reservation doesn\'t exist']);
    }

    $reservationRecord = new Record();
    $reservationRecord->setId(strval($quote_zoho_id));

    $data = [
        'Name' => $invoice->getTitle(),
        'Payment_Amount' => floatval($invoice->getAmount()),
        'Payment_Date' => $invoice->getDueDate(),
        'Payment_Number' => new Choice($invoice->getType()['label']),
        'Status' => new Choice($invoice->getStatusLabel()),
        'Reservation_ID' => strval($quote_zoho_id),
        'Reservation' => $reservationRecord
    ];

    try{

        $zoho_payment_id = Payments::createRecord($data);
    }catch(SDKException $e){

        SDKLogger::err($e);
        $logger->error($e->getErrorMessage(), 'wp_ajax_hwzi_export_invoice_to_zoho');
        wp_send_json(['success' => false, 'message' => 'An error ocurred while creating the record in Zoho: ' . $e->getErrorMessage()]);
    }

    // an API Exception ocurred
    if($zoho_payment_id == false){

        wp_send_json(['success' => false, 'message' => 'An error ocurred while creating the record in Zoho. Check the log.']);
    }

    $invoice->set('zoho_payment_id', $zoho_payment_id);

    ob_start();
    hwzi_get_template('admin/metaboxes/invoices/payment', ['payment_id' => $zoho_payment_id]);
    $html = ob_get_clean();

    wp_send_json(['success' => true, 'message' => 'Quote created with ID: ' . $zoho_payment_id, 'html' => $html]);
});