<?php

namespace HW\CLI;

use HW\Enums\TransactionStatus;
use HW\Models\Transaction;
use HW\Payment\PoS;

class Transactions{

    /**
     * triggers update transactions for all websites
     *
     * @return void
     */
    public function update_pending_transactions(){

        $sites = get_sites([
            'number' => 9999
        ]);

        foreach($sites as $site){

            $blog_id = $site->blog_id;
            switch_to_blog($blog_id);

            \WP_CLI::line(sprintf('Processing site %s', $site->domain));

            // fire this for whatever pyament integration wants to update their transaction.
            $this->process_pending_transactions();

            restore_current_blog();
        }

        \WP_CLI::success('Completed');
    }

    /**
     * review classic vacations pending transactions
     * 
     * ## OPTIONS
     *
     * <id>
     * : The id of the site
     *
     * @return bool|WP_Error
     */
    public function update_website_pending_transactions($args = []){

        if(!isset($args[0]) || empty($args[0])){
            \WP_CLI::error(__('Invalid id'));
            return;
        }

        switch_to_blog($args[0]);

        // fire this for whatever pyament integration wants to update their transaction.
        $this->process_pending_transactions();

        restore_current_blog();

        \WP_CLI::success('Completed');
    }

    /**
     * process pending transactions
     *
     * @return void
     */
    protected function process_pending_transactions(){

        $logger = ( new PoS() )->getLogger();
        $logger->info('Starting PoS cron job "Update pending transactions"');

        $transactions = hw_get_transactions([
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'hw_status',
                    'value' => 'pending'
                ]
            ]
        ]);
    
        // remove the cron job, no need to run again if there are no transactions to check
        if(empty($transactions)){
    
            $logger->info('No pending transactions found, ending cron job.');
            \WP_CLI::line('No pending transactions found, ending cron job.');
            return false;
        }
    
        // go through the transactions
        foreach($transactions as $model){

            $logger->info(sprintf('Processing transaction #%d', $model->getID()));

            $keep_status = false;
            /**
             * trigger action for the corresponding gateway to update the transaction
             */
            $keep_status = apply_filters( "hw_cron_job_{$model->getGateway()}_update_pending_transactions", $keep_status, $model, $logger );

            // if after 24 hours from its creation the transaction is still pending, or got stuck in processing, change the status to canceled
            $current_status = $model->getStatus();
            if($keep_status == false && in_array($current_status, [ TransactionStatus::PENDING->value, TransactionStatus::PROCESSING->value ])){
                
                $created_at = get_post_time('U', true, $model->getID());
                $age = time() - $created_at;

                if ( $age > DAY_IN_SECONDS ) {

                    // 24 h passed → cancel
                    $model->setStatus(TransactionStatus::CANCELED);

                    $logger->info(sprintf('Status updated to %s', 'Canceled'), self::class );
                    $model->addNote(sprintf('Status updated to %s', 'Canceled') , self::class );
                }
            }
        }
    }
}