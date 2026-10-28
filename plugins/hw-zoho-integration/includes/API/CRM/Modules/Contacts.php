<?php

namespace HWZoho\API\CRM\Modules;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\record\ResponseWrapper;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\GetRecordParam;
use Exception;

class Contacts{

    protected static $module_name = 'Contacts';

    /**
     * Get a record
     * This method is used to get a single record of a module with ID and print the response.
     * @param moduleAPIName - The API Name of the record's module.
     * @param recordId - The ID of the record to be obtained.
     * @param args - Additional arguments
     * @throws Exception
     */
    public static function getRecord(string $recordId, $args = [])
    {
        
        $recordOperations = new RecordOperations();
        $paramInstance = new ParameterMap();
        
        $fieldNames = ["id", "Email", "First_Name", "Last_Name", "Phone", "Mailing_City", "Mailing_Country", "Mailing_State", "Mailing_Street", "Mailing_Zip"];
        foreach($fieldNames as $fieldName)
        {
        	$paramInstance->add(GetRecordParam::fields(), $fieldName);
        }
        $headerInstance = new HeaderMap();
        
        //Call getRecord method that takes paramInstance, moduleAPIName and recordID as parameter
        $response = $recordOperations->getRecord($recordId, self::$module_name, $paramInstance, $headerInstance);
        if ($response != null) {
            
            if (in_array($response->getStatusCode(), array(204, 304))) {
                //echo ($response->getStatusCode() == 204 ? "No Content\n" : "Not Modified\n");
                return new \WP_Error('NOT_FOUND', $response->getStatusCode() == 204 ? "No Content\n" : "Not Modified\n");
            }
            if ($response->isExpected()) {
                $responseHandler = $response->getObject();
                if ($responseHandler instanceof ResponseWrapper) {
                    
                    $responseWrapper = $responseHandler;
                    $records = $responseWrapper->getData();
                    return $records[0];
                    
                }
                else if ($responseHandler instanceof APIException) {

                    return new \WP_Error($responseHandler->getCode(), $responseHandler->getMessage());
                }
            }
        }

        return false;
    }
}