<?php
namespace HW\Integrations\ClassicVacations\API;

use HW\Integrations\ClassicVacations\API\Constants;

/**
 * This class representing the HTTP parameter name and value.
 */
class ParameterMap
{
    private $parameterMap = array();

    /**
     * This is a getter method to get parameter map.
     * @return array An array representing the API request parameters.
     */
    public function getParameterMap()
    {
        return $this->parameterMap;
    }

    /**
     * This is a setter method to set parameter map.
     * @param array An array representing the API request parameters.
     */
    public function setParameterMap(array $parameterMap)
    {
        $this->parameterMap = $parameterMap;
    }

    /**
     * This method to add parameter name and value.
     * @param Param $param A Param class instance.
     * @param object $value A object containing the parameter value.
     */
    public function add($paramName, $value)
    {

        if($paramName === null)
        {
            throw new \Exception(Constants::PARAM_NAME_NULL_ERROR . ' ' . Constants::PARAM_NAME_NULL_ERROR_MESSAGE);
        }

        if($value === null)
        {
            throw new \Exception(Constants::PARAMETER_NULL_ERROR . ' ' . $paramName.Constants::NULL_VALUE_ERROR_MESSAGE);
        }

        if (array_key_exists($paramName, $this->parameterMap) && isset($this->parameterMap[$paramName]))
        {
            $paramValue = $this->parameterMap[$paramName];

            $paramValue = $paramValue . "," . $value;

            $this->parameterMap[$paramName] = $paramValue;
        }
        else
        {
            $this->parameterMap[$paramName] = $value;
        }
    }
}