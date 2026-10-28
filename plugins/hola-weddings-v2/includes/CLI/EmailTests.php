<?php

namespace HW\CLI;

use HW\Email\EmailSender;
use HW\Email\Templates\CanceledTransaction;
use HW\Email\Templates\NewQuoteAdminNotification;
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

    /**
     * Render or re-send the new-quote admin notification for an existing quote.
     *
     * Lets the template be worked on without putting a booking through the form
     * every time. The quote is read back through the model exactly as the live
     * hook does, so what this prints is what the real notification contains.
     *
     * ## OPTIONS
     *
     * [--quote=<quote>]
     * : ID of the quote to render. Defaults to the most recent published quote.
     *
     * [--site=<site>]
     * : Blog id on the network. Default 1.
     *
     * [--to=<to>]
     * : Comma separated recipients to send to instead of the real inbox.
     *
     * [--dry-run]
     * : Render only, never send. This is the default unless --to or --yes is given.
     *
     * [--out=<out>]
     * : Write the rendered HTML to this file instead of printing it.
     *
     * ## EXAMPLES
     *
     *     # Print the message for the latest quote, send nothing
     *     wp hw test-quote-email
     *
     *     # Open one specific quote in a browser
     *     wp hw test-quote-email --quote=1234 --out=quote-email.html
     *
     *     # Actually deliver it, to yourself
     *     wp hw test-quote-email --quote=1234 --to=me@example.com
     *
     * @return void
     */
    public function test_quote_email( $args, $assoc_args ){

        $site_id = isset( $assoc_args['site'] ) && ! empty( $assoc_args['site'] ) ? intval( $assoc_args['site'] ) : 1;

        if ( is_multisite() ) {
            switch_to_blog( $site_id );
        }

        $quote_id = isset( $assoc_args['quote'] ) ? intval( $assoc_args['quote'] ) : $this->get_latest_quote_id();

        if ( ! $quote_id ) {

            $this->restore_blog();
            \WP_CLI::error( 'No quote found on this site. Pass one with --quote=<id>.' );
            return;
        }

        $quote = hw_get_quote( $quote_id );

        if ( ! $quote->exists() ) {

            $this->restore_blog();
            \WP_CLI::error( sprintf( 'Quote %d not found on site %d.', $quote_id, $site_id ) );
            return;
        }

        $template = new NewQuoteAdminNotification( $quote );
        $sender   = new EmailSender();

        // Recipient override. Registered as a filter rather than passed in, so
        // the template stays exactly the object the live hook builds.
        $override = [];

        if ( ! empty( $assoc_args['to'] ) ) {

            foreach ( explode( ',', $assoc_args['to'] ) as $email ) {

                $email = trim( $email );

                if ( ! is_email( $email ) ) {

                    $this->restore_blog();
                    \WP_CLI::error( sprintf( '"%s" is not a valid email address.', $email ) );
                    return;
                }

                $override[] = $email;
            }

            add_filter( 'hw_new_quote_admin_recipients', function() use ( $override ) {
                return $override;
            } );
        }

        // Sending is opt-in: without --to or --yes this only ever renders, so a
        // stray command cannot mail the real inbox about an old booking.
        $send = ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false )
            && ( ! empty( $override ) || \WP_CLI\Utils\get_flag_value( $assoc_args, 'yes', false ) );

        \WP_CLI::log( sprintf( 'Quote #%d - %s', $quote_id, $quote->getTitle() ) );
        \WP_CLI::log( sprintf( 'Subject: %s', $template->getSubject() ) );
        \WP_CLI::log( sprintf( 'Recipients: %s', implode( ', ', $template->getRecipients() ) ) );
        \WP_CLI::log( sprintf(
            'Insurance: %s (plan id %s, total %s)',
            $quote->hasInsurance() ? 'yes' : 'no',
            $quote->getInsurancePlanID() ?: '-',
            $quote->getInsuranceTotal()
        ) );

        $html = $sender->render( $template );

        if ( ! empty( $assoc_args['out'] ) ) {

            $path = $assoc_args['out'];

            if ( file_put_contents( $path, $html ) === false ) {

                $this->restore_blog();
                \WP_CLI::error( sprintf( 'Could not write to %s', $path ) );
                return;
            }

            \WP_CLI::success( sprintf( 'Rendered %d bytes to %s', strlen( $html ), $path ) );

        } elseif ( ! $send ) {

            \WP_CLI::log( '' );
            \WP_CLI::log( $html );
        }

        if ( ! $send ) {

            \WP_CLI::log( 'Nothing was sent. Add --to=<email> to deliver it, or --yes to use the real recipients.' );
            $this->restore_blog();
            return;
        }

        $result = $sender->send( $template );

        $this->restore_blog();

        if ( is_wp_error( $result ) ) {

            \WP_CLI::error( sprintf( 'Email not sent: %s', $result->get_error_message() ) );
            return;
        }

        if ( ! $result ) {

            \WP_CLI::error( 'Email not sent. Check the mailer configuration.' );
            return;
        }

        \WP_CLI::success( 'Email sent' );
    }

    /**
     * Most recently published quote on the current site.
     *
     * @return int
     */
    protected function get_latest_quote_id(){

        $posts = get_posts( [
            'post_type'      => 'quote',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ] );

        return $posts ? intval( $posts[0] ) : 0;
    }

    /**
     * Undo a switch_to_blog() that only happened on multisite.
     *
     * @return void
     */
    protected function restore_blog(){

        if ( is_multisite() ) {
            restore_current_blog();
        }
    }
}
