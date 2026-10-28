<?php

namespace HW\Controllers;

use HW\Traits\Singleton;

class InvoicePaymentConfirmation extends BaseController{

    use Singleton;

    /**
     * perform middleware checks on template_redirect hook
     *
     * @return void
     */
    public function template_redirect(){

        if(hw_is_invoice_payment_confirmation()){

            if(!isset($_GET['transactionID']) || empty($_GET['transactionID'])){

                wp_die(new \WP_Error('INVALID_REQUEST', 'Invalid Request.'), 'Invalid Request', 400);
            }
        
            $transactionID = $_GET['transactionID'];
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
        }
    }
}