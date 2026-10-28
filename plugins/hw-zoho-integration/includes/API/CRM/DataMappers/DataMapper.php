<?php

namespace HWZoho\API\CRM\DataMappers;

use com\zoho\crm\api\record\Record;
use HW\Models\DecoratorModel;

abstract class DataMapper implements DataMapperInterface{

    protected $attributesMap;

    /**
     * constructor
     */
    public function __construct(){

        $this->attributesMap = $this->getAttributesMap();
    }

    /**
     * get a list of automap attributes
     *
     * @return array
     */
    protected function getAttributesMap(){

        return [];
    }

    /**
     * return an attribute form the map
     *
     * @param string $id
     * @return mixed
     */
    protected function getAttribute( string $id ){
        
        return $this->attributesMap[$id] ?? false;
    }

    /**
     * process the mapping
     *
     * @param Record $record
     * @param DecoratorModel $model
     * @return void
     */
    public function map(Record $record, DecoratorModel $model){
        
        $keyValues = $record->getKeyValues();

        foreach($keyValues as $key => $value){
            
            // camel case the field key
            $method = hw_string_to_camel_case($key);

            // first check if there's a custom method for this field
            if(method_exists($this, $method)){

                // call the method
                call_user_func_array([$this, $method], [$value, $model, $record]);

            }elseif( $attribute = $this->getAttribute($key) ){ // if not, checks if there's an automap field

                // a closure was passed 
                if(is_array($attribute)){

                    list($meta_key, $callback) = $attribute;

                    if(is_callable($callback)){

                        $value = call_user_func($callback, $value);
                        $model->set($meta_key, $value);
                    }
                }elseif(is_string($attribute)){ // the meta key was passed, simply set the value without modifications
                    
                    $model->set($attribute, $value);
                }
            }

            // if no method or automap attribute is defined do nothing, the field was not mapped and we don't know how to handle it
        }

        // allow child classes to do more complicated manipulation
        $this->processRecord( $record, $model );
    }

    /**
     * allow child classes to do more complicated manipulation
     *
     * @param Record $record
     * @param DecoratorModel $model
     * @return void
     */
    protected function processRecord( Record $record, DecoratorModel &$model ){}
}