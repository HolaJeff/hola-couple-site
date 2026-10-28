<?php
namespace HW\Integrations\ClassicVacations\API;

/**
 * This class is the common API response object.
 */
class APIResponse
{
    private $headers;

    private $statusCode;

    private $body;

    /**
     * Creates an APIResponse class instance with the specified parameters.
     * @param array $headers A array containing the API response headers.
     * @param string $status_code A string containing the API response HTTP status code.
     * @param body $object A object containing the API response POJO class instance.
     */
    public function __construct($headers, $statusCode, $body)
    {
        $this->headers = $headers;

        $this->statusCode = $statusCode;

        $this->body = $body;
    }

    /**
     * This is a getter method to get API response headers.
     * @return array A array representing the API response headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * This is a getter method to get the API response HTTP status code.
     * @return string A string representing the API response HTTP status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * This method to get an API response POJO class instance.
     * @return object A POJO class instance.
     */
    public function getBody()
    {
        return $this->body;
    }

}