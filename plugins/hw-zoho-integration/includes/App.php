<?php

namespace HWZoho;

use HW\Factories\LoggerFactory;
use HW\Session;
use HWZoho\API\Auth\OAuth;
use HWZoho\REST\Manager;
use HWZoho\AsyncRequests\UpdateRecord;
use HWZoho\CLI\ImportPayments;
use HWZoho\CLI\TestAsyncRequest;
use HWZoho\Utils\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class App{

    use Traits\Singleton;

    public $session;

    protected $logger;

    /**
     * Init hooks
     *
     * @return void
     */
    public function __construct(){

        // create new website logger
        $this->logger = ( LoggerFactory::makeWebsiteLogger('log', 'log', HW_ZOHO_PLUGIN_PATH . '/logs/') )->setLevel('debug');

        // init api
        OAuth::initialize();

        Frontend::getInstance();
        Manager::getInstance();

        // Init async requests
        UpdateRecord::getInstance();

        if(is_admin()){
            
            Admin\App::getInstance();
        }

        // CLI
        if(class_exists( 'WP_CLI' )){
            
            $this->register_cli_commands();
        }
    }

    /**
     * return logger
     *
     * @return Logger
     */
    public function getLogger(){

        return $this->logger;
    }

    /**
     * register CLI commands
     *
     * @return void
     */
    protected function register_cli_commands(){

        \WP_CLI::add_command( 'hwzi-import-payments-all-sites', [ ImportPayments::class, 'importPaymentsForAllSites' ] );
        \WP_CLI::add_command( 'hwzi-import-payments-single-site', [ ImportPayments::class, 'importPaymentsForSingleSite' ] );

        // Invoice Async Request testing
        // usage example: hwzi invoice-async-request-test --site=1 --invoice=1234 --fields=hw_invoice_status,hw_amount
        \WP_CLI::add_command( 'hwzi invoice-async-request-test', [ TestAsyncRequest::class, 'test_invoice' ] );

    }
}