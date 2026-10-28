<?php

namespace HW\Integrations\ClassicVacations\CRM\Utils;

use HW\Objects\HWObject;

class JWEPayload extends HWObject{

    protected $jwt;

    protected $card_number;

    protected $expiration_year;

    protected $expiration_month;

    protected $ccv;

    protected $type;

    /**
     * setter for JWT property
     *
     * @param string $jwt
     * @return void
     */
    public function setJWT($jwt){

        $this->set('jwt', $jwt);
    }

    /**
     * setter for card number
     *
     * @param string $number
     * @return void
     */
    public function setCardNumber($number){

        $this->set('card_number', $number);
    }

    /**
     * setter for expiration year and month
     *
     * @param string $year
     * @param string $month
     * @return void
     */
    public function setExpiration($year, $month){

        $this->set('expiration_year', $year);
        $this->set('expiration_month', $month);
    }

    /**
     * setter methos for security number
     *
     * @param string $ccv
     * @return void
     */
    public function setCCV($ccv){

        $this->set('ccv', $ccv);
    }

    /**
     * setter method for card type
     * 
     * 001	Visa
     * 002	Mastercard
     * 003	American Express
     * 004	Discover
     * 005	Diners Club
     *
     * @param string $type
     * @return void
     */
    public function setCardType($type){

        $this->set('type', $type);
    }

    /**
     * build and return the JWE
     *
     * @return array
     */
    public function toArray(){

        return [
            'data' => [
                'paymentInformation' => [
                    'card' => [
                        'expirationYear' => $this->expiration_year,
                        'number' => $this->card_number,
                        'securityCode' => $this->ccv,
                        'expirationMonth' => $this->expiration_month,
                        'type' => $this->type
                    ]
                ]
            ],
            'context' => $this->jwt,
            'index' => 0
        ];
    }

    /**
     * return the JWE JSON serialized
     *
     * @return string
     */
    public function toJSON(){

        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
    }
}