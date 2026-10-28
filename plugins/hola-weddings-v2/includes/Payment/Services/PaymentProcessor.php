<?php

namespace HW\Payment\Services;

use Exception;
use HW\Enums\TransactionStatus;
use HW\Models\Transaction;
use HW\Payment\Gateway\PaymentGatewayInterface;
use HW\Payment\Utilities\Charge;

class PaymentProcessor{

    protected $paymentGateway;

    protected $logger;

    /**
     * constructor
     */
    public function __construct( PaymentGatewayInterface $gateway ){
        
        $this->paymentGateway = $gateway;
        $this->logger = hw_cv_get_logger();
    }

    /**
     * handle payment processing logic
     *
     * @param Charge $charge
     * @param Transaction $transaction
     * @return \WP_Error
     */
    public function processPayment( Charge $charge, Transaction $transaction ){

        try{

            $response = $this->paymentGateway->process( $charge, $transaction );
            if(is_wp_error($response)){

                return new \WP_Error('HW_PROCESS_CHARGE_ERROR', $response->get_error_message());
            }

        }catch(Exception $e){

            return new \WP_Error('HW_PAYMENT_GATEWAY_EXCEPTION', $e->getMessage() , [ 'message' => __('An error ocurred while processing the payment', 'hola-weddings')]);
        }

        return true;
    }
}