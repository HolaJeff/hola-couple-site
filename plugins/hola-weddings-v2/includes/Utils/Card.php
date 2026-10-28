<?php

namespace HW\Utils;

use HW\Objects\HWObject;

class Card extends HWObject{

    protected $holder;

    protected $number;

    protected $month;
    
    protected $year;

    protected $ccv;

    protected $type;

    /**
     * constructor
     *
     * @param string $number
     * @param string $month
     * @param string $year
     * @param string $ccv
     * @param string $type
     */
    public function __construct($number, $month, $year, $ccv, $type, $card_holder){
        
        $this->number = $number;
        $this->month = $month;
        $this->year = $year;
        $this->ccv = $ccv;
        $this->type = $type;
        $this->holder = $card_holder;
    }

    /**
     * return card number
     *
     * @return string
     */
    public function getNumber(){
        return $this->number;
    }

    /**
     * return month
     *
     * @return string
     */
    public function getExpMonth(){
        return $this->month;
    }

    /**
     * return year
     *
     * @return string
     */
    public function getExpYear(){
        return $this->year;
    }

    /**
     * return ccv
     *
     * @return string
     */
    public function getCCV(){
        return $this->ccv;
    }

    /**
     * return type
     *
     * @return string
     */
    public function getType(){
        return $this->type;
    }

    /**
     * return card holder
     *
     * @return self
     */
    public function getCardHolder(){
        return $this->holder;
    }

    /**
     * setter for card number
     *
     * @return self
     */
    public function setNumber($value){
        
        $this->set('number', $value);
        return $this;
    }

    /**
     * return month
     *
     * @return self
     */
    public function setExpMonth($value){
        $this->set('month', $value);
        return $this;
    }

    /**
     * return year
     *
     * @return self
     */
    public function setExpYear($value){
        $this->set('year', $value);
        return $this;
    }

    /**
     * return ccv
     *
     * @return self
     */
    public function setCCV($value){
        $this->set('ccv', $value);
        return $this;
    }

    /**
     * return type
     *
     * @return self
     */
    public function setType($value){
        $this->set('type', $value);
        return $this;
    }

    /**
     * return card holder
     *
     * @return self
     */
    public function setCardHolder($value){
        $this->set('holder', $value);
        return $this;
    }

    /**
     * convert object to array
     *
     * @return array
     */
    public function toArray(){
        
        return [
            'holder' => $this->getCardHolder(),
            'number' => $this->getNumber(),
            'exp_month' => $this->getExpMonth(),
            'exp_year' => $this->getExpYear(),
            'ccv' => $this->getCCV(),
            'type' => $this->getType()
        ];
    }
}