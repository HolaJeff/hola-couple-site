<?php

namespace HWZoho\CLI;

use HWZoho\API\CRM\Modules\Payments;
use com\zoho\crm\api\relatedrecords\APIException;
use HW\Models\Invoice;
use HWZoho\API\CRM\DataMappers\Invoice as DataMappersInvoice;
use WP_Site;
use WP_CLI;

class ImportPayments{

    /**
     * Import payments for all websites
     * 
     * ## OPTIONS
     * 
     * [--from=<from_id>]
     * : Whether or not to greet the person with success or error.
     * ---
     * default: 0
     * ---
     *
     * @return void
     */
    public static function importPaymentsForAllSites( $args = [], $assoc_args ){

        $sites = get_sites([
            'number' => 9999,
            'offset' => intval($assoc_args['from'])
        ]);

        foreach($sites as $site){

            if(self::processSite($site) == false){
                continue;
            }
        }
    }

    /**
     * import payments for single site
     * 
     * ## OPTIONS
     *
     * <id>
     * : The id of the site
     *
     * @return void
     */
    public static function importPaymentsForSingleSite($args){

        if(!isset($args[0]) || empty($args[0])){

            WP_CLI::error('Provide a valid site id');
            return;
        }
        
        $site = get_site($args[0]);

        if(!$site instanceof \WP_site){

            WP_CLI::error(sprintf('Website with id %d doesn\'t exist', $args[0]));
            return;
        }

        self::processSite($site);;
    }

    /**
     * import payments for given website
     *
     * @param WP_Site $site
     * @return void
     */
    public static function processSite(WP_Site $site){

        $blog_id = $site->blog_id;
        switch_to_blog($blog_id);

        WP_CLI::log(sprintf('Processing site %s - #%d', $site->domain, $blog_id));

        $query = new \WP_Query([
            'post_type' => 'quote',
            'posts_per_page' => -1,
            'post_status' => [ 'publish', 'draft' ]
        ]);

        if(!$query->have_posts()){

            WP_CLI::log('No quotes found');
            return false;
        }

        WP_CLI::log(sprintf('%d quotes found', $query->found_posts));

        foreach($query->posts as $quote){

            WP_CLI::log(sprintf('Processing quote %d', $quote->ID));

            $reservation_zoho_id = hwzi_get_quote_zoho_reservation_id($quote->ID);

            if(!is_numeric($reservation_zoho_id)){

                WP_CLI::warning(sprintf('Invalid reservation id for quote %d ( id: %s )', $quote->ID, $reservation_zoho_id), false);
                continue;
            }

            // check if the quote has a zoho reservation id set
            if(empty($reservation_zoho_id)){

                WP_CLI::warning(sprintf('No zoho reservation id set for quote %d', $quote->ID));
                continue;
            }

            try{

                $records = Payments::getBy($reservation_zoho_id, 'Reservations');
            }catch(\Exception $e){
        
                WP_CLI::error('API Error: ' . $e->getMessage(), false);
                continue;
            }

            if($records instanceof APIException){

                WP_CLI::error( sprintf('API Exception - %s.', $records->getMessage()->getValue()), false);
                continue;
            }
        
            if(empty($records)){
        
                WP_CLI::log( sprintf('No payments found for reservation %s on quote %s.', $reservation_zoho_id, $quote->ID));
                continue;
            }
        
            foreach($records as $record){
        
                $invoice = hwzi_get_invoice_by_zoho_id($record->getId());

                // checks if an invoice already exists with the same zoho payment id
                if($invoice instanceof \WP_Post){

                    WP_CLI::log( sprintf('Payment %s already exists', $record->getId()));

                    // check if the wp id field in zoho has already a value, if not set the matching invoice id
                    if($record->getKeyValue('WPInvoiceID') == null){

                        // store the newly created wp id to zoho payment record
                        Payments::updateRecord($record->getId(), ['WPInvoiceID' => $invoice->ID]);
                        WP_CLI::log( sprintf('WPInvoiceID zoho field updated for payment %s', $record->getId()));
                    }

                    continue;
                }

                // create a new model
                $model = Invoice::create($record->getName());
                $dataMapper = new DataMappersInvoice();

                // associate to the current quote
                $model->set('hw_reservation_id', $quote->ID);

                // map the fields into the database
                $dataMapper->map($record, $model);

                // store the newly created wp id to zoho payment record
                Payments::updateRecord($record->getId(), ['WPInvoiceID' => $model->getID()]);

                WP_CLI::success( sprintf('Payment record %s mapped to %s invoice', $record->getId(), $model->getID()));
            }
        }

        restore_current_blog();
    }
}