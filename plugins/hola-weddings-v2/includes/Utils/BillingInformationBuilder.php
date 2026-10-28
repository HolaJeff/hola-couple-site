<?php

namespace HW\Utils;

use HW\Models\CRM\BillingInformation;

class BillingInformationBuilder{

    /**
     * return an instance without calling the constructor
     *
     * @return BillingInformation
     */
    public static function build($first_name, $last_name, $email, $phone, $address, $city, $state, $country, $postal_code){

        $class = new \ReflectionClass(BillingInformation::class);

        $billing = $class->newInstanceWithoutConstructor();
        $billing->setFirstName($first_name);
        $billing->setLastName($last_name);
        $billing->setEmail($email);
        $billing->setPhone($phone);
        $billing->setAddress($address);
        $billing->setCity($city);
        $billing->setState($state);
        $billing->setCountry($country);
        $billing->setPostalCode($postal_code);

        return $billing;
    }
}