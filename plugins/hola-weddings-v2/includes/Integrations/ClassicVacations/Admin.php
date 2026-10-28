<?php

namespace HW\Integrations\ClassicVacations;

use Exception;
use Formr\Formr;
use HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\Bootstrap;
use HW\Helpers\Assets\JQueryValidate;
use HW\Helpers\Assets\Noty;
use HW\Integrations\ClassicVacations\Admin\Metaboxes\Quote;
use HW\Integrations\ClassicVacations\Admin\Pages\POS;
use HW\Integrations\ClassicVacations\App;
use HW\Integrations\ClassicVacations\CRM\Payment\Payment;
use HW\Integrations\ClassicVacations\CRM\Payment\PaymentRecord;
use HW\Integrations\ClassicVacations\CRM\Utils\APIException;
use HW\Integrations\ClassicVacations\CRM\Utils\PaymentJWEBuilder;
use HW\Integrations\ClassicVacations\CRM\Utils\PaymentPayload;
use HW\Integrations\ClassicVacations\Models\Charge;
use HW\Traits\Singleton;
use HW\Utils\BillingInformationBuilder;
use HW\Utils\CardBuilder;

class Admin{

    use Singleton;

    public static $options_page_slug = 'classic-vacations-options';

    public static $test_page_slug = 'classic-vacations-tests';

    protected $form;

    public function __construct(){

        $this->form = new Formr('bootstrap', false );
        
        add_action( 'network_admin_menu', [ $this, 'add_network_menu' ]);
        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);

        add_action( 'wp_ajax_hw_cv_process_test_payment', [$this, 'process_test_payment'] );
        add_action( 'wp_ajax_hw_cv_save_network_settings', [$this, 'ajax_save_options'] );

        // init other classes
        POS::getInstance();

