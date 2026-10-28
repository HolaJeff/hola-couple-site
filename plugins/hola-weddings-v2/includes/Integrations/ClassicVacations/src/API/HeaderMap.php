<?php
namespace HW\Integrations\ClassicVacations\API;

use HW\Integrations\ClassicVacations\API\Constants;

/**
 * This class represents the HTTP header name and value.
 */
class HeaderMap
{
    private $headerMap = array();

    /**
     * This is a getter method to get header map.
     * @return array An array representing the API request headers.
     */
    public function getHeaderMap()
    {
        return $this->headerMap;
    }

    /**
     * This is a setter method to set header map.
     * @param array An array representing the API request headers.
     */
    public function setHeaderMap(array $headerMap)
    {
        $this->headerMap = $headerMap;
    }

    /**
     * This method is to add header name and value.
     * @param Header $header A Header class instance.
     * @param object $value A object containing the header value.
     */
    public function add(Header $header, $value)
    {
        if($header === null)
        {
            throw new \Exception(Constants::HEADER_NULL_ERROR . ' ' . Constants::HEADER_INSTANCE_NULL_ERROR);
        }

        $headerName = $header->getName();

        if($headerName === null)
        {
            throw new \Exception(Constants::HEADER_NAME_NULL_ERROR . ' ' . Constants::HEADER_NAME_NULL_ERROR_MESSAGE);
        }

        if($value === null)
        {
            throw new \Exception(Constants::HEADER_NULL_ERROR . ' ' . $headerName.Constants::NULL_VALUE_ERROR_MESSAGE);
        }

        if (array_key_exists($headerName, $this->headerMap) && isset($this->headerMap[$headerName]))
        {
            $headerValue = $this->headerMap[$headerName];

            $headerValue = $headerValue . "," . $value;

            $this->headerMap[$headerName] = $headerValue;
        }
        else
        {
            $this->headerMap[$headerName] = $value;
        }
    }
}