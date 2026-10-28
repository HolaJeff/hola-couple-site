<?php

namespace HW\Fields;

use HW\Objects\HWObject;

abstract class AbstractField extends HWObject{
    
    protected $name;
    
    protected $id;
    
    protected $value;
    
    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct( $args ){
        
        if( isset($args['name']) ){
            
            $this->name = $args['name'];
        }
        
        if( isset($args['id']) ){
            
            $this->id = $args['id'];
        }else{
            
            $this->id = $this->name . '_field';
        }
        
        if( isset($args['value']) ){
            
            $this->value = $args['value'];
        }
    }
    
    /**
     * Abstract method to render field
     *
     * @return void
     */
    abstract function render();

    /**
     * setter for value
     *
     * @param [type] $value
     * @return void
     */
    public function set_value($value){

        $this->value = $value;
    }
}