        // Metaboxes
        Quote::getInstance();
    }

     /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts($hook){
        
        if( 'classic-vacations_page_classic-vacations-group' == $hook
            || 'classic-vacations_page_classic-vacations-tests' == $hook ){

            Bootstrap::import();
        }

        if( 'classic-vacations_page_classic-vacations-tests' == $hook ){

            Noty::import();
            JQueryValidate::import();
            BlockUI::import();
        }

        if('toplevel_page_classic-vacations-options' == $hook){
            
            if ( ! did_action( 'wp_enqueue_media' ) ) {
                wp_enqueue_media();
            }
        
            wp_enqueue_script( 'image-upload' );
            
            wp_enqueue_editor();

            Noty::import();
            BlockUI::import();
            
            wp_enqueue_style('hw-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css');
            wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
            wp_enqueue_style('hw-settings', HW_URL . 'assets/style/admin-settings.css');
            wp_enqueue_style('hw-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
            
            wp_enqueue_script('hw-jquery-ui', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', ['jquery']);
            wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js',['jquery'], true );
            wp_enqueue_script('js-cookie', 'https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js',null, true );
            wp_enqueue_script('hw-settings', HW_URL . 'assets/js/admin-settings.js',['jquery','hw-jquery-ui', 'bootstrap', 'js-cookie', 'blockui'], true );
   
        }
    }

    /**
     * Add network admin menus
     *
     * @return void
     */
    public function add_network_menu(){

        // main settings page
        $hook = add_menu_page( 
            __( 'Classic Vacations', 'hola-weddings' ),
            'Classic Vacations',
            'manage_options',
            self::$options_page_slug,
            [ $this, 'display_settings_page' ],
            CLASSICV_URL . '/assets/images/classic.png'
        ); 

        // submenu pages
        $connection_hook = add_submenu_page( self::$options_page_slug, __( 'Connection', 'hola-weddings' ), __( 'Connection', 'hola-weddings' ), 'manage_options', 'classic-vacations-connection', [ $this, 'display_connection_page' ] );
        $tests_hook = add_submenu_page( self::$options_page_slug, __( 'Tests Classic Vacation', 'hola-weddings' ), __( 'Tests', 'hola-weddings' ), 'manage_options', 'classic-vacations-tests', [ $this, 'display_tests_page' ] );
        $loghook = add_submenu_page( self::$options_page_slug, __( 'Classic Vacation Log', 'hola-weddings' ), __( 'Log', 'hola-weddings' ), 'manage_options', 'classic-vacations-log', [ $this, 'display_log_page' ] );
        
        // save action hooks
        add_action( 'load-' . $connection_hook, [ $this, 'do_submit_actions' ] );
        add_action( 'load-' . $loghook, [ $this, 'do_submit_actions' ] );
    }

    /**
     * Display settings page content
     *
     * @return void
     */
	public function display_settings_page(){

        $tabs = [
            'debug' => [
                'label' => 'Debug',
                'icon' => '<i class="fas fa-tools"></i>',
                'callback' => [ $this, 'display_network_page' ]
            ],
        ];

		$args = [
			'tabs' => $tabs,
            'form' => $this->form,
            'action' => 'hw_cv_save_network_settings',
            'title' => 'Network Settings'
		];
		
		hw_get_template( 'templates/admin/settings', $args );
	}

    /**
     * Call back to display plugin settings tabs
     *
     * @param string $tab
     * @return void
     */
    public function display_network_page( $tab ){
        
        $template_helper = App::getInstance()->get_template_helper();
        $template_helper->get_template('admin/settings/'. $tab .'-tab', ['form' => $this->form ] );
    }

    /**
     * display network page
     *
     * @return void
     */
    public function display_connection_page(){
        
        $template_helper = App::getInstance()->get_template_helper();
        $template_helper->get_template('admin/connection');
    }

    /**
     * display group page
     *
     * @return void
     */
    public function display_tests_page(){
        
        $template_helper = App::getInstance()->get_template_helper();
        $template_helper->get_template('admin/tests');
    }

    /**
     * display log page
     *
     * @return void
     */
    public function display_log_page(){

        $log = '';

        if(file_exists(CLASSICV_PATH . '/logs/log.txt')){
            $log = file_get_contents(CLASSICV_PATH . '/logs/log.txt');
        }
        
        $template_helper = App::getInstance()->get_template_helper();
        $template_helper->get_template('admin/log', ['log' => $log]);
    }

    /**
     * save options
     *
     * @return void
     */
    public function do_submit_actions(){

        if( 'POST' !== $_SERVER['REQUEST_METHOD'] ){
			
			return false;
		}
        
        if(isset($_POST['action'])){
            switch($_POST['action']){
                case 'hw_save':
                    update_network_option(null, 'hw_classic_vacation_client_id', $_POST['client-id']);
                    update_network_option(null, 'hw_classic_vacation_client_secret', $_POST['client-secret']);
                    update_network_option(null, 'hw_classic_vacation_client_redirect_uri', $_POST['redirect-uri']);
                    break;
                case 'hw_revoke_token':
                    delete_network_option(null, 'hw_classic_vacation_token');
                    break;
                case 'hw_clear_log':
                    if(file_exists(CLASSICV_PATH . '/logs/logs.txt')){
                        file_put_contents(CLASSICV_PATH . '/logs/logs.txt', '');
                    }
                    break;
            }
            
        }
    }

    /**
     * ajax wrapper method for settings save
     *
     * @return void
     */
    public function ajax_save_options(){

        check_ajax_referer( 'hw-save-settings' );

        if(!$this->save_options()){

            wp_send_json(['success' => false, 'message' => 'Invalid request']);
        }

        wp_send_json(['success' => true]);
    }

    /**
     * save settings
     *
     * @return void
     */
    public function save_options(){
		
		if( 'POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_POST['data']) ){
			
			return false;
		}

        // just to give the idea something was saved return true
        if(empty($_POST['data'])){

            return true;
        }

        $data = $_POST['data'];
        
        foreach( $data as $key => $value ){
            
                
            $value = is_array($value) ? stripslashes_deep($value) : stripslashes($value);
            update_network_option(null, $key, $value );
        }

        // Let other plugins to save their settings
        do_action('hw_cv_on_save_network_settings');

        return true;
	}

    /**
     * process the test payment
     *
     * @return json
     */
    public function process_test_payment(){

        $logger = hw_cv_get_logger();

        // only admins can perform this task
        if(!current_user_can('administrator')){
            wp_send_json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $jwt = Payment::generatePaymentContext();

        if($jwt instanceof APIException){
            wp_send_json(['success' => false, 'message' => 'A problem ocurred while generating JWT Payment context.']);
        }

        $billing_data = $_POST['billing'];
        $card_data = $_POST['card'];
        $booking_id = $_POST['booking_id'];
        $group_id = $_POST['group_id'];
        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        $ref_id = $_POST['ref_id'];

        if(hw_transaction_ref_id_exists($ref_id)){
            wp_send_json(['success' => false, 'message' => 'A transaction with this reference id already exists. Please change it.']);
        }

        $billing = BillingInformationBuilder::build(
            $billing_data['first_name'],
            $billing_data['last_name'],
            $billing_data['email'],
            null,
            $billing_data['address1'],
            $billing_data['locality'],
            $billing_data['administrative_area'],
            $billing_data['country'],
            $billing_data['postal_code']
        );

        // card
        $card = CardBuilder::build(
            $card_data['number'], 
            $card_data['month'], 
            $card_data['year'], 
            $card_data['ccv'], 
            $card_data['type'], 
            $card_data['holder']
        );

        // tokenize the card
        $payment_token = PaymentJWEBuilder::tokenize($jwt, $card);

         /***************** */
        /** Create Charge **/
        /***************** */

        $charge = new Charge();
        $charge->setAmount($amount)
            ->setGroupID($group_id)
            ->setBookingID($booking_id)
            ->setCard($card)
            ->setBillingInformation($billing)
            ->setRefID(hw_cv_generate_ref_id('TEST-PAGE'));

        /***************** **/
        /** Process Charge **/
        /***************** **/

        if(!$charge->process()){

            $post_data = [
                'post_type' => 'transactions',
                'post_title' => $ref_id,
                'post_status' => 'publish',
                'meta_input' => [
                    'hw_currency' => $currency,
                    'hw_amount' => $amount,
                    'hw_status' => 'failed',
                    'hw_transaction_id' => $ref_id,
                    'hw_gateway' => 'classic-vacations',
                    'hw_notes' => sprintf('Classic Vacations Payment Failed - Message: %s', $charge->getError())
                ]
            ];

            // create failed transaction
            wp_insert_post($post_data);


            wp_send_json(['success' => false, 'message' => $charge->getError()]);
        }

        // create transaction
        $post_data = [
            'post_type' => 'transactions',
            'post_title' => $ref_id,
            'post_status' => 'publish',
            'meta_input' => [
                'hw_currency' => $currency,
                'hw_amount' => $amount,
                'hw_status' => 'confirmed',
                'hw_transaction_id' => $ref_id,
                'hw_gateway' => 'Classic Vacation'
            ]
        ];

        $payment_record = Payment::getRecord($ref_id, null, $group_id);

        if($payment_record instanceof PaymentRecord){

            $cv_transaction_metadata = [
                'hw_cv_transaction_data' => json_encode($payment_record)
            ];
            
            $post_data['meta_input'] = array_merge($post_data['meta_input'], $cv_transaction_metadata);
        }

        $transaction_id = wp_insert_post($post_data, true);

        wp_send_json(['success' => true, 'message' => 'Payment created successfully.']);
    }
}