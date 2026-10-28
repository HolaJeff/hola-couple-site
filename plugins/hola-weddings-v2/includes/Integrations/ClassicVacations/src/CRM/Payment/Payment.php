<?php

namespace HW\Integrations\ClassicVacations\CRM\Payment;

use HW\Integrations\ClassicVacations\API\ParameterMap;
use HW\Integrations\ClassicVacations\CRM\Utils\APIException;
use HW\Integrations\ClassicVacations\CRM\Utils\PaymentPayload;
use RuntimeException;

class Payment{

    /**
     * generate the JWT Payment context token
     *
     * @return mixed
     */
    public static function generatePaymentContext(){

        $apiHandler = new PaymentOperations();
        $apiResponse = $apiHandler->getContext();
        $body = $apiResponse->getBody();

        // request was not successful
        if($apiResponse->getStatusCode() > 299){

            return new APIException($body);
        }

        // handle an API exception
        if(isset($body['Exception'])){
            
            return new APIException($body['Exception']);
        }
        
        return $body;
    }

    /**
     * generate the JWT Payment context token
     *
     * @return MakePaymentResponse|APIException
     */
    public static function makePayment(PaymentPayload $data){

        $apiHandler = new PaymentOperations();
        $apiResponse = $apiHandler->makePayment($data->toArray());
        $body = $apiResponse->getBody();

        // handle an API exception
        if(isset($body['Exception'])){
            
            return new APIException($body['Exception']);
        }
        
        return new MakePaymentResponse($body);
    }

    /**
     * get payment record by booking
     *
     * @param string $ref_id
     * @param string $booking_id
     * @return PaymentRecord
     */
    public static function getRecord($ref_id, $booking_id = null, $group_id = null){

        $paramsMap = new ParameterMap();

        if(empty($group_id) && empty($booking_id)){
            throw new RuntimeException('Either Booking ID or Group ID should be provided');
        }

        if(!empty($booking_id)){
            $paramsMap->add('booking_id', $booking_id);
        }

        if(!empty($group_id)){
            $paramsMap->add('group_id', $group_id);
        }

        $apiHandler = new PaymentOperations();
        $apiResponse = $apiHandler->getRecord($ref_id, $paramsMap);
        $body = $apiResponse->getBody();

        // retur false when no record is found
        if($apiResponse->getStatusCode() == '404'){

            return false;
        }

        // handle an API exception
        if(isset($body['Exception'])){
            
            return new APIException($body['Exception']);
        }

        return new PaymentRecord($body);
    }

    /**
     * return a fake payment record for testing purpose
     *
     * @return PaymentRecord
     */
    public static function getTestPaymentRecord(){

        $obj = new PaymentRecord();
        $obj->setRefId('hola-fake-payment-record');
        return $obj;
    }
}