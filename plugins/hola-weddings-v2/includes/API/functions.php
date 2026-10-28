<?php

use HW\API\Operations\Hotels;
use HW\API\Operations\Insurance;

/**
 * return a full list of hotels from API
 *
 * @return array
 */
function hw_api_get_hotel_list(){

    return Hotels::getList();
}

/**
 * return an insurance plan
 *
 * @param mixed $id
 * @return array
 */
function hw_api_get_insurance_plan($id){

    return Insurance::getPlan($id);
}