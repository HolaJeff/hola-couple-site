<?php

namespace HW\Integrations\ClassicVacations\CRM\Groups;

use HW\Integrations\ClassicVacations\API\ParameterMap;

use HW\Integrations\ClassicVacations\API\HeaderMap;

use HW\Integrations\ClassicVacations\API\APIHandler;

use HW\Integrations\ClassicVacations\API\Constants;

class GroupOperations{

    /**
	 * The method to get record
	 * @param string $id A string
	 * @param ParameterMap $paramInstance An instance of ParameterMap
	 * @param HeaderMap $headerInstance An instance of HeaderMap
	 * @return APIResponse An instance of APIResponse
	 */
	public function getRecord(string $id, ParameterMap $paramInstance=null, HeaderMap $headerInstance=null)
	{
		$handlerInstance=new APIHandler(); 
		$apiPath=""; 
		$apiPath=$apiPath.('/api/GetGroupOverView/');
		$apiPath=$apiPath.(strval($id)); 
		$handlerInstance->setAPIPath($apiPath); 
		$handlerInstance->setHttpMethod(Constants::REQUEST_METHOD_GET); 
		$handlerInstance->setParam($paramInstance); 
		$handlerInstance->setHeader($headerInstance); 
		return $handlerInstance->apiCall(); 
	}
}