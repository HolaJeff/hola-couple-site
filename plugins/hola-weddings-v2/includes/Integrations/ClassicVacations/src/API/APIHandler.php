<?php

namespace HW\Integrations\ClassicVacations\API;

use HW\Integrations\ClassicVacations\API\HeaderMap;

use HW\Integrations\ClassicVacations\API\Initializer;

use HW\Integrations\ClassicVacations\API\ParameterMap;

use HW\Integrations\ClassicVacations\API\Constants;

use HW\Integrations\ClassicVacations\API\APIResponse;
use HW\Integrations\ClassicVacations\App;

/**
 * This class is to process the API request and its response.
 * Construct the objects that are to be sent as parameters or in the request body with the API.
 * The Request parameter, header and body objects are constructed here.
 * Process the response JSON and converts it to relevant objects in the library.
 */
class APIHandler
{
    private $apiPath;

    private $param;

    private $header;

    private $httpMethod;

    private $contentType;

    private $request;

    private $requestBody;

    public function __construct()
    {
        $this->header = new HeaderMap();

        $this->param = new ParameterMap();
    }

    /**
     * This is a setter method to set an API request content type.
     * @param string $contentType A string containing the API request content type.
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * This is a setter method to set the API request URL.
     * @param string $apiPath A string containing the API request URL.
     */
    public function setAPIPath($apiPath)
    {
        $this->apiPath = $apiPath;
    }

    /**
     * This method is to add an API request parameter.
     * @param string $param A Param containing the API request parameter .
     * @param object $paramValue A object containing the API request parameter value.
     */
    public function addParam($paramInstane, $paramValue)
    {
        if ($paramValue === null)
        {
            return;
        }

        if ($this->param === null)
        {
            $this->param = new ParameterMap();
        }

        $this->param->add($paramInstane, $paramValue);
    }

    /**
     * This method to add an API request header.
     * @param string $header A Header containing the API request header .
     * @param string $headerValue A object containing the API request header value.
     */
    public function addHeader($headerInstane, $headerValue)
    {
        if ($headerValue === null)
        {
            return;
        }

        if ($this->header === null)
        {
            $this->header = new HeaderMap();
        }

        $this->header->add($headerInstane, $headerValue);
    }

    /**
     * This is a setter method to set the API request parameter map.
     * @param ParameterMap $param A ParameterMap class instance containing the API request parameter.
     */
    public function setParam($param)
    {
        if ($param === null)
        {
            return;
        }

        if($this->param->getParameterMap() !== null && count($this->param->getParameterMap()) > 0)
        {
            $this->param->setParameterMap(array_merge($this->param->getParameterMap(), $param->getParameterMap()));
        }
        else
        {
            $this->param = $param;
        }
    }

    /**
     * This is a setter method to set the API request header map.
     * @param HeaderMap $header A HeaderMap class instance containing the API request header.
     */
    public function setHeader($header)
    {
        if ($header === null)
        {
            return;
        }

        if($this->header->getHeaderMap() !== null && count($this->header->getHeaderMap()) > 0)
        {
            $this->header->setHeaderMap(array_merge($this->header->getHeaderMap(), $header->getHeaderMap()));
        }
        else
        {
            $this->header = $header;
        }
    }

