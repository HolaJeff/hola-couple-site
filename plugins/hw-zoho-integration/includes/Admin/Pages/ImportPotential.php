<?php

namespace HWZoho\Admin\Pages;

use HWZoho\Traits\Singleton;
use \HWZoho\API\Auth\OAuth;
use \HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\JQueryValidate;
use HW\Session;
use HWZoho\API\CRM\Importers\RoomBlock as RoomBlockImporter;
use HWZoho\API\CRM\Modules\Potentials;
use HWZoho\Helpers\Assets\Noty;
use HWZoho\Services\CoupleSite;

class ImportPotential{

    use Singleton;

    public function __construct(){

        add_action( 'network_admin_menu', [ $this, 'add_network_menu' ]);
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
        add_action( 'wp_ajax_hwzi_import_wedding', [$this, 'import_potential']);
    }

    /**
     * enqueue style
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts($hook){
        
        if( 'zoho-crm_page_zoho-import-potential' == $hook ){
            
            BlockUI::import();
            JQueryValidate::import();
            Noty::import();
            
            wp_enqueue_style('hw-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css');

            wp_enqueue_script( 'jquery-ui' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'hwzi-import-potential', HW_ZOHO_ADMIN_ASSETS_URL . '/js/import-potential.js', ['jquery', 'blockui', 'jquery-ui-datepicker', 'noty'] );
        }
    }

    /**
     * Add menu page to network menu 
     *
     * @return void
     */
    public function add_network_menu(){

        add_submenu_page( 'zoho-options', __( 'Import Wedding', 'hw-zoho' ), __( 'Import Wedding', 'hw-zoho' ), 'manage_options', 'zoho-import-potential', [ $this, 'display' ] );
    }

    /**
     * display import wedding page
     *
     * @return void
     */
    public function display(){

        OAuth::initialize();

        $potential = null;
        $id = null;

        if(isset($_GET['id'])){

            $id = $_GET['id'];
            $potential = Potentials::getRecord($id);
        }

        hwzi_get_template('admin/pages/import-potential', ['id' => $id, 'potential' => $potential]);
    }

    /**
     * Ajax handler to import website
     *
     * @return void
     */
    public function import_potential(){

        check_ajax_referer( 'hwzi-import-wedding' );

        $root_domain = parse_url( get_site_url(), PHP_URL_HOST );
        $data = $_POST['hwzi_import']; 

        // make variablea available
        extract($data);

        $domain = $subdomain . '.' . $root_domain;
        $options = [
            'hw_her_name' => $spouse_name,
            'hw_his_name' => $contact_name,
            'hw_date' => $wedding_date,
            'hw_start_date' => $start_date,
            'hw_end_date' => $end_date,
            'hw_date_unavailable' => $cut_off_date,
            'hw_currency' => 'USD',
            'hw_currency_symbol' => '$',
            'hw_cart_max_adults' => 3,
            'hw_cart_max_teen' => 3,
            'hw_cart_max_children' => 3,
            'hw_hotel_id' => $hotel_id,
            'hw_has_transfer' => $has_transfer,
            'hw_service_fee' => $transfer_fee,
            'hwzi_wedding_id' => $potential_id,
            'hw_cart_mode' => 'room',
            'hw_ages' => [
                'infant_max' => 1,
                'children_min' => 2,
                'children_max' => 12,
                'teen_min' => 13,
                'teen_max' => 17,
            ],
            'hw_transfer_multiplier' => $transfer_multiplier,
            'hw_cart_max_adults' => 3,
            'hw_cart_max_teen' => 2,
            'hw_cart_max_children' => 2,
            'hw_cancellation_policy' => stripslashes($cancellation_policy),
            'hw_terms' => stripslashes($terms_conditions)
        ];

        // create + seed + import via the shared mechanism — identical to the
        // previous inline code, now also reused by the Stage 2 REST endpoint.
        $blog_id = CoupleSite::create($domain, $wedding_name, $options, isset($room_blocks) ? $room_blocks : [], get_current_user_id());

        if($blog_id instanceof \WP_Error){

            wp_send_json(['success' => false, 'message' => $blog_id->get_error_message()]);
        }

        zoho_add_notice(sprintf(
            __('Website %s created successfully. <a href="%s">Visit Site</a>'),
            $domain,
            get_admin_url($blog_id)
        ));

        wp_send_json(['success' => true]);
    }
}