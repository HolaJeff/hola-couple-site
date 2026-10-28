<?php

namespace HW\API\Models;

use HW\Objects\HWObject;

abstract class AbstractModel extends HWObject{

    protected $id;

    /**
     * constructor
     *
     * @param array $properties
     */
    public function __construct($properties = null)
    {
        if(is_int($properties)){
            $properties = $this->getFromAPI($properties);
        }

        parent::__construct($properties);
    }

    /**
     * implement the function to get a model from API and pass back the data
     *
     * @param int $id
     * @return array
     */
    abstract protected function getFromAPI($id);

    /**
     * return the model id
     *
     * @return int
     */
    public function getID(){
        return $this->id;
    }
}