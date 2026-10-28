<?php

namespace HW\CLI;

use HW\Email\EmailSender;
use HW\Email\Templates\CanceledTransaction;
use HW\Email\Templates\PendingTransaction;

class EmailTests{

    /**
     * test migrating single site by id
     * 
     * ## OPTIONS
     *
     * --site=<site>
     * : The id of site
     * 
     * --transaction=<transaction>
     * : The id of the transaction
     * 
     * --template=<template>
     * : The id of the email
     *
     * @return void
     */
    public function test_email( $args, $assoc_args ){

        // check for email id
        if(!isset($assoc_args['site']) || empty($assoc_args['site'])){

            $assoc_args['site'] = 1;
        }

        $site_id = $assoc_args['site'];
        $email_template =  $assoc_args['template'];

        if(!isset($assoc_args['transaction']) || !isset($assoc_args['template']) ){

            \WP_CLI::error('Transaction ID and template are mandatory arguments');
            return;
        }

        $emails_map = [
            'pending' => [ $this, 'test_pending_transaction' ],
            'canceled' => [ $this, 'test_canceled_transaction' ]
        ];
        
        // validate id
        if(!isset($emails_map[$email_template])){

            \WP_CLI::error(__('Email template not found'));
            return;
        }

        switch_to_blog($site_id);

        call_user_func( $emails_map[$email_template], $assoc_args['transaction'] );

        restore_current_blog();
    }

    /**
     * test for PoS pending transaction
     *
     * @return void
     */
    protected function test_pending_transaction( $transaction_id ){

        $transaction = hw_get_transaction( $transaction_id );

        if(!$transaction->exists()){

            \WP_CLI::error('No transactions found');
        }

        $emailTemplate = new PendingTransaction( $transaction );
        $sender = new EmailSender();

        // expect mail response true or false
        if(!$sender->send($emailTemplate)){

            \WP_CLI::error(__('Email not sent'));
        }

         \WP_CLI::success(__('Email sent'));
    }

     /**
     * test for PoS canceled transaction
     *
     * @return void
     */
    protected function test_canceled_transaction( $transaction_id ){

        $transaction = hw_get_transaction( $transaction_id );

        if(!$transaction->exists()){

            \WP_CLI::error('No transactions found');
        }

        $emailTemplate = new CanceledTransaction( $transaction );
        $sender = new EmailSender();

        // expect mail response true or false
        if(!$sender->send($emailTemplate)){

            \WP_CLI::error(__('Email not sent'));
        }

         \WP_CLI::success(__('Email sent'));
    }
}