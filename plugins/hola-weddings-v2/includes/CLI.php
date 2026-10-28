<?php

namespace HW;

use HW\CLI\EmailTests;
use HW\CLI\InvoiceTests;
use HW\CLI\RoomBlocks;
use HW\CLI\Settings;
use HW\CLI\Transactions;
use HW\CLI\UpdateHomeSections;
use HW\Traits\Singleton;

class CLI{

    use Singleton;

    public function __construct(){
        
        // Room blocks
        \WP_CLI::add_command( 'room-blocks migrate-all', [ RoomBlocks::class, 'migrateAll' ] );
        \WP_CLI::add_command( 'room-blocks delete-all', [ RoomBlocks::class, 'deleteAll' ] );
        \WP_CLI::add_command( 'room-blocks migrate-site', [ RoomBlocks::class, 'migrate_single_site' ] );

        // Transactions
        \WP_CLI::add_command( 'hw update-website-pending-transactions', [ Transactions::class, 'update_website_pending_transactions' ] );
        \WP_CLI::add_command( 'hw update-pending-transactions', [ Transactions::class, 'update_pending_transactions' ] );

        // others
        \WP_CLI::add_command( 'update-transfer-fields', [ Settings::class, 'updateTransferFields' ] );
        \WP_CLI::add_command( 'update-homepage-section-single', [ UpdateHomeSections::class, 'update_site' ] );
        \WP_CLI::add_command( 'update-homepage-section', [ UpdateHomeSections::class, 'update' ] );

        // email testing
        \WP_CLI::add_command( 'hw test-email', [ EmailTests::class, 'test_email' ] );

        // invoice testing
        // usage example: hw test-invoice change-status --site=1 --invoice=1234 --status=paid
        \WP_CLI::add_command( 'hw test-invoice change-status', [ InvoiceTests::class, 'test_status_change' ] );
    }
}