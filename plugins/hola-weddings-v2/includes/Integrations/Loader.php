<?php

namespace HW\Integrations;

use HW\Integrations\ClassicVacations\App as ClassicApp;
use HW\Traits\Singleton;

class Loader{

    use Singleton;

    public function __construct()
    {
        
        ClassicApp::getInstance();
    }
}