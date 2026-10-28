<?php

use HW\Models\Invoice;

/**
 * display the payment action in the invoices list of the quote view
 */
add_action('hw_frontend_quote_invoices_actions_column', function($model){

    if($model->getStatus() == 'not-paid'){
        
        switch( hw_get_invoice_payment_btn_action () ){
            case 'payment-form':
                echo sprintf('<a href="%s" class="action" target="_blank">%s</a>', hw_get_invoice_payment_permalink($model), __('Pay Now', 'hola-weddings'));
                break;
            case 'request-payment-link':
                echo hw_get_template('template-parts/account/partials/invoice-request-payment-link-btn', [ 'invoice' => $model, 'style' => 'btn-outline-primary', 'class' => 'rounded-pill' ]);
                break;
        }
    }
});

/**
 * update the invoice on new transaction
 *
 * @param int $post_id
 * @param WP_Post $post
 * @param bool $update
 * @return void
 */
add_action('wp_insert_post', function($post_id, $post, $update){

    if($post->post_type == 'transactions'){

        $transaction = hw_get_transaction($post_id);
        $invoice = $transaction->getInvoice();

        // if invoice is fully paid or over paid change the status
        if($invoice->getDueAmount() <= 0 && $invoice->getStatus() != Invoice::$STATUS_PAID){

            $invoice->setStatus(Invoice::$STATUS_PAID);
            $invoice->setPaidDate(new DateTime('NOW'));
        }else{
            
            $invoice->setStatus(Invoice::$STATUS_NOTPAID);
        }
    }
}, 10, 3);

/**
 * update the invoice on status update
 *
 * @param int $post_id
 * @param WP_Post $post
 * @param bool $update
 * @return void
 */
add_action('hw_model_transactions_metadata_updated', function( $name, $value, $old_value, $model){

    if($name == 'hw_status'){

        $invoice = $model->getInvoice();

        // if invoice is fully paid or over paid change the status
        if($invoice->getDueAmount() <= 0 && $invoice->getStatus() != Invoice::$STATUS_PAID){

            $invoice->setStatus(Invoice::$STATUS_PAID);
            $invoice->setPaidDate(new DateTime('NOW'));
        }else{
            
            $invoice->setStatus(Invoice::$STATUS_NOTPAID);
        }
    }
}, 10, 4);

/**
 * update the invoice status on price change
 *
add_action('wp_insert_post', function($post_id, $post, $update){

    if($post->post_type == 'invoices' && $update){

        $invoice = hw_get_invoice($post_id);

        if($invoice->getStatus() != Invoice::$STATUS_PAID && $invoice->getDueAmount() == 0){

            $invoice->setStatus(Invoice::$STATUS_PAID);

        }elseif($invoice->getDueAmount() > 0){

            if($invoice->getPaidAmount() > 0){
                
                $invoice->setStatus(Invoice::$STATUS_PARTIALLY_PAID);
            }else{

                $invoice->setStatus(Invoice::$STATUS_NOTPAID);
            }
        }
    }
}, 20, 3);*/