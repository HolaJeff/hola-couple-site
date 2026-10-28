<?php

namespace HW\Payment\Gateway;

use HW\Enums\TransactionStatus;
use HW\Models\Transaction;
use HW\Payment\PoS;
use HW\Payment\Utilities\Charge;

class PaymentGatewaySimulator extends PaymentGateway{

    public function getId(){

        return 'payment-gateway-simulator';
    }

    public function getName(){

        return 'Payment Gateway Simulator';
    }

     /**
     * process payment
     *
     * @param Charge $charge
     * @param Transaction $transaction
     * @return boolean
     */
    public function process(Charge $charge, Transaction $transaction){

        $logger = PoS::getInstance()->getLogger();

        $response_type = $_POST['test_response_type'] ?? 'success';
        $status = '';

        $logger->info( 'Payment Simulation' , self::class);
        $transaction->addNote( 'Payment Simulation', $this->getName() );
        
        switch($response_type){
            case 'success':
                $status = TransactionStatus::CONFIRMED;
                break;
            case 'pending':
                $status = TransactionStatus::PENDING;
                break;
            case 'failed':
            default:
                $status = TransactionStatus::FAILED;
                break;
        }

        $transaction->set('hw_is_simulation', true);
        $transaction->setStatus($status);
        $transaction->addNote( sprintf('Simulation response type %s.', $response_type), $this->getName() );
        $transaction->addNote( sprintf('Status updated to %s', $status->value), $this->getName() );

        if($status == 'failed'){
            return false;
        }

        return true;
    }
}