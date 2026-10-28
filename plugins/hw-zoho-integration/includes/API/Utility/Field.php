<?php

namespace HWZoho\API\Utility;

class Field{

    protected $fieldName;

    protected $value;

    /**
     * constructor
     *
     * @param string $fieldName
     */
    public function __construct($fieldName, $value){
        
        $this->fieldName = $fieldName;

        $this->value = $value;
    }

    /**
     * return the field name
     *
     * @return string
     */
    public function getFieldName(){

        return $this->fieldName;
    }

    /**
     * return field value
     *
     * @return mixed
     */
    public function getValue(){

        return $this->value;
    }
}