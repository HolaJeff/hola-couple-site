<?php

namespace HWTheme\Traits;

Trait Singleton{
    
    protected static $instance;
    
    public static function getInstance(){
        
        if(!isset(self::$instance)){
            
            self::$instance = new static(); 
        }
        
        return self::$instance;
    }
    
    public function __construct(){
        
        $this->init();
    }
}