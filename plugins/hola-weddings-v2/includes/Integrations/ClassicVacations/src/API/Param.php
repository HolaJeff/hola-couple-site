<?php
namespace HW\Integrations\ClassicVacations\API;

/**
 * This class represents the HTTP parameter.
 */
class Param
{
    private $name = null;

    /**
     * static methos to create a new param
     *
     * @param string $name
     * @param mixed $value
     * @return Param
     */
    public static function create($name){

        return new self($name);
    }

   /**
    * Creates an Param class instance with the specified parameter name.
    * @param string $name A string containing the parameter name.
    * @param string $className A string containing the parameter class name.
    */
    function __construct($name)
    {
        $this->name = $name;
    }

   /**
    * This is a getter method to get parameter name.
    * @return string A string representing the parameter name.
    */
   public function getName()
   {
       return $this->name;
   }
}