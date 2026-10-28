<?php

namespace HW\Integrations\ClassicVacations\CRM\Payment;

use RuntimeException;

class MakePaymentResponse{

    protected $raw_body; 

    protected $payment_status;

    protected $client_reference_id;

    protected $error;

    protected $error_code;

    protected $error_message;

    public function __construct($data)
    {
        
        if(!isset($data['payment_status'])){
            throw new RuntimeException('Invalid payment response');
        }

        $this->raw_body = $data;

        $this->payment_status = $data['payment_status'];

        if(isset($data['client_reference_id'])){
            $this->client_reference_id = $data['client_reference_id'];
        }

        if(isset($data['error_message'])){

            $this->error = $data['error_message'];

            // error messagge has not consistent format
            switch(true){
                case strpos( $data['error_message'], 'Decline' ) !== false:
                    $error = explode('|', $this->error);
                    $this->error_code = $error[1];
                    $this->error_message = $error[0];
                    break;
                case strpos($data['error_message'], '|') !== false:
                    list($errorCode, $errorMessage) = explode('|', $this->error);
                    $this->error_code = $errorCode;
                    $this->error_message = $errorMessage;
                    break;
                case strpos($data['error_message'], 'Error:') !== false:
                default:
                    $this->error_message = $data['error_message'];
                    break;
            }            
        }
    }

    /**
     * return true if success
     *
     * @return boolean
     */
    public function isSuccess(){
        return $this->payment_status == 'SUCCESS' ? true : false;
    }

    /**
     * return status
     *
     * @return string
     */
    public function getStatus(){
        return $this->payment_status;
    }

    /**
     * return client reference
     *
     * @return string
     */
    public function getClientReference(){
        return $this->client_reference_id;
    }

    /**
     * return error
     *
     * @return array
     */
    public function getError(){
        return [
            'code' => $this->error_code,
            'message' => $this->error_message
        ];
    }

    /**
     * return error code
     *
     * @return string
     */
    public function getErrorCode(){
        return $this->error_code;
    }

    /**
     * return error message
     *
     * @return string
     */
    public function getErrorMessage(){
        return $this->error_message;
    }

    /**
     * return raw data
     *
     * @return void
     */
    public function getRawData(){
        return $this->raw_body;
    }
}