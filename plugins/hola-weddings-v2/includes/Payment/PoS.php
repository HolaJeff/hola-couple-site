<?php

namespace HW\Payment;

use HW\Factories\LoggerFactory;
use HW\Traits\Singleton;

class PoS{

    use Singleton;

    protected $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::makeWebsiteLogger('pos_log');
        $this->logger->setLevel(hw_get_pos_debug_level());
    }

    /**
     * return the logger
     *
     * @return HW\Utils\Logger
     */
    public function getLogger(){

        return $this->logger;
    }
}