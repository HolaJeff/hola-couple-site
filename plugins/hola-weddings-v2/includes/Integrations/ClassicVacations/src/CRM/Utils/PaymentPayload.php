<?php

namespace HW\Integrations\ClassicVacations\CRM\Utils;

use HW\Models\CRM\BillingInformation;
use HW\Objects\HWObject;

class PaymentPayload extends HWObject{

    protected $client_reference_id;

    protected $group_id;

    protected $bookind_id;

    protected $amount;

    protected $currency;

    protected $bill_to;

    protected $payment_token;

    /**
     * setter for client reference id
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setRefID($value){

        $this->set('client_reference_id', $value);

        return $this;
    }

    /**
     * setter for group id
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setGroupID($value){

        $this->set('group_id', $value);

        return $this;
    }

    /**
     * setter for expiration year and month
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setBookingID($value){

        $this->set('bookind_id', $value);

        return $this;
    }

    /**
     * setter method for amount
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setAmount($value){

        $this->set('amount', $value);

        return $this;
    }

    /**
     * setter method for currency
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setCurrency($value){

        $this->set('currency', $value);

        return $this;
    }

    /**
     * setter method for billing information
     *
     * @param BillingInformation $billing
     * @return PaymentPayload
     */
    public function setBillingInformation(BillingInformation $billing){

        $data = [
            'first_name' => $billing->getFirstName(),
            'last_name' => $billing->getLastName(),
            'postal_code' => $billing->getPostalCode(),
            'address1' => $billing->getAddress(),
            'email' => $billing->getEmail(),
            'country' => $billing->getCountry(),
            'locality' => $billing->getCity(),
            'administrative_area' => $billing->getState()
        ];

        $this->set('bill_to', $data);

        return $this;
    }

    /**
     * setter method for payment_token
     *
     * @param string $value
     * @return PaymentPayload
     */
    public function setPaymentToken($value){

        $this->set('payment_token', $value);

        return $this;
    }

    /**
     * build and return the JWE
     *
     * @return array
     */
    public function toArray(){

        return [
            'client_reference_id' => $this->client_reference_id,
            'group_id' => $this->group_id,
            'booking_id' => $this->bookind_id,
            'amount_details' => [
                'amount' => $this->amount,
                'currency' => $this->currency
            ],
            'bill_to' => $this->bill_to,
            'payment_token' => $this->payment_token
        ];
    }

    /**
     * return the JSON
     *
     * @return string
     */
    public function toJSON(){

        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
    }
}