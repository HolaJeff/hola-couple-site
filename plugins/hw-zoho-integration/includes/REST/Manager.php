<?php

namespace HWZoho\REST;

use HWZoho\REST\Routes\Records;
use HWZoho\REST\Routes\CoupleSite;
use HWZoho\Traits\Singleton;

class Manager{

    use Singleton;

    protected $routes = [
        Records::class,
        CoupleSite::class
    ];

    public function __construct()
    {
        $this->initRoutes();
    }

    protected function initRoutes(){

        foreach($this->routes as $class){
            new $class();
        }
    }
}