<?php

use HW\Models\Invoice;
use HW\Session;

/**
 * check user auth for viewing an invoice
 *
 * @param Invoice $invoice
 * @return bool
 */
function hw_pos_can_user_access_invoice( Invoice $invoice ){

    $session = Session::getInstance();

    // check if user is not logged in then redirect to home
    /* if(!is_user_logged_in()){
        $redirect = hw_get_invoice_payment_permalink( $_GET['invoiceID'] );
        wp_safe_redirect(wp_login_url($redirect));
        die;
    } */

    if(is_user_logged_in()){

        // allow admin to skip the verification
        if(current_user_can('administrator')){

            return true;
        }
        
        $user_id = get_current_user_id();
        
        // if user is not owner of the quote then redirect
        if($invoice->getOwner() == $user_id){
            
            return true;
        }
    }
    
    if($session->get('hw_pos_can_view_invoice_' . $invoice->getID())){

        return true;
    }

    return false;
}

/**
 * return invoice payment confirmation base url
 *
 * @return string
 */
function hw_get_invoice_payment_confirmation_url(){

    return home_url( '/invoice-payment/confirmation/' );
}