<?php

namespace HW\Integrations\ClassicVacations\CRM\Payment;

use HW\Objects\HWObject;

class PaymentRecord extends HWObject{

    public $client_reference_id = null;

    public $created_by;

    public $group_id;

    public $booking_id;

    public $status;

    public $created_at;

    public $currency;

    public $amount;
    
    public function getRefId(){

        return $this->get('client_reference_id');
    }

    public function getCreatedBy(){

        return $this->get('created_by');
    }

    public function getGroupID(){

        return $this->get('group_id');
    }

    public function getBookingID(){

        return $this->get('booking_id');
    }

    public function getStatus(){

        return $this->get('status');
    }

    public function getCreatedAt(){

        return $this->get('created_at');
    }

    public function getCurrency(){

        return $this->get('currency');
    }

    public function getAmount(){

        return $this->get('amount');
    }

    public function setRefId($value){

        return $this->set('client_reference_id', $value);
    }
}