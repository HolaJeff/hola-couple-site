<?php

namespace HW\Integrations\ClassicVacations\Payment;

use Exception;
use HW\Enums\TransactionStatus;
use HW\Integrations\ClassicVacations\CRM\Payment\Payment;
use HW\Integrations\ClassicVacations\CRM\Utils\APIException;
use HW\Integrations\ClassicVacations\CRM\Utils\PaymentJWEBuilder;
use HW\Integrations\ClassicVacations\CRM\Utils\PaymentPayload;
use HW\Integrations\ClassicVacations\API\Initializer;
use HW\Integrations\ClassicVacations\CRM\Payment\MakePaymentResponse;
use HW\Integrations\ClassicVacations\CRM\Payment\PaymentRecord;
use HW\Models\Transaction;
use HW\Payment\Gateway\PaymentGateway;
use HW\Payment\PoS;
use HW\Payment\Utilities\Charge;
use HW\Payment\Utilities\PaymentError;
use HW\Payment\Utilities\PaymentResponse;

class ClassicVacationGateway extends PaymentGateway{

    protected $error;

    protected $last_response;

    protected $last_payload;

    /**
     * get gateway human name
     *
     * @return string
     */
    public function getName(){

        return 'Classic Vacations';
    }

    /**
     * get gateway id
     *
     * @return string
     */
    public function getId(){

        return 'classic-vacations';
    }

    /**
     * process the payment
     *
     * @param Charge $charge
     * @return boolean|\WP_Error
     */
    public function process( Charge $charge, Transaction $transaction){

        $logger = PoS::getInstance()->getLogger();

        try{

            // Initialize Connection
            Initializer::getInitializer();

        }catch(Exception $e){

            $logger->error('Error while initializing Classic Vacation. Refer to Classic Vacation log.', self::class);
            return false;
        }

        $jwt = Payment::generatePaymentContext();

        if($jwt instanceof APIException){

            $logger->error('Error while getting Classic Vacation JWT. Refer to Classic Vacation log.', self::class);
            return false;
        }

        // get reference id
        $ref_id = $charge->getRefID();
        if(empty($ref_id)){

            $ref_id = hw_cv_generate_ref_id();
        }

        // tokenize the card
        $payment_token = PaymentJWEBuilder::tokenize($jwt, $charge->getCard());

        // build payment request payload
        $payload = new PaymentPayload();
        
        $payload->setRefID($ref_id)
            ->setGroupID($charge->getGroupID())
            ->setBookingID($charge->getBookingID())
            ->setAmount($charge->getAmount())
            ->setCurrency($charge->getCurrency())
            ->setBillingInformation($charge->getBillingInformation())
            ->setPaymentToken($payment_token);

        $this->last_payload = $payload;

        try{

            $response = Payment::makePayment($payload);

        }catch(Exception $e){

            $logger->error( sprintf('An exception ocurred while processing the payment: %s', $e->getMessage() ), self::class );
            return false;
        }

        // handle unsuccessful payment
        if($response instanceof ApiException){

            $logger->error( sprintf('Payment exception response. Code: %s. Message: %s',  $response->getErrorCode(), $response->getErrorDescription()), self::class );
            return false;
        }

        $logger->info( sprintf('Payment processed with status: %s', $response->getStatus()), self::class );

        // update transaction status
        switch($response->getStatus()){
            case 'SUCCESS':
                $transaction->setStatus( TransactionStatus::CONFIRMED );
                break;
            case 'PENDING':
                $transaction->setStatus( TransactionStatus::PENDING );
                break;
            case 'ERROR':
            case 'FAIL':
                $transaction->setStatus( TransactionStatus::FAILED );
                break;
        }

        $transaction->addNote( sprintf('Status updated to %s', $transaction->getStatus()), $this->getName() );

        // store payload data for debug
        $transaction->set('hw_cv_api_request_payload', $payload->toJSON());

        // store response body
        if($response instanceof MakePaymentResponse){

            $transaction->set('hw_cv_response_body', json_encode($response->getRawData()));
        }

        // if payment was successful get payment info from CV API
        $payment_record = Payment::getRecord($charge->getRefID(), $charge->getBookingID(), $charge->getGroupID());
        if($payment_record instanceof PaymentRecord){

            $transaction->set('hw_cv_transaction_data', json_encode($payment_record));
        }

        return true;
    }
}