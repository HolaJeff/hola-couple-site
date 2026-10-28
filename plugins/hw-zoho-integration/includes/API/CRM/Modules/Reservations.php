<?php
namespace HWZoho\API\CRM\Modules;

use com\zoho\api\logger\SDKLogger;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\FileBodyWrapper;
use com\zoho\crm\api\record\GetRecordParam;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\record\ResponseWrapper;
use com\zoho\crm\api\util\Choice;

class Reservations
{

    /**
     * return a list of payments by related modules
     *
     * @param string $recordId
     * @param string $moduleAPIName
     * @param array $args
     * @return array
     */
	public static function getRecord(string $recordId)
	{
        $moduleAPIName = 'Reservations';
        $recordOperations = new RecordOperations();
        $paramInstance = new ParameterMap();
        $fieldNames = ["id", "Name"];
        
        foreach($fieldNames as $fieldName)
        {
        	$paramInstance->add(GetRecordParam::fields(), $fieldName);
        }

        $headerInstance = new HeaderMap();

        //Call getRecord method that takes paramInstance, moduleAPIName and recordID as parameter
        $response = $recordOperations->getRecord($recordId, $moduleAPIName, $paramInstance, $headerInstance);
        if ($response != null) {
            
            // record wasn't found
            if (in_array($response->getStatusCode(), array(204, 304))) {
                
                return false;
            }
           
            if ($response->isExpected()) {
                $responseHandler = $response->getObject();
                if ($responseHandler instanceof ResponseWrapper) {
                    
                    $responseWrapper = $responseHandler;
                    $records = $responseWrapper->getData();
                   
                    if($records == null){
                        return false;
                    }

                    return $records[0];
                }
                else if ($responseHandler instanceof APIException) {

                    $exception = $responseHandler;

                    SDKLogger::debug(
                        sprintf('%s - Status: %s - Code: %s - Message: %s', 
                            Reservations::class, 
                            $exception->getStatus()->getValue(),
                            $exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()
                        )
                    );

                    return false;
                    /*$exception = $responseHandler;
                    echo ("Status: " . $exception->getStatus()->getValue() . "\n");
                    echo ("Code: " . $exception->getCode()->getValue() . "\n");
                    echo ("Details: ");
                    foreach ($exception->getDetails() as $key => $value) {
                        echo ($key . " : " . $value . "\n");
                    }
                    echo ("Message : " . ($exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()) . "\n");*/
                }
            }

            return false;
        }
	}
}