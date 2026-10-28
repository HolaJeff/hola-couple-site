<?php

namespace HW\Models\CRM;

use HW\Models\Model;

class BillingInformation extends Model{

    protected $first_name;

    protected $last_name;

    protected $email;

    protected $phone_number;

    protected $address;

    protected $city;

    protected $zip;

    protected $state;

    protected $country;

    /**
     * constructor
     *
     * @param array $properties
     */
    public function __construct($properties = null){
        
        if(isset($properties['postal_code'])){

            $properties['zip'] = $properties['postal_code'];
            unset($properties['postal_code']);
        }

        if(isset($properties['phone'])){

            $properties['phone_number'] = $properties['phone'];
            unset($properties['phone']);
        }

        parent::__construct($properties);
    }

    /**
     * get first name
     *
     * @return string
     */
    public function getFirstName(){

        return $this->get('first_name');
    }

    /**
     * get last name
     *
     * @return string
     */
    public function getLastName(){
        
        return $this->get('last_name');
    }

    /**
     * get email
     *
     * @return string
     */
    public function getEmail(){
        
        return $this->get('email');
    }

    /**
     * get phone
     *
     * @return string
     */
    public function getPhone(){
        
        return $this->get('phone_number');
    }

    /**
     * get address
     *
     * @return string
     */
    public function getAddress(){
        
        return $this->get('address');
    }

    /**
     * get city
     *
     * @return string
     */
    public function getCity(){
        
        return $this->get('city');
    }

    /**
     * get state
     *
     * @return string
     */
    public function getState(){
        
        return $this->get('state');
    }

    /**
     * get country
     *
     * @return string
     */
    public function getCountry(){
        
        return $this->get('country');
    }

    /**
     * get postal code
     *
     * @return string
     */
    public function getPostalCode(){
        
        return $this->get('zip');
    }

    /**
     * set first name
     *
     * @return void
     */
    public function setFirstName($value){

        return $this->set('first_name', $value);
    }

    /**
     * set last name
     *
     * @return void
     */
    public function setLastName($value){
        
        return $this->set('last_name', $value);
    }

    /**
     * set email
     *
     * @return void
     */
    public function setEmail($value){
        
        return $this->set('email', $value);
    }

    /**
     * set phone
     *
     * @return void
     */
    public function setPhone($value){
        
        return $this->set('phone_number', $value);
    }

    /**
     * set address
     *
     * @return void
     */
    public function setAddress($value){
        
        return $this->set('address', $value);
    }

    /**
     * set city
     *
     * @return void
     */
    public function setCity($value){
        
        return $this->set('city', $value);
    }

    /**
     * set state
     *
     * @return void
     */
    public function setState($value){
        
        return $this->set('state', $value);
    }

    /**
     * set country
     *
     * @return void
     */
    public function setCountry($value){
        
        return $this->set('country', $value);
    }

    /**
     * set postal code
     *
     * @return void
     */
    public function setPostalCode($value){
        
        return $this->set('zip', $value);
    }

    /**
     * convert object to array
     *
     * @return array
     */
    public function toArray(){

        return [
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'address' => $this->getAddress(),
            'city' => $this->getCity(),
            'state' => $this->getState(),
            'country' => $this->getCountry(),
            'postal_code' => $this->getPostalCode()
        ];
    }
}