    /**
     * This is a setter method to set the API request body object.
     * @param object $request A object containing the API request body object.
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * This is a setter method to set the HTTP API request method.
     * @param string $httpMethod A string containing the HTTP API request method.
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
    }

    /**
     * This is a setter method to set the API request body.
     * @param object $requestBody A Object containing the API request body.
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
    }

    /**
     * This method is used in constructing API request and response details. To make the Zoho CRM API calls.
     * @param string $className A string containing the method return type.
     * @param string $encodeType A String containing the expected API response content type.
     * @return \com\zoho\crm\api\util\APIResponse A APIResponse representing the Zoho CRM API response instance or null.
     */
    public function apiCall()
    {

        $logger = App::getInstance()->getLogger();

        if(Initializer::getInitializer() === null)
        {
            throw new \Exception(Constants::SDK_UNINITIALIZATION_ERROR . ': ' . Constants::SDK_UNINITIALIZATION_MESSAGE);
        }

        $connector = new HTTPConnector();

        try
        {
            $this->setAPIUrl($connector);
        }
        catch (\Exception $e)
        {

            $logger->severeError(Constants::SET_API_URL_EXCEPTION . ' ' . $e->getMessage(), self::class . '::' . __METHOD__ );

            throw $e;
        }

        $connector->setRequestMethod($this->httpMethod);

        $connector->setContentType($this->contentType);

        if ($this->header != null && count($this->header->getHeaderMap()) > 0)
        {
            $connector->setHeaders($this->header->getHeaderMap());
        }

        if ($this->param != null && count($this->param->getParameterMap()) > 0)
        {
            $connector->setParams($this->param->getParameterMap());
        }

        try
        {
            Initializer::getInitializer()->getToken()->authenticate($connector);
        }
        catch (\Exception $e)
        {
            $logger->error(Constants::AUTHENTICATION_EXCEPTION . ' ' . $e->getMessage(), self::class . '::' . __METHOD__ );

            throw $e;
        }

        // if is set, add the body
        if($this->requestBody != null){

            $body = $this->requestBody;

            switch($this->contentType){
                case 'application/json':
                    $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
                    break;
            }

            $connector->setRequestBody($body);
        }

        try
        {
            $response = $connector->fireRequest();

            $statusCode = $response[Constants::HTTP_CODE];

            $headerMap = $response[Constants::HEADERS];

            $returnObject  = null;

            if(array_key_exists(Constants::CONTENT_TYPE, $headerMap) && !array_key_exists(Constants::ERROR, $response))
            {
                $responseContentType = $headerMap[Constants::CONTENT_TYPE];

                if (strpos($responseContentType, ';') != false)
                {
                    $splitArray = explode(';', $responseContentType);

                    $responseContentType = $splitArray[0];
                }

                $returnObject = $this->getConverterBodyContent(strtolower($responseContentType), $response[Constants::BODY]);
            }
            else
            {
                if(array_key_exists(Constants::ERROR, $response))
                {
                    $logger->error(Constants::API_ERROR_RESPONSE . ' ' . $response[Constants::ERROR], self::class . '::' . __METHOD__ );
                }
                else
                {
                    $logger->error(Constants::API_ERROR_RESPONSE . json_encode($response, JSON_UNESCAPED_UNICODE), self::class . '::' . __METHOD__ );
                }
            }

            return new APIResponse($headerMap, $statusCode, $returnObject);
        }
        catch (\Exception $e)
        {
            $logger->error(Constants::API_CALL_EXCEPTION . $e->getMessage(), self::class . '::' . __METHOD__ );

            throw $e;
        }

        return null;
    }

    private function setAPIUrl(HTTPConnector $connector)
    {
        $APIPath = "";

        $APIPath = $APIPath . (Initializer::getInitializer()->getEnvironment()->getUrl());

        $APIPath = $APIPath . ($this->apiPath);
        
        $connector->setURL($APIPath);
    }

	public function getHttpMethod()
	{
		return $this->httpMethod;
	}

    public function getAPIPath()
	{
		return $this->apiPath;
	}

    /**
     * This method is used to get a Converter class instance.
     * @param string $encodeType A string containing the API response content type.
     * @return NULL|\com\zoho\crm\api\util\Converter A Converter class instance.
     */
    public function getConverterBodyContent($encodeType, $content)
    {
        switch ($encodeType)
        {
            case "application/json":
            case "text/plain":
            case "application/ld+json":
                return json_decode($content, true);
            case "application/jwt":
            default:
                return $content;
        }
    }
}