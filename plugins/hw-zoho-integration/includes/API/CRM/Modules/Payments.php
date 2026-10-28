<?php

namespace HWZoho\API\CRM\Modules;

use com\zoho\api\logger\SDKLogger;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\record\ActionWrapper;
use com\zoho\crm\api\record\APIException as RecordAPIException;
use com\zoho\crm\api\record\BodyWrapper;
use com\zoho\crm\api\record\Record;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\record\SuccessResponse;
use com\zoho\crm\api\relatedrecords\APIException;
use com\zoho\crm\api\relatedrecords\RelatedRecordsOperations;
use com\zoho\crm\api\relatedrecords\GetRelatedRecordsParam;
use com\zoho\crm\api\relatedrecords\ResponseWrapper;
use com\zoho\crm\api\util\Choice;

class Payments
{

    /**
     * return a list of payments by related modules
     *
     * @param string $recordId
     * @param string $moduleAPIName
     * @param array $args
     * @return Record[]
     */
	public static function getBy(string $recordId, string $moduleAPIName, $args = [])
	{
        $relatedListAPIName = 'Payments';

        $args = wp_parse_args($args,[
            'page' => 1,
            'per_page' => 200,
            'fields' => ['id', 'Name', 'Payment_Amount', 'Payment_Date', 'Date_Paid', 'Status', 'Payment_Number', 'WPInvoiceID']
        ]);

	    $relatedRecordsOperations = new RelatedRecordsOperations($relatedListAPIName, $moduleAPIName, null);
		$paramInstance = new ParameterMap();
		$paramInstance->add(GetRelatedRecordsParam::page(), $args['page']);
		$paramInstance->add(GetRelatedRecordsParam::perPage(), $args['per_page']);
		$headerInstance = new HeaderMap();	
        
        foreach($args['fields'] as $id)
        {
            $paramInstance->add(GetRelatedRecordsParam::fields(), $id);
        }

		//$datetime = date_create("2019-02-26T15:28:34+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		//$headerInstance->add(GetRelatedRecordsHeader::IfModifiedSince(), $datetime);
		$response = $relatedRecordsOperations->getRelatedRecords($recordId, $paramInstance, $headerInstance);
		
		if($response != null)
		{
            //echo("Status code " . $response->getStatusCode() . "\n");
            if(in_array($response->getStatusCode(), array(204, 304)))
            {
                //echo($response->getStatusCode() == 204? "No Content\n" : "Not Modified\n");
                return;
			}
			if($response->isExpected())
			{
				$responseHandler = $response->getObject();
				if($responseHandler instanceof ResponseWrapper)
				{
					$responseWrapper = $responseHandler;
					$records = $responseWrapper->getData();

                    return $records;
				}
				else if($responseHandler instanceof APIException)
				{
					/*$exception = $responseHandler;
					echo("Status: " . $exception->getStatus()->getValue() . "\n");
					echo("Code: " . $exception->getCode()->getValue() . "\n");
					if($exception->getDetails() != null)
					{
						echo("Details: " );
						foreach($exception->getDetails() as $key => $value)
						{
							echo($key . " : " . $value . "\n");
						}
						echo("Message: " . $exception->getMessage()->getValue() . "\n");
					}*/
					return $responseHandler;
				}
			}
		}
	}

