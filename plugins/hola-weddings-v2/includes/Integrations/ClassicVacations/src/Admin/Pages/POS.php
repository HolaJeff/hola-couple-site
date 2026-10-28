<?php

namespace HW\Integrations\ClassicVacations\Admin\Pages;

use HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\JQueryValidate;
use HW\Helpers\Assets\Noty;
use HW\Integrations\ClassicVacations\App;
use HW\Integrations\ClassicVacations\Controllers\POS as POSController;
use HW\Traits\Singleton;

class POS{

    use Singleton;
    
    public function __construct()
    {
        add_action( 'admin_menu', [ $this, 'register_pages' ] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 10);

        add_action( 'wp_ajax_hw_cv_pos_get_invoices_by_quote', [$this, 'get_invoices_by_quote'] );
        add_action( 'wp_ajax_hw_cv_pos_get_invoice_checkout', [$this, 'get_invoice_checkout'] );
        add_action( 'wp_ajax_hw_cv_pos_process_payment', [$this, 'hw_cv_pos_process_payment'] );
    }

    /**
     * enqueue scripts
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts($hook){
        
        if($hook == 'toplevel_page_classic-vacations'){
            Noty::import();
            JQueryValidate::import();
            BlockUI::import(['hw-utils']);
            wp_enqueue_script( 'cv-pos', CLASSICV_URL . 'assets/dist/js/pos.js', [ 'jquery', 'hw-utils', 'blockui', 'jquery-validate' ] );
        }
    }

    /**
     * register pages in admin menu
     *
     * @return void
     */
    public function register_pages(){
        
        /** POS */
        $settings_hook = add_menu_page(
            __( 'Classic Vacations POS', 'hola-weddings' ), 
            __( 'Classic Vacations Point of Sale', 'hola-weddings' ), 
            'manage_options', 
            'classic-vacations', 
            [ $this, 'display_pos' ],
            CLASSICV_URL . '/assets/images/classic.png',
            35
        );
		
        /** POS 
        $hook = add_submenu_page( 
            'classic-vacations',
            __( 'POS', 'hola-weddings' ),
            __( 'Classic Vacations Point of Sale', 'hola-weddings' ),
            'manage_options', 
            'classic-vacations-pos'
        );*/
        
        //add_action( 'load-' . $settings_hook, [ $this->settings, 'save_options' ] );
    }

    /**
     * display POS page
     *
     * @return void
     */
    public function display_pos(){
        $template_helper = App::getInstance()->get_template_helper();
        $template_helper->get_template('admin/POS/default');
    }

    /**
     * Ajax handle, return a list of invoices
     *
     * @return void
     */
    public function get_invoices_by_quote(){

        if(!isset($_GET['quote_id']) || empty($_GET['quote_id'])){

            wp_send_json(['success' => false, 'message' => 'Quote id missing']);
        }

        $quote = hw_get_quote($_GET['quote_id']);

        // check if the quote has group id and booking id
        if(empty($quote->get('hw_cv_group_id')) || empty($quote->get('hw_cv_booking_id'))){

            wp_send_json(['success' => false, 'message' => 'The quote has not Group ID and/or Booking ID assigned.']);
        }

        $invoices = hw_get_quote_invoices($_GET['quote_id']);
        $result = [];

        $template_helper = App::getInstance()->get_template_helper();

        if(!empty($invoices)){
            // first option
            $result[] = sprintf('<option disabled selected>%s</option>', __('- Select an invoice -'));

            foreach($invoices as $invoice){
                $result[] = sprintf('<option value="%s">%s</option>', $invoice->getID(), sprintf('#%d - %s', $invoice->getID(), $invoice->getTitle()));
            }
        }
        
        $card = $quote->getCardData();
 
        $data = [
            'invoice_options' => implode('', $result),
            'billing_information' => $quote->getBillingInformation(),
            'card_digits' => $card ? substr($card->getNumber(), -4) : ''
        ];
         
        wp_send_json(['success' => true, 'data' => $data ]);
    }

    /**
     * Ajax handle, return a list of invoices
     *
     * @return void
     */
    public function get_invoice_checkout(){

        if(!isset($_GET['invoice_id']) || empty($_GET['invoice_id'])){

            wp_send_json(['success' => false, 'message' => 'Invoice id missing']);
        }

        $invoice = hw_get_invoice($_GET['invoice_id']);

        // could be for any reason the invoice does not exist
        if(!$invoice->exists()){

            wp_send_json(['success' => false, 'message' => 'No Invoice found with id ' . $_GET['invoice_id']]);
        }

        $template_helper = App::getInstance()->get_template_helper();

        ob_start();
        $template_helper->get_template('admin/POS/invoice-checkout', ['invoice' => $invoice]);
        $checkout = ob_get_clean();

        wp_send_json(['success' => true, 'data' => [ 'html' => $checkout, 'invoice' => $invoice->toArray() ]]);
    }

    /**
     * process pos payment
     *
     * @return json
     */
    public function hw_cv_pos_process_payment(){

        $controller = new POSController();
        
        if(!$controller->processCharge()){

            wp_send_json(['success' => false, 'message' => $controller->getError()]);
        }

        wp_send_json(['success' => true]);
    }
}