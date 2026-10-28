<?php

namespace HW\Controllers;

use Exception;
use HW\Enums\TransactionStatus;
use HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\Noty;
use HW\Models\Transaction;
use HW\Utils\BillingInformationBuilder;
use HW\Utils\CardBuilder;
use HW\Integrations\ClassicVacations\Payment\ClassicVacationGateway;
use HW\Payment\Gateway\PaymentGatewaySimulator;
use HW\Payment\PoS as PaymentPoS;
use HW\Payment\Utilities\Charge;
use HW\Session;
use HW\Traits\Singleton;

class POS extends BaseController{

    use Singleton;

    protected $error;

    /**
     * register hooks
     *
     * @return void
     */
    public function add_hooks(){

        parent::add_hooks();

        // AJAX hooks
        add_action( 'wp_ajax_hw_pos_process_payment', [$this, 'ajax_process_payment'] );
        add_action( 'wp_ajax_nopriv_hw_pos_process_payment', [$this, 'ajax_process_payment_no_priv'] );

        // Confirmation landing page
        add_action('wp_ajax_hw_confirmation_transaction_ticker', [ $this, 'transaction_pending_ticket' ]);
    }

    /**
     * wp init function
     *
     * @return void
     */
    public function init(){

        if(isset($_POST['action']) && $_POST['action'] == 'hw_pos_invoice_verify_email'){
            
            if((!isset($_GET['invoiceID']) || empty($_GET['invoiceID']))
             || (!isset($_POST['email']) || empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))){

                wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request'), 'Invalid Request');
            }
        
            $session = Session::getInstance();
            $email = $_POST['email'];
            $invoiceID = $_GET['invoiceID'];
            $invoice = hw_get_invoice($invoiceID);
            $quote = $invoice->getQuote();
            $billing_information = $quote->getBillingInformation();


// phase4-email-gate: throttle guessing (per invoice + IP), constant-time compare, case-insensitive

$gate_key = 'hw_pos_gate_' . md5($invoice->getID() . '|' . ($_SERVER['REMOTE_ADDR'] ?? ''));

$gate_n = (int) get_transient($gate_key);

if($gate_n >= 5){ status_header(429); wp_die('Too many attempts - please wait 15 minutes and try again, or use the link in your email.', 'Slow down', array('response' => 429)); }

if(hash_equals(strtolower(trim((string) $billing_information->getEmail())), strtolower(trim((string) $email)))){

    delete_transient($gate_key);

                $session->set('hw_pos_can_view_invoice_' . $invoice->getID(), true);

                wp_redirect( hw_get_invoice_payment_permalink($invoice) );
            } else {
                set_transient($gate_key, $gate_n + 1, 15 * MINUTE_IN_SECONDS);
                sleep(1);
            }
        }
    }

    /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(){

        global $invoice;

        if(!hw_is_pos()){
            return;
        }

        Noty::import();
        BlockUI::importFrontEnd();

        wp_enqueue_script('hw-pos', HW_ASSET_DIST_URL . 'js/standalone/POS.js', ['jquery', 'jquery-validate', 'noty', 'blockui']);
        wp_localize_script('hw-pos', 'billing_information', $invoice->getBillingInformation() );
        wp_localize_script('hw-pos', 'hw_pos', [
            'reservation_url' => hw_get_reservation_permalink($invoice->getQuote()->getID()),
            'messages' => $this->get_i18n_messages()
        ] );
    }

    /**
     * handle confirmation landing page ticker to check if a transaction has changed state from pending to another state
     *
     * @return json
     */
    public function transaction_pending_ticket() {
        
        if(!isset($_POST['transactionID']) || empty($_POST['transactionID'])){

            wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request.'), 'Invalid Request', 400);
        }
    
        $transactionID = $_POST['transactionID'];
        $model = hw_get_transaction($transactionID);
    
        // if post is not defined and not of type quote
        if(!$model->exists()){
    
            wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Transaction.'), 'Invalid Request', 400);
        }

        $invoice = $model->getInvoice();

        // check if the user can access the invoice
        if(!hw_pos_can_user_access_invoice( $invoice )){

            wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request. User not Authorized.'), 'Invalid Request', 403);
        }
        
        wp_send_json( [ 'reload' => $model->getStatus() != TransactionStatus::PENDING->value ] );
    }

    /**
     * perform middleware checks on template_redirect hook
     *
     * @return void
     */
    public function template_redirect(){

        if(hw_is_pos() ){

            if( hw_get_invoice_payment_btn_action() != 'payment-form' || !isset($_GET['invoiceID']) || empty($_GET['invoiceID'])){

                wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request'), 'Invalid Request');
            }
        
            $invoiceID = $_GET['invoiceID'];
            $model = hw_get_invoice($invoiceID);
        
            // if post is not defined and not of type quote
            if(!$model->exists()){
        
                wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request'), 'Invalid Request');
            }

            // add invoice to global variables
            $GLOBALS['invoice'] = $model;
        }
    }

    /**
     * middleware to verify the user has access to the invoice before processing it
     *
     * @return void
     */
    public function ajax_process_payment_no_priv(){

        // if there's no invoice id return false
        if(!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])){
            
            $this->setError('HW_INVALID_INVOICE_ID', 'Invalid invoice');
            wp_send_json(['success' => false, 'error' => $this->getError()]);
        }

        $invoice_id = $_POST['invoice_id'];
        $invoice = hw_get_invoice($invoice_id);
        $session = Session::getInstance();

        $auth = $session->get('hw_pos_can_view_invoice_' . $invoice->getID());
        if($auth == true){
            $this->ajax_process_payment();
        }else{
            wp_send_json(['success' => false, 'error' => ['code' => 'SESSION_EXPIRED', 'message' => 'Session has expired. Reloading ...'], 'reload' => true]);
        }
    }

    /**
     * handle process payment ajax request and response
     *
     * @return void
     */
    public function ajax_process_payment(){
        
        $logger = PaymentPoS::getInstance()->getLogger();
        $response = $this->process_payment();

        if(is_wp_error($response)){

            $error_prefix = 'Error';

            if($response->get_error_code() == 'HW_VALIDATION_ERROR'){
                $error_prefix = 'Validation Error';
            }

            $logger->error(
                $error_prefix . ' Code: ' . $response->get_error_code() . '. Message: ' . $response->get_error_message(),
                 self::class
            );

            $message = $response->get_error_message();

            // override message
            if(isset($response->get_error_data()['message'])){
                $message = $response->get_error_data()['message'];
            }

            // send erro response
            wp_send_json([ 'success' => false, 'error' => [ 'message' => $message ] ]);
        }

        wp_send_json(['success' => true, ...$response]);
    }

    /**
     * Process the payment request
     *
     * @return array|\WP_Error
     */
    public function process_payment(){

        $logger = PaymentPoS::getInstance()->getLogger();

        /***************** */
        /**    Invoice    **/
        /***************** */

        // if there's no invoice id return false
        if(!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])){
            
            return new \WP_Error('HW_VALIDATION_ERROR', 'Invalid invoice');
        }

        $invoice_id = $_POST['invoice_id'];
        $invoice = hw_get_invoice($invoice_id);

        // if no invoice is found return false
        if(!$invoice->exists()){

            return new \WP_Error('HW_VALIDATION_ERROR', 'Invoice not found.');
        }

        // check wp nonce
        check_ajax_referer( 'hw-pos-invoice-' . $invoice->getID() );

        // log initialization
        $logger->info('Begin processing invoice #' . $invoice->getID() . ' payment', self::class);

        /***************** */
        /**    Quote      **/
        /***************** */

        // check quote pre-requisites
        $quote = $invoice->getQuote();

        $group_id = $quote->get('hw_cv_group_id');
        $booking_id = $quote->get('hw_cv_booking_id');

        // a charge needs the group; the booking id is optional — group-level
        // charges (no per-reservation TO booking yet) are supported by the
        // gateway payload (empty booking_id is omitted, which CV accepts).
        // The quote may not carry the group: fall back to the site option and
        // persist it, exactly like the auto-charge path.
        if(empty($group_id)){
            $group_id = get_option('hw_cv_group_id', '');
            if(!empty($group_id)){
                update_post_meta($quote->getID(), 'hw_cv_group_id', $group_id);
            }
        }
        if(empty($group_id)){

            return new \WP_Error('HW_VALIDATION_ERROR', 'Invalid group id: assign a group id to the quote');
        }

        /***************** */
        /**   Admin options  **/
        /***************** */
        $simulation = false;

        if(current_user_can('administrator')){

            if(isset($_POST['simulate']) && $_POST['simulate'] == 1){
                $simulation = true;
            }
        }

        /***************** */
        /**    Card       **/
        /***************** */

        $card = null;
        $browser_token = null;

        // Let's get the card info
        $use_saved_card = isset($_POST['use_saved_card']) && $_POST['use_saved_card'] == 1;

        if($use_saved_card){

            if(!$quote->hasCardData()){

                return new \WP_Error('HW_VALIDATION_ERROR', 'No card saved with the reservation');
            }

            $card = hw_cv_convert_card_type($quote->getCardData());
        }else{

            // card data is missing
            if(!isset($_POST['card'])){

                return new \WP_Error('HW_VALIDATION_ERROR', 'Missing card information.');
            }

            $card_data = $_POST['card'];

            // browser-tokenized path: the page encrypted the card straight to
            // Classic's public key — only the JWE and the last 4 digits arrive here
            $jwe = isset($card_data['jwe']) ? trim((string) wp_unslash($card_data['jwe'])) : '';

            if($jwe !== ''){

                if(strlen($jwe) > 8192
                   || !preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $jwe)){

                    return new \WP_Error('HW_VALIDATION_ERROR', 'Invalid payment token.');
                }

                if(empty($card_data['holder'])){

                    return new \WP_Error('HW_VALIDATION_ERROR', __('Please insert the card owner name', 'hola-weddings'));
                }

                if(empty($card_data['type']) || !in_array($card_data['type'], ['001', '002', '003', '004', '005'])){

                    return new \WP_Error('HW_VALIDATION_ERROR', __('Please choose a valid card type', 'hola-weddings'));
                }

                $last4 = substr(preg_replace('/\D/', '', (string) ($card_data['last4'] ?? '')), -4);

                // display-only card: masked number, no expiry, no CVV
                $card = CardBuilder::build('****' . $last4, '', '', '', $card_data['type'], $card_data['holder']);
                $browser_token = $jwe;

            }else{

                // legacy/fallback path: raw card posted (no-JS or token race)
                $logger->info('Raw-card fallback path used for invoice #' . $invoice->getID(), self::class);

                // validate card data
                $card_validation = $this->validateCard($card_data);
                if(is_wp_error($card_validation)){

                    return $card_validation;
                }

                // all good, crete it
                $card = CardBuilder::build(
                    $card_data['number'],
                    $card_data['month'],
                    $card_data['year'],
                    $card_data['ccv'],
                    $card_data['type'],
                    $card_data['holder']
                );
            }
        }

        /***************** */
        /**    Billing    **/
        /***************** */

        // card data is missing
        if(!isset($_POST['billing'])){

            return new \WP_Error('HW_VALIDATION_ERROR', 'Missing billing information.');
        }

        $billing_data = $_POST['billing'];
        
        // validate
        $billing_validation = $this->validateBillingInformation($billing_data);
        if(is_wp_error($billing_validation)){

            return $billing_validation;
        }

        $billing_information = BillingInformationBuilder::build(
            $billing_data['first_name'],
            $billing_data['last_name'],
            $billing_data['email'],
            null,
            $billing_data['address1'],
            $billing_data['locality'],
            $billing_data['administrative_area'],
            $billing_data['country'],
            $billing_data['postal_code']
        );

        /****************************** */
        /**    Terms and Conditions    **/
        /****************************** */
        if(!isset($_POST['terms_and_conditions']) || $_POST['terms_and_conditions'] != 1){

            return new \WP_Error('HW_VALIDATION_ERROR', 'Please accept Terms and Conditions');
        }

        if(!isset($_POST['signature']) || empty($_POST['signature'])){

            return new \WP_Error('HW_VALIDATION_ERROR', 'The signature is missing');
        }

        $signature = $_POST['signature'];

        /***************** */
        /** Create Charge **/
        /***************** */

        $charge = new Charge();
        $charge->setAmount($invoice->getDueAmount())
            ->setGroupID($group_id)
            ->setBookingID($booking_id)
            ->setCard($card)
            ->setBillingInformation($billing_information)
            ->setRefID(hw_cv_generate_ref_id('I-' . $invoice->getID()));

        if(!empty($browser_token)){

            $charge->setPaymentToken($browser_token);
        }
        
        /********************* **/
        /** Create Transaction **/
        /********************* **/
        
        // save card data but remove security number for security.
        // PCI: display/audit needs last-4 only — never store the full PAN
        // (this meta is plaintext JSON).
        $card_data_to_store = $card->toArray();
        unset($card_data_to_store['ccv']);
        if (!empty($card_data_to_store['number']) && strpos((string) $card_data_to_store['number'], '****') !== 0) {
            $card_data_to_store['number'] = '****' . substr((string) $card_data_to_store['number'], -4);
        }

        // transaction meta data
        $meta_data = [
            'hw_currency' => $charge->getCurrency(),
            'hw_amount' => $charge->getAmount(),
            'hw_status' => TransactionStatus::PROCESSING->value,
            'hw_transaction_id' => $charge->getRefID(),
            'hw_invoice_id' => $invoice->getID(),
            'hw_billing_information' => json_encode($billing_information->toArray()),
            'hw_charge' => json_encode($charge->toArray()),
            'hw_card' => json_encode($card_data_to_store),
            'hw_signature' => $signature,
            'hw_ip_address' => hw_get_the_user_ip(),
        ];

        // create transaction
        $transactionModel = Transaction::create( $charge->getRefID(), $meta_data );

        if(is_wp_error($transactionModel)){

            // pass down new WP_Error and provide default error message to UI
            return new \WP_Error(
                'HW_POS_TRANSACTION_CREATION_ERROR', 
                $transactionModel->get_error_message(), 
                [ 'message' => __('An error ocurred while processing the payment', 'hola-weddings') ]
            );
        }

        $logger->info('Transaction created. ID: ' . $transactionModel->getID() , self::class);

        /***************** **/
        /** Process Charge **/
        /***************** **/

        try{

            // get the gateway
            $paymentGateway = !$simulation ? new ClassicVacationGateway() : new PaymentGatewaySimulator();

            // update transaction with selected payment gateway
            $transactionModel->set('hw_gateway', $paymentGateway->getId());

            if(!$paymentGateway->process( $charge, $transactionModel )){

                // a declined charge must leave a FAILED transaction (the ApiException path never set it) - hw-card-attempts stamps Zoho from that
                if($transactionModel->getStatus() !== TransactionStatus::FAILED->value) $transactionModel->setStatus( TransactionStatus::FAILED );

                return new \WP_Error( 'HW_PAYMENT_GATEWAY_EXCEPTION', __('An error ocurred while processing the payment', 'hola-weddings') );
            }

        }catch(Exception $e){

            $transactionModel->setStatus( TransactionStatus::FAILED );
            return new \WP_Error( 'HW_PAYMENT_GATEWAY_EXCEPTION', $e->getMessage() , [ 'message' => __('An error ocurred while processing the payment', 'hola-weddings')]);
        }

        /**
         * payment has been processed and transaction updated
         */
        do_action( 'hw_pos_payment_processed', $transactionModel );

        // log 
        $logger->info('Charge processed successfully.' , self::class);

        // return false in any of the previous options haven't worked
        return [
            'redirect' => add_query_arg( ['transactionID' => $transactionModel->getID()], hw_get_invoice_payment_confirmation_url() ),
            'data' => [
                'status' => $transactionModel->getStatus(),
                'transactionID' => $transactionModel->getID(),
            ]
        ];
    }

    /**
     * validate card data
     *
     * @param array $data
     * @return bool
     */
    protected function validateCard($data){

         // Card owner
         if(empty($data['holder'])){
            $this->setError( 'HW_POS_CARD_HOLDER', __('Please insert the card owner name', 'hola-weddings'));
            return false;
        }

        // Card type
        if(empty($data['type']) || !in_array($data['type'], ['001', '002', '003', '004', '005'])){
            $this->setError( 'HW_POS_CARD_TYPE',__('Please choose a valid card type', 'hola-weddings'));
            return false;
        }
        
        // Card number
        if(empty($data['number'])){
            
            $this->setError(  'HW_POS_CARD_NUMBER', __('Please insert the card number', 'hola-weddings'));
            return false;
        }
        
        // Card month
        if(empty($data['month'])){
            $this->setError( 'HW_POS_CARD_MONTH', __('Please insert the card expiration month', 'hola-weddings'));
            return false;
        }else{
            
            // check if the month is in the range 1-12
            
            if( intval($data['month']) < 1 || intval($data['month']) > 12){
                
                $this->setError( 'HW_POS_CARD_MONTH_RANGE', __('Invalid expiration month', 'hola-weddings'));
                return false;
            }
            
            // if current year check if the chosen month is at least the current
            if(intval($data['year']) == intval(date("Y")) && intval($data['month']) < intval(date("n"))){
                
                $this->setError( 'HW_POS_CARD_MONTH_OLD', __('Invalid expiration month', 'hola-weddings'));
                return false;
            }
        }
        
        // Card year
        if(empty($data['year'])){
            $this->setError( 'HW_POS_CARD_YEAR', __('Please insert the card expiration year', 'hola-weddings'));
            return false;
        }else{
            
            if( intval($data['year']) < intval(date("Y"))){
                
                $this->setError( 'HW_POS_CARD_YEAR_RANGE', __('Invalid expiration year', 'hola-weddings'));
                return false;
            }
        }
        
        // CCV
        if(empty($data['ccv'])){
            $this->setError( 'HW_POS_CARD_CCV', __('Please insert the card CCV', 'hola-weddings'));
            return false;
        }else{
            
            $ccv_length = $data['type'] == '003' ? 4 : 3; // if amex
            $ccv_max = $data['type'] == '003' ? 10000 : 1000; // if amex

            if( intval($data['ccv']) < 0 || intval($data['ccv']) > $ccv_max
               || strlen($data['ccv']) != $ccv_length ){
                
                $this->setError( 'HW_POS_CARD_CCV_LENGTH', __('Invalid CCV length', 'hola-weddings'));
                return false;
            }
        }

        return true;
    }

    /**
     * validate billing information data
     *
     * @param array $data
     * @return bool
     */
    protected function validateBillingInformation($data){

        /*
         * Customer
         */ 
        if(empty($data['first_name'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing first name', 'hola-weddings'));
        }
        
        if(empty($data['last_name'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR',__('Missing last name', 'hola-weddings'));
        }
        
        if(empty($data['email']) || !filter_var( $data['email'] ,FILTER_VALIDATE_EMAIL)){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Invalid email', 'hola-weddings'));
        }

        if(empty($data['address1'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing address', 'hola-weddings'));
        }

        if(empty($data['locality'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing City', 'hola-weddings'));
        }

        if(empty($data['postal_code'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing post code', 'hola-weddings'));
        }
        
        if(empty($data['administrative_area'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing State/Province', 'hola-weddings'));
        }

        if(empty($data['country'])){
            return new \WP_Error( 'HW_VALIDATION_ERROR', __('Missing Country', 'hola-weddings'));
        }

        return true;
    }

    /**
     * set the error message
     *
     * @param string $message
     * @return void
     */
    public function setError($code, $message){

        $this->error = [
            'code' => $code,
            'message' => $message,
        ];
    }

    /**
     * get the error message
     *
     * @param string $message
     * @return string
     */
    public function getError(){

        return $this->error;
    }

    /**
     * return an array of messages for JS
     *
     * @return array
     */
    public function get_i18n_messages(){
        return [
            "billing_first_name" => __("Insert your first name", 'hola-weddings'),
            "billing_last_name" => __("Insert your last name", 'hola-weddings'),
            "billing_phone_number" => __("Insert the phone number", 'hola-weddings'),
            "billing_email" => [
                'required' => __("Insert your email address", 'hola-weddings'),
                'email' => __("Insert a valid email address", 'hola-weddings')
            ],
            "billing_address" => __("Insert a valid address", 'hola-weddings'),
            "billing_postal_code" => __("Insert Postal Code", 'hola-weddings'),
            "billing_city" => __("Insert a City", 'hola-weddings'),
            "billing_state" => __("Insert a State / Province", 'hola-weddings'),
            "billing_country" => __("Select a Country", 'hola-weddings'),
            "card_owner" => __("Insert card holder name", 'hola-weddings'),
            "card_number"=> [
                'required' => __("Insert the card number", 'hola-weddings'),
                'minlength' => __("The card number must be 16 digits", 'hola-weddings'),
            ],
            "card_month"=> [
                'required' => __("Required", 'hola-weddings'),
                'minlength' => __("2 digits", 'hola-weddings'),
                'maxlength' => __("2 digits", 'hola-weddings'),
                'digits' => __("Digits only", 'hola-weddings'),
            ],
            "card_year"=> [
                'required' => __("Required", 'hola-weddings'),
                'minlength' => __("4 digits", 'hola-weddings'),
                'maxlength' => __("4 digits", 'hola-weddings'),
                'digits' => __("Digits only", 'hola-weddings'),
            ],
            "card_ccv"=> [
                'required' => __("Required", 'hola-weddings'),
                'minlength' => __("3 digits", 'hola-weddings'),
                'maxlength' => __("3 digits", 'hola-weddings'),
                'digits' => __("Digits only", 'hola-weddings'),
            ],
            "terms_and_conditions" => __("Please accept the terms and conditions", 'hola-weddings'),
            "signature" => __("The signature is required", 'hola-weddings'),
        ];
    }
}