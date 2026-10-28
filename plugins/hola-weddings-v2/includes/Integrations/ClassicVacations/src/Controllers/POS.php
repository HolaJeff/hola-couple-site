<?php

namespace HW\Integrations\ClassicVacations\Controllers;

use HW\Integrations\ClassicVacations\CRM\Payment\Payment;
use HW\Integrations\ClassicVacations\CRM\Payment\PaymentRecord;
use HW\Integrations\ClassicVacations\Models\Charge;
use HW\Integrations\ClassicVacations\Models\PaymentProcessor;
use HW\Models\CRM\BillingInformation;
use HW\Models\Transaction;
use HW\Utils\BillingInformationBuilder;
use HW\Utils\CardBuilder;

class POS{

    protected $error;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function processCharge(){

        /***************** */
        /**    Invoice    **/
        /***************** */

        // if there's no invoice id return false
        if(!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])){
            
            $this->setError('Invalid invoice id.');
            return false;
        }

        $invoice_id = $_POST['invoice_id'];
        $invoice = hw_get_invoice($invoice_id);

        // if no invoice is found return false
        if(!$invoice->exists()){

            $this->setError('Invoice not found.');
            return false;
        }

        /***************** */
        /**    Quote      **/
        /***************** */

        // check quote pre-requisites
        $quote = $invoice->getQuote();

        $group_id = $quote->get('hw_cv_group_id');
        $booking_id = $quote->get('hw_cv_booking_id');

        // First-payment-at-booking: a fresh quote cannot carry a group id yet (it is
        // normally pasted into wp-admin AFTER booking). Fall back to the couple
        // site's group id, seeded from the contract's CV group number (test sites
        // point it at the CV dummy group instead). A quote-level id always wins.
        if(empty($group_id)){
            $group_id = get_option('hw_cv_group_id', '');
        }

        // is not possible to do a charge without both group_id and booking_id
        if(empty($group_id)){

            $this->setError('Invalid group id: assign a group id to the quote');
            return false;
        }

        if(empty($booking_id)){

            // Group-level charging (clone pilot / Phase-3 prototype): CV's API accepts
            // posts without a booking_id. Allowed ONLY when the network flag is on --
            // default behaviour is unchanged.
            if( ! get_network_option(null, 'hw_cv_allow_group_level', false) ){

                $this->setError('Invalid booking id: assign a booking id to the quote');
                return false;
            }

            $booking_id = null;
        }

        /***************** */
        /**    Card       **/
        /***************** */

        // Let's get the card info
        $use_saved_card = isset($_POST['use_saved_card']) && $_POST['use_saved_card'] == 1;

        if($use_saved_card && $quote->hasCardData()){

            $card = hw_cv_convert_card_type($quote->getCardData());
        }else{

            // card data is missing
            if(!isset($_POST['card'])){

                $this->setError('Missing card information.');
                return false;
            }

            $card_data = $_POST['card'];

            // validate card data
            if(!$this->validateCard($card_data)){

                return false;
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

        /***************** */
        /**    Amount     **/
        /***************** */

        $amount = $_POST['amount'] == 'full' ? $invoice->getDueAmount() : $_POST['custom_amount'];

        // if the custom amount is lower than 1 and higher than due amount return
        if($_POST['amount'] == 'custom' && ( $amount <= 0 || $amount > $invoice->getDueAmount())){

            $this->setError(sprintf('Invalid amount. Value should be between 1 and %s', $invoice->getDueAmount()));
            return false;
        }

        /***************** */
        /**    Billing    **/
        /***************** */

        // card data is missing
        if(!isset($_POST['billing'])){

            $this->setError('Missing billing information.');
            return false;
        }

        $billing_data = $_POST['billing'];
        
        // validate
        if(!$this->validateBillingInformation($billing_data)){

            return false;
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

        /***************** */
        /** Create Charge **/
        /***************** */

        $charge = new Charge();
        $charge->setAmount($amount)
            ->setGroupID($group_id)
            ->setBookingID($booking_id)
            ->setCard($card)
            ->setBillingInformation($billing_information)
            ->setRefID(hw_cv_generate_ref_id('I-' . $invoice->getID()));

        $simulation = isset($_POST['simulate']) && $_POST['simulate'] == 1 ? true : false;

        // if not simulation process the charge
        if(!$simulation){
            
            /***************** **/
            /** Process Charge **/
            /***************** **/

            if(!$charge->process()){

                $this->setError($charge->getError());
                return false;
            }

            $payment_record = Payment::getRecord($charge->getRefID(), $charge->getBookingID(), $charge->getGroupID());

        }else{

            $payment_record = Payment::getTestPaymentRecord();
        }

        /********************* **/
        /** Create Transaction **/
        /********************* **/

        $meta_data = [
            'hw_currency' => $charge->getCurrency(),
            'hw_amount' => $charge->getAmount(),
            'hw_status' => 'confirmed',
            'hw_transaction_id' => $charge->getRefID(),
            'hw_gateway' => 'Classic Vacation',
            'hw_invoice_id' => $invoice->getID()
        ];

        if($payment_record instanceof PaymentRecord){

            $cv_transaction_metadata = [
                'hw_cv_transaction_data' => json_encode($payment_record)
            ];
            
            $post_data['meta_input'] = array_merge($meta_data['meta_input'], $cv_transaction_metadata);
        }

        $transaction = Transaction::create( $charge->getRefID(), $meta_data );

        return true;
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
            $this->setError(  __('Please insert the card owner name', 'hola-weddings'));
            return false;
        }

        // Card type
        if(empty($data['type'])){
            $this->setError( __('Please choose a valid card type', 'hola-weddings'));
            return false;
        }
        
        // Card number
        if(empty($data['number'])){
            
            $this->setError(  __('Please insert the card number', 'hola-weddings'));
            return false;
        }
        
        // Card month
        if(empty($data['month'])){
            $this->setError(  __('Please insert the card expiration month', 'hola-weddings'));
            return false;
        }else{
            
            // check if the month is in the range 1-12
            
            if( intval($data['month']) < 1 || intval($data['month']) > 12){
                
                $this->setError( __('Invalid expiration month', 'hola-weddings'));
                return false;
            }
            
            // if current year check if the chosen month is at least the current
            if(intval($data['year']) == intval(date("Y")) && intval($data['month']) < intval(date("n"))){
                
                $this->setError( __('Invalid expiration month', 'hola-weddings'));
                return false;
            }
        }
        
        // Card year
        if(empty($data['year'])){
            $this->setError(  __('Please insert the card expiration year', 'hola-weddings'));
            return false;
        }else{
            
            if( intval($data['year']) < intval(date("Y"))){
                
                $this->setError( __('Invalid expiration year', 'hola-weddings'));
                return false;
            }
        }
        
        // CCV
        if(empty($data['ccv'])){
            $this->setError(  __('Please insert the card CCV', 'hola-weddings'));
            return false;
        }else{
            
            $ccv_length = $data['type'] == '003' ? 4 : 3; // if amex
            $ccv_max = $data['type'] == '003' ? 10000 : 1000; // if amex

            if( intval($data['ccv']) < 0 || intval($data['ccv']) > $ccv_max
               || strlen($data['ccv']) != $ccv_length ){
                
                $this->setError(  __('Invalid CCV length', 'hola-weddings'));
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
            $this->setError( __('Missing first name', 'hola-weddings'));
            return false;
        }
        
        if(empty($data['last_name'])){
            $this->setError( __('Missing last name', 'hola-weddings'));
            return false;
        }
        
        if(empty($data['email']) || !filter_var( $data['email'] ,FILTER_VALIDATE_EMAIL)){
            $this->setError( __('Invalid email', 'hola-weddings'));
            return false;
        }

        if(empty($data['address1'])){
            $this->setError( __('Missing address', 'hola-weddings'));
            return false;
        }

        if(empty($data['locality'])){
            $this->setError( __('Missing City', 'hola-weddings'));
            return false;
        }

        if(empty($data['postal_code'])){
            $this->setError( __('Missing post code', 'hola-weddings'));
            return false;
        }
        
        if(empty($data['administrative_area'])){
            $this->setError( __('Missing State/Province', 'hola-weddings'));
            return false;
        }

        if(empty($data['country'])){
            $this->setError(__('Missing Country', 'hola-weddings'));
            return false;
        }

        return true;
    }

    /**
     * set the error message
     *
     * @param string $message
     * @return void
     */
    public function setError($message){

        $this->error = $message;
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
}