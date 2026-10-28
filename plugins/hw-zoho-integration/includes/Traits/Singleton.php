<?php

namespace HWZoho\Traits;

Trait Singleton{
    
    protected static $instance;
    
    public static function getInstance(){
        
        if(!isset(self::$instance)){
            
            self::$instance = new static(); 
        }
        
        return self::$instance;
    }
}