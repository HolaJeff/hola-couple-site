<?php

namespace HW\Integrations\ClassicVacations\CRM\Groups;

use HW\Integrations\ClassicVacations\CRM\Utils\APIException;

class Group{

    /**
     * return a group overview
     *
     * @param int $id
     * @return object
     */
    public static function getGroupOverview($id){

        $apiHandler = new GroupOperations();
        $apiResponse = $apiHandler->getRecord($id);
        $body = $apiResponse->getBody();

        // handle an API exception
        if(isset($body['Exception'])){
            
            return new APIException($body['Exception']);
        }

        return new GroupOverview($body);
    }
}