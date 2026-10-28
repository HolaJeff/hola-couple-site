<?php

namespace HWZoho\Admin;

use HW\Helpers\Assets\BlockUI;
use HWZoho\Admin\Metaboxes\Invoices;
use HWZoho\Admin\Metaboxes\Quote;
use HWZoho\Admin\Metaboxes\RoomBlock;
use HWZoho\Admin\Pages\ImportPotential;
use HWZoho\Admin\Pages\ImportRoomBlock;
use HWZoho\Admin\Pages\Logs;
use HWZoho\Admin\Pages\MissingZohoIDTool;
use \HWZoho\API\Auth\OAuth;
use \HWZoho\Admin\TableList\Weddings as Weddings_List;


class App{

    use \HWZoho\Traits\Singleton;

    public function __construct(){

        add_action( 'network_admin_menu', [ $this, 'add_network_menu' ]);
        add_action( 'admin_menu', [ $this, 'add_menu' ]);

        add_action( 'hw_on_save_settings', [ $this, 'save_settings' ] );
        add_action( 'admin_enqueue_scripts', [$this, 'register_scripts'], 11);

        add_filter( 'hola_weddings_settings_tabs', [ $this, 'add_zoho_tab' ], 10 );

        // init tools
        ImportPotential::getInstance();
        ImportRoomBlock::getInstance();
        MissingZohoIDTool::getInstance();
        Logs::getInstance();

        // Metaboxes
        RoomBlock::getInstance();
        Quote::getInstance();
        Invoices::getInstance();

        // Columns
        Columns::getInstance();
    }

    /**
     * register scripts
     *
     * @return void
     */
    public function register_scripts(){

        global $post_type;

        // Register style
        wp_register_style( 'hwzi-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css' );
        wp_register_script( 'hwzi-jquery-ui', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', ['jquery'] );

        wp_enqueue_style( 'hwzi-admin', HW_ZOHO_ASSETS_URL . '/css/admin.css' );

        wp_enqueue_script('jquery');
        wp_enqueue_script('hwzi-utils', HW_ZOHO_ASSETS_URL . '/admin/js/utils.js', ['jquery'] );

        if($post_type == 'quote'){
            BlockUI::import();
        }

    }

    /**
     * Add menu page to network menu 
     *
     * @return void
     */
    public function add_network_menu(){
        
        $hook = add_menu_page( 
            __( 'Zoho CRM', 'hw-zoho' ),
            'Zoho CRM',
            'manage_options',
            'zoho-options',
            [ $this, 'display_network_page' ],
            plugins_url( 'hw-zoho-integration/assets/images/favicon-8.png' )
        ); 

        add_action( 'load-' . $hook, [ $this, 'do_setting_actions' ] );

        // Submenus
        add_submenu_page( 'zoho-options', __( 'All Zoho Weddings', 'hw-zoho' ), __( 'Zoho CRM Weddings', 'hw-zoho' ), 'manage_options', 'zoho-crm-weddings', [ $this, 'display_weddings' ] );
    }

    /**
     * Display the network page to save options
     *
     * @return void
     */
    public function display_network_page(){

        hwzi_get_template('admin/settings');
    }

    /**
     * add website menu
     *
     * @return void
     */
    public function add_menu(){

        global $submenu;

        $hook = add_menu_page( 
            __( 'Zoho CRM', 'hw-zoho' ),
            'Zoho CRM',
            'manage_options',
            'zoho-crm',
            false,
            plugins_url( 'hw-zoho-integration/assets/images/favicon-8.png' ),
            35
        ); 

        $submenu['zoho-crm'] = [];
    }

    /**
     * Display available weddings in Zoho
     *
     * @return void
     */
    public function display_weddings(){

        OAuth::initialize();
        //$records = Potentials::getRecords();
        $table = new Weddings_List();
        $table->prepare_items();

        hwzi_get_template('admin/pages/weddings', ['table' => $table]);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function do_setting_actions(){
        
        if(isset($_POST['action']) && $_POST['action'] == 'zoho_send_test'){

            if(isset($_POST['update_test_data']) && $_POST['update_test_data'] == 1){

                file_put_contents( HW_ZOHO_INCLUDES_PATH . '/Admin/Test/test_data.json', stripslashes(html_entity_decode($_POST['data'])));
            }

            $test_data = file_get_contents( HW_ZOHO_INCLUDES_PATH . '/Admin/Test/test_data.json');

            $args = [
                'body' => $test_data,
                'method' => 'POST',
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Connection' => 'keep-alive'
                ]
            ];
    
            $response = wp_remote_post('https://www.zohoapis.com/crm/v2/functions/create_reservation_and_contact/actions/execute?auth_type=apikey&zapikey=1003.a84ea8a9d6907d17c79f8f1c0cd2ceb1.c781f6f2387c5c016b7eca7dd3e0a631', $args);

            //Check for success
            if(!is_wp_error($response) && ($response['response']['code'] == 200 || $response['response']['code'] == 201)) {
                
                $body = wp_remote_retrieve_body( $response );
                zoho_add_notice( 'Request sent successfully.<br><br>Response:' . $body );
            }else{

                if(is_wp_error($response)){

                    zoho_add_notice( 'Error.<br><br>' . print_r( $response->get_error_messages(), true ), 'error' );
                }
                return false;
            }
        }
    }

    /**
     * Add Zoho tab to Hola Weddings plugin
     *
     * @param [type] $tabs
     * @return void
     */
    public function add_zoho_tab( $tabs ){
        
        $tabs['zoho'] = [
            'label' => '<img style="vertical-align:middle; margin-right: 5px;" src="'.plugins_url( 'hw-zoho-integration/assets/images/favicon-8.png' ).'" />' . __('Zoho'),
            'callback' => [ $this, 'display_tab' ]
        ];

        return $tabs;
    }

    /**
     * display zoho tab content
     *
     * @param [type] $tab
     * @param [type] $form
     * @return void
     */
    public function display_tab( $tab, $form ){
        
        hwzi_get_template('admin/settings/tabs/zoho', ['form' => $form ] );
    }

    /**
     * Save settings
     *
     * @return void
     */
    public function save_settings(){

        if(isset($_POST['hwz_wedding_id'])){
                
            $value = $_POST['hwz_wedding_id'];
            $value = is_array($value) ? stripslashes_deep($value) : stripslashes($value);
            update_option( 'hwz_wedding_id', $value );
        }

        if(isset($_POST['hwzi_wedding_group_type'])){
                
            $value = $_POST['hwzi_wedding_group_type'];
            update_option( 'hwzi_wedding_group_type', $value );
        }
    }
}