	/**
     * Update Record
     * This method is used to update a single record of a module with ID and print the response.
     * @param recordId - The ID of the record to be updated.
	 * @param data - data to update
     * @throws Exception
     */
	public static function updateRecord(string $recordId, array $data)
    {
        $recordOperations = new RecordOperations();
        $request = new BodyWrapper();
        $records = array();
        $record = new Record();

		foreach($data as $key => $value){

			$record->addKeyValue($key, $value);
		}

		//Add Record instance to the list
        array_push($records, $record);
        $request->setData($records);

        $headerInstance = new HeaderMap();

        //Call updateRecord method that takes BodyWrapper instance, ModuleAPIName and recordId as parameter.
        $response = $recordOperations->updateRecord($recordId, 'Payments', $request, $headerInstance);
        if ($response != null) {
            echo ("Status Code: " . $response->getStatusCode() . "\n");
            if ($response->isExpected()) {
                $actionHandler = $response->getObject();
                if ($actionHandler instanceof ActionWrapper) {
                    $actionWrapper = $actionHandler;
                    $actionResponses = $actionWrapper->getData();
                    foreach ($actionResponses as $actionResponse) {
						return $actionResponse;
                        if ($actionResponse instanceof SuccessResponse) {
                            $successResponse = $actionResponse;
                            echo ("Status: " . $successResponse->getStatus()->getValue() . "\n");
                            echo ("Code: " . $successResponse->getCode()->getValue() . "\n");
                            echo ("Details: ");
                            foreach ($successResponse->getDetails() as $key => $value) {
                                echo ($key . " : ");
                                print_r($value);
                                echo ("\n");
                            }
                            echo ("Message: " . ($successResponse->getMessage() instanceof Choice ? $successResponse->getMessage()->getValue() : $successResponse->getMessage()) . "\n");
                        }
                        else if ($actionResponse instanceof RecordAPIException) {
                            $exception = $actionResponse;
                            echo ("Status: " . $exception->getStatus()->getValue() . "\n");
                            echo ("Code: " . $exception->getCode()->getValue() . "\n");
                            echo ("Details: ");
                            foreach ($exception->getDetails() as $key => $value) {
                                echo ($key . " : " . $value . "\n");
                            }
                            echo ("Message : " . ($exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()) . "\n");
                        }
                    }
                }
                else if ($actionHandler instanceof RecordAPIException) {
					return $actionHandler;
                    $exception = $actionHandler;
                    echo ("Status: " . $exception->getStatus()->getValue() . "\n");
                    echo ("Code: " . $exception->getCode()->getValue() . "\n");
                    echo ("Details: ");
                    foreach ($exception->getDetails() as $key => $value) {
                        echo ($key . " : " . $value . "\n");
                    }
                    echo ("Message : " . ($exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()) . "\n");
                }
            } else {
				return false;
                print_r($response);
            }
        }
    }

	/**
     * <h3> Create Records</h3>
     * This method is used to create records of a module and print the response
     * @param moduleAPIName - The API Name of the module to obtain records.
     * @throws Exception
     * @throws SDKException
     */
    public static function createRecord($data)
    {
        
        $recordOperations = new RecordOperations();
        $bodyWrapper = new BodyWrapper();

        //List of Record instances
        $records = array();
        $record = new Record();

		foreach($data as $key => $value){

			$record->addKeyValue($key, $value);
		}
       
        //Add Record instance to the list
        array_push($records, $record);
        $bodyWrapper->setData($records);

        $headerInstance = new HeaderMap();
        $response = $recordOperations->createRecords('Payments', $bodyWrapper, $headerInstance);
        if ($response != null) {
            if ($response->isExpected()) {
                $actionHandler = $response->getObject();
                if ($actionHandler instanceof ActionWrapper) {
                    $actionWrapper = $actionHandler;
                    $actionResponses = $actionWrapper->getData();
                    foreach ($actionResponses as $actionResponse) {
                        if ($actionResponse instanceof SuccessResponse) {

                            $data = $actionResponse->getDetails();
                            return $data['id'];
                            /*$successResponse = $actionResponse;
                            echo ("Status: " . $successResponse->getStatus()->getValue() . "\n");
                            echo ("Code: " . $successResponse->getCode()->getValue() . "\n");
                            echo ("Details: ");
                            foreach ($successResponse->getDetails() as $key => $value) {
                                echo ($key . " : ");
                                print_r($value);
                                echo ("\n");
                            }
                            echo ("Message: " . ($successResponse->getMessage() instanceof Choice ? $successResponse->getMessage()->getValue() : $successResponse->getMessage()) . "\n");
                            */
                        }
                        else if ($actionResponse instanceof RecordAPIException) {

                            $exception = $actionResponse;
                            SDKLogger::debug(
                                sprintf('%s - Status: %s - Code: %s - Message: %s', 
                                    Payments::class, 
                                    $exception->getStatus()->getValue(),
                                    $exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()
                                )
                            );

                            return false;

                            /*$exception = $actionResponse;
                            echo ("Status: " . $exception->getStatus()->getValue() . "\n");
                            echo ("Code: " . $exception->getCode()->getValue() . "\n");
                            echo ("Details: ");
                            foreach ($exception->getDetails() as $key => $value) {
                                echo ($key . " : " . $value . "\n");
                            }
                            echo ("Message : " . ($exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()) . "\n");*/
                        }
                    }
                }
                else if ($actionHandler instanceof RecordAPIException) {

                    $exception = $actionHandler;
                    SDKLogger::debug(
                        sprintf('%s - Status: %s - Code: %s - Message: %s', 
                            Payments::class, 
                            $exception->getStatus()->getValue(),
                            $exception->getMessage() instanceof Choice ? $exception->getMessage()->getValue() : $exception->getMessage()
                        )
                    );

                    return false;
                }
            } else {
                return false;
            }
        }
    }
}