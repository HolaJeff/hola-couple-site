<?php

namespace HW\Integrations\ClassicVacations\CRM\Payment;

use HW\Integrations\ClassicVacations\API\ParameterMap;

use HW\Integrations\ClassicVacations\API\HeaderMap;

use HW\Integrations\ClassicVacations\API\APIHandler;

use HW\Integrations\ClassicVacations\API\Constants;

class PaymentOperations{

    /**
	 * The method to get record
	 * @param string $id A string
	 * @param ParameterMap $paramInstance An instance of ParameterMap
	 * @param HeaderMap $headerInstance An instance of HeaderMap
	 * @return APIResponse An instance of APIResponse
	 */
	public function getContext( ParameterMap $paramInstance=null, HeaderMap $headerInstance=null)
	{
		$handlerInstance=new APIHandler(); 
		$apiPath=""; 
		$apiPath=$apiPath.("/v2/payments/context/");
		$handlerInstance->setAPIPath($apiPath); 
		$handlerInstance->setHttpMethod(Constants::REQUEST_METHOD_POST); 
		$handlerInstance->setContentType('application/json');
		$handlerInstance->setParam($paramInstance); 
		$handlerInstance->setHeader($headerInstance); 
		return $handlerInstance->apiCall(); 
	}

	/**
	 * The method to make a payment
	 * @param string $id A string
	 * @param ParameterMap $paramInstance An instance of ParameterMap
	 * @param HeaderMap $headerInstance An instance of HeaderMap
	 * @return APIResponse An instance of APIResponse
	 */
	public function makePayment($body, ParameterMap $paramInstance=null, HeaderMap $headerInstance=null){
		
		$handlerInstance=new APIHandler(); 
		$apiPath=""; 
		$apiPath=$apiPath.("/v2/payments");
		$handlerInstance->setAPIPath($apiPath); 
		$handlerInstance->setHttpMethod(Constants::REQUEST_METHOD_POST); 
		$handlerInstance->setContentType('application/json');
		$handlerInstance->setRequestBody($body);
		$handlerInstance->setParam($paramInstance); 
		$handlerInstance->setHeader($headerInstance); 
		return $handlerInstance->apiCall(); 
	}

	/**
	 * The method to get a payment record
	 * 
	 * @param string $id A string
	 * @param ParameterMap $paramInstance An instance of ParameterMap
	 * @param HeaderMap $headerInstance An instance of HeaderMap
	 * @return APIResponse An instance of APIResponse
	 */
	public function getRecord($ref_id, ParameterMap $paramInstance=null, HeaderMap $headerInstance=null){
		
		$handlerInstance=new APIHandler(); 
		$apiPath=""; 
		$apiPath=$apiPath.("/v2/payments/{$ref_id}");
		$handlerInstance->setAPIPath($apiPath); 
		$handlerInstance->setHttpMethod(Constants::REQUEST_METHOD_GET); 
		$handlerInstance->setContentType('application/json');
		$handlerInstance->setParam($paramInstance); 
		$handlerInstance->setHeader($headerInstance); 
		return $handlerInstance->apiCall(); 
	}
}