<?php

namespace HW\Integrations\ClassicVacations\CRM\Utils;

use HW\Objects\HWObject;

class APIException extends HWObject{

    protected $error_code;

    protected $message;

    protected $time_stamp;

    public function __construct($data)
    {
        if(isset($data['Message'])){

            $message = $data['Message'];
            
            // let's split the message into error code and details
            $message = explode(':', $message, 2);
            
            $this->error_code = trim($message[0]);
            $this->message = json_decode(trim($message[1]), true)[0];
        }
    }

    public function getErrorCode(){
        return isset($this->error_code) ? $this->error_code : '';
    }

    public function getErrorDescription(){
        return isset($this->message['error_description']) ? $this->message['error_description'] : '';
    }
}