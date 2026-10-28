<?php

namespace HWZoho\API\CRM\Modules;
use com\zoho\crm\api\record\RecordOperations;
use com\zoho\crm\api\ParameterMap;
use com\zoho\crm\api\HeaderMap;
use com\zoho\crm\api\record\ResponseWrapper;
use com\zoho\crm\api\record\APIException;
use com\zoho\crm\api\record\GetRecordsParam;
use com\zoho\crm\api\record\GetRecordParam;
use com\zoho\crm\api\record\SearchRecordsParam;
use com\zoho\crm\api\exception\SDKException;
use com\zoho\crm\api\relatedrecords\APIException as RelatedrecordsAPIException;
use com\zoho\crm\api\relatedrecords\GetRelatedRecordsParam;
use com\zoho\crm\api\relatedrecords\RelatedRecordsOperations;
use com\zoho\crm\api\relatedrecords\ResponseWrapper as RelatedrecordsResponseWrapper;
use Exception;

class Potentials{

    protected static $module_name = 'Potentials';
    
    public static function getRecords($args = [])
    {        

        $args = wp_parse_args($args,[
            'page' => 1,
            'per_page' => 200
        ]);

        //Get instance of RecordOperations Class that takes moduleAPIName as parameter
        $recordOperations = new RecordOperations();
        $paramInstance = new ParameterMap();
        $s = isset($args['s']) ? $args['s'] : null;
        
        // $paramInstance->add(GetRecordsParam::approved(), "true");
        
        // $paramInstance->add(GetRecordsParam::converted(), "1234");
        
        // $paramInstance->add(GetRecordsParam::cvid(), "34770610089005");
        
        $ids = array('id', 'Deal_Name');
        
        foreach($ids as $id)
        {
            $paramInstance->add(GetRecordsParam::fields(), $id);
        }

        if($s){

            $paramInstance->add(SearchRecordsParam::criteria(), "Deal_Name:in:{$s}");
        }
        
        // $paramInstance->add(GetRecordsParam::uid(), "34770615181008");
        
        // $fieldNames = array("Last_Name", "City");
        
        // foreach($fieldNames as $fieldName)
        // {
            // $paramInstance->add(GetRecordsParam::fields(), "id");
        // }
        
        // $paramInstance->add(GetRecordsParam::sortBy(), "Email");
        
        // $paramInstance->add(GetRecordsParam::sortOrder(), "desc");
        
        $paramInstance->add(GetRecordsParam::page(), $args['page']);
        
        $paramInstance->add(GetRecordsParam::perPage(), $args['per_page']);

        //$startdatetime = date_create("2020-06-27T15:10:00+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        
        // $paramInstance->add(GetRecordsParam::startDateTime(), $startdatetime);
        
        // $enddatetime = date_create("2020-06-29T15:10:00+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        
        // $paramInstance->add(GetRecordsParam::endDateTime(), $enddatetime);
        
        // $paramInstance->add(GetRecordsParam::territoryId(), "34770613051357");
        
        // $paramInstance->add(GetRecordsParam::includeChild(), true);
        
        $headerInstance = new HeaderMap();
        
        // $datetime = date_create("2021-02-26T15:28:34+05:30")->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        
        // $headerInstance->add(GetRecordsHeader::IfModifiedSince(), $datetime);
        
        //$headerInstance->add(GetRecordHeader::XEXTERNAL(), "Potentials.External");

        try{

            //Call getRecords method
            if(!isset($args['s'])){
                $response = $recordOperations->getRecords(self::$module_name, $paramInstance, $headerInstance);
            }else{
                $response = $recordOperations->searchRecords(self::$module_name, $paramInstance, $headerInstance);
            }
        }catch(SDKException $e){
            echo var_dump($e->getDetails());
            die;
        }
        
        if($response != null)
        {
            //Get the status code from response
            //echo("Status code " . $response->getStatusCode() . "\n");

            if(in_array($response->getStatusCode(), array(204, 304)))
            {
                echo($response->getStatusCode() == 204? "No Content\n" : "Not Modified\n");

                return;
            }
            
            if($response->isExpected())
            {
                //Get the object from response
                $responseHandler = $response->getObject();
                    
                if($responseHandler instanceof ResponseWrapper)
                {
                    //Get the received ResponseWrapper instance
                    $responseWrapper = $responseHandler;
                    
                    //Get the obtained Record instances
                    $records = $responseWrapper->getData();

                    return $responseWrapper;

                    $recordClass = 'com\zoho\crm\api\record\Record';

                    hwzi_get_template('admin/pages/weddings', ['records' => $records]);

                    //Get the Object obtained Info instance
                    $info = $responseWrapper->getInfo();
                    
                    //Check if info is not null
                    if($info != null)
                    {
                        if($info->getPerPage() != null)
                        {
                            //Get the PerPage of the Info
                            echo("Record Info PerPage: " . $info->getPerPage() . "\n");
                        }
                        
                        if($info->getCount() != null)
                        {
                            //Get the Count of the Info
                            echo("Record Info Count: " . $info->getCount() . "\n");
                        }
    
                        if($info->getPage() != null)
                        {
                            //Get the Page of the Info
                            echo("Record Info Page: " . $info->getPage() . "\n");
                        }
                        
                        if($info->getMoreRecords() != null)
                        {
                            //Get the MoreRecords of the Info
                            echo("Record Info MoreRecords: " . $info->getMoreRecords() . "\n");
                        }
                    }
                }
                //Check if the request returned an exception
                else if($responseHandler instanceof APIException)
                {
                    //Get the received APIException instance
                    $exception = $responseHandler;
                    
                    //Get the Status
                    echo("Status: " . $exception->getStatus()->getValue() . "\n");
                    
                    //Get the Code
                    echo("Code: " . $exception->getCode()->getValue() . "\n");
    
                    if($exception->getDetails() != null)
                    {
                        echo("Details: " );
                        
                        //Get the details map
                        foreach($exception->getDetails() as $key => $value)
                        {
                            //Get each value in the map
                            echo($key . " : " . $value . "\n");
                        }
                    }
                    //Get the Message
                    echo("Message: " . $exception->getMessage()->getValue() . "\n");
                }
            }
            
            return false;
        }
    }

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
        //$recordId = "347002";
        $recordOperations = new RecordOperations();
        $paramInstance = new ParameterMap();
        // $paramInstance->add(GetRecordParam::approved(), "false");
        // $paramInstance->add(GetRecordParam::converted(), "false");
        $fieldNames = [
            "id", 
            "Deal_Name", 
            "Transfer_Cost", 
            "Wedding_Page_URL",
            "Contact_Name",
            "Spouse_Name",
            "Wedding_Date",
            "Cut_Off_Date",
            'st_Deposit_Due_Date',
            'nd_Deposit_Due_Date',
            'rd_Deposit_Due_Date',
            'st_Deposit_Amount',
            'nd_Deposit_Amount',
            'rd_Deposit_Amount',
            'th_Deposit_Due_Date',
            'nd_Deposit_Type',
            'Period',
            'Period_2',
            'Period_3',
            'Period_4',
            'Period_5',
            'Penalty_Date_Range_1',
            'Penalty_Date_Range_2',
            'Penalty_Date_Range_3',
            'Penalty_Date_Range_4',
            'Penalty_Date_Range_5',
        ];
        
        if(isset($args['fields'])){

            $fieldNames = array_merge($fieldNames, $args['fields']);
        }
        
        foreach($fieldNames as $fieldName)
        {
        	$paramInstance->add(GetRecordParam::fields(), $fieldName);
        }

        $headerInstance = new HeaderMap();

        //Call getRecord method that takes paramInstance, moduleAPIName and recordID as parameter
        $response = $recordOperations->getRecord($recordId, self::$module_name, $paramInstance, $headerInstance);
        if ($response != null) {
            
            if (in_array($response->getStatusCode(), array(204, 304))) {
                
                return false;
            }
            if ($response->isExpected()) {
                $responseHandler = $response->getObject();
                if ($responseHandler instanceof ResponseWrapper) {
                    $responseWrapper = $responseHandler;
                    $records = $responseWrapper->getData();
                    return $records[0];
                    
                }
                else if ($responseHandler instanceof APIException) {
                    return $responseHandler;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * return a list of payments by related modules
     *
     * @param string $recordId
     * @param string $moduleAPIName
     * @param array $args
     * @return array
     */
	public static function getRelatedRecords(string $recordId, string $relatedModuleAPIName, $args = [])
	{

        $args = wp_parse_args($args,[
            'page' => 1,
            'per_page' => 200,
            'fields' => [ 'id', 'Name' ]
        ]);

	    $relatedRecordsOperations = new RelatedRecordsOperations( $relatedModuleAPIName, self::$module_name, null );
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
                return [];
			}
			if($response->isExpected())
			{
				$responseHandler = $response->getObject();
                
				if($responseHandler instanceof RelatedrecordsResponseWrapper)
				{
					$responseWrapper = $responseHandler;
					$records = $responseWrapper->getData();

                    return $records;
				}
				else if($responseHandler instanceof RelatedrecordsAPIException)
				{
                    // do something with the error but no return
				}
			}
		}

        return [];
	}
}