<?php

namespace HW\Payment\Utilities;

use HW\Models\CRM\BillingInformation;
use HW\Utils\Card;

class Charge{

    protected $ref_id;

    protected $card;

    protected $billing;

    protected $amount;

    protected $currency = 'USD';

    protected $group_id;

    protected $booking_id;

    // a Flex JWE built in the guest's browser; when set, the gateway charges with it
    // directly and the card object is display-only (masked number, no CVV)
    protected $payment_token;

    /**
     * setter method for the pre-built payment token
     *
     * @param string $token
     * @return Charge
     */
    public function setPaymentToken($token){

        $this->payment_token = $token;

        return $this;
    }

    /**
     * getter method for the pre-built payment token
     *
     * @return string|null
     */
    public function getPaymentToken(){

        return $this->payment_token;
    }

    /**
     * setter method for card property
     *
     * @param Card $card
     * @return Charge
     */
    public function setCard(Card $card){

        $this->card = $card;

        return $this;
    }

    /**
     * setter method for billing information
     *
     * @param BillingInformation $billing
     * @return Charge
     */
    public function setBillingInformation(BillingInformation $billing){

        $this->billing = $billing;

        return $this;
    }

    /**
     * set the amount
     *
     * @param int $amount
     * @return Charge
     */
    public function setAmount($amount){

        $this->amount = $amount;

        return $this;
    }

    /**
     * set the group id
     *
     * @param int $group_id
     * @return Charge
     */
    public function setGroupID($group_id){

        $this->group_id = $group_id;

        return $this;
    }

    /**
     * set the booking id
     *
     * @param int $booking_id
     * @return Charge
     */
    public function setBookingID($booking_id){

        $this->booking_id = $booking_id;

        return $this;
    }

    /**
     * set the ref id
     *
     * @param int $ref_id
     * @return Charge
     */
    public function setRefID($ref_id){

        $this->ref_id = $ref_id;

        return $this;
    }

    /**
     * getter method for card property
     *
     * @return Card
     */
    public function getCard(){

        return $this->card;
    }

    /**
     * getter method for group id
     *
     * @return string
     */
    public function getGroupID(){

        return $this->group_id;
    }

    /**
     * getter method for group id
     *
     * @return string
     */
    public function getBookingID(){

        return $this->booking_id;
    }

    /**
     * getter method for amount
     *
     * @return string
     */
    public function getAmount(){

        return $this->amount;
    }

    /**
     * getter method for currency
     *
     * @return string
     */
    public function getCurrency(){

        return $this->currency;
    }

    /**
     * getter method for ref id
     *
     * @return string
     */
    public function getRefID(){

        return $this->ref_id;
    }

    /**
     * getter method for billing information
     *
     * @return BillingInformation
     */
    public function getBillingInformation(){

        return $this->billing;
    }

    /**
     * convert object to array
     *
     * @return array
     */
    public function toArray(){

        return [
            'ref_id' => $this->ref_id,
            'billing' => $this->billing->toArray(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'group_id' => $this->group_id,
            'booking_id' => $this->booking_id
        ];
    }
}