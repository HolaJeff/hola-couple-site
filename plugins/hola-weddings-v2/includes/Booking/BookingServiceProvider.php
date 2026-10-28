<?php

namespace HW\Booking;

use HW\Booking\Managers\PersonTypeManager;
use HW\Traits\Singleton;

class BookingServiceProvider{

    use Singleton;

    public function init(){

        PersonTypeManager::getInstance();
    }
}