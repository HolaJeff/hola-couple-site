<?php

namespace HW\Traits;

Trait Singleton{
    
    protected static $instance;
    
    /**
     * return an instance of the class
     *
     * @return static
     */
    public static function getInstance(){
        
        if(!isset(self::$instance)){
            
            self::$instance = new static(); 
        }
        
        return self::$instance;
    }
}