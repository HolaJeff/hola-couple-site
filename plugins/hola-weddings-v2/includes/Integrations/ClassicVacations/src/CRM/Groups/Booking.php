<?php

namespace HW\Integrations\ClassicVacations\CRM\Groups;

use HW\Integrations\ClassicVacations\API\Param;
use HW\Integrations\ClassicVacations\API\ParameterMap;
use HW\Integrations\ClassicVacations\CRM\Utils\APIException;

class Booking{

    /**
     * return a group overview
     *
     * @param int $id
     * @return object
     */
    public static function getBookingDetails($groupId, $bookingId){

        /*$paramInstance = new ParameterMap();
        $paramInstance->add(Param::create('groupID'), $groupId);
        $paramInstance->add(Param::create('bookingID'), $bookingId);*/

        $apiHandler = new BookingOperations();
        $apiResponse = $apiHandler->getRecord($groupId, $bookingId);
        $body = $apiResponse->getBody();

        // handle an API exception
        if(isset($body['Exception'])){
            
            return new APIException($body['Exception']);
        }

        return new GroupOverview($body);
    }
}