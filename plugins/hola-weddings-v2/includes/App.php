<?php

namespace HW;

use HW\RestApi\BookingEndpoints;
use HW\Booking\BookingServiceProvider;
use HW\Controllers\InvoicePaymentConfirmation;
use HW\Controllers\POS;
use HW\CPT\Invoices;
use HW\CPT\Quote;
use HW\CPT\RoomBlock;
use HW\CPT\Transactions;
use HW\Factories\LoggerFactory;
use HW\Helpers\PluginData;
use HW\Integrations\Loader;
use HW\Objects\HWObject;
use HW\Traits\Singleton;
use HW\Utils\Logger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class App extends HWObject{

	use Singleton;

	public $wedding;

	public $session;
	
	public $settings;
	
	protected $api_connection;

	protected $logger;
	
	protected static $instance;
    
    public function __construct(){

		add_action( 'init', [ $this, 'add_cpt' ] );
		add_filter( 'register_post_type_args', [ $this, 'remove_native_post_type' ], 10, 2 );
		add_action( 'wpmu_new_blog', [ $this, 'on_create_blog'], 10, 6 );
		
		// create the website logger
		$this->logger = LoggerFactory::makeWebsiteLogger();

		$this->session = Session::getInstance();
		Routes::getInstance();
		Database::getInstance();
		Ajax::getInstance();
		Cache::getInstance();
		Scripts::getInstance();
		Template::getInstance();
		Elementor::getInstance();

		// REST API
		BookingEndpoints::getInstance();
		\HW\Feedback\FeedbackEndpoint::getInstance();

		// Authentication
		\HW\Auth\Google\Controller::getInstance();
		\HW\Auth\EmailVerificationController::getInstance();
		\HW\Auth\RegistrationController::getInstance();

		// Service Providers
		BookingServiceProvider::getInstance()->init();

		// load CPT
		Quote::getInstance();
		RoomBlock::getInstance();
		Quotes::getInstance();
		Invoices::getInstance();
		Transactions::getInstance();

		// Controllers
		POS::getInstance();
		InvoicePaymentConfirmation::getInstance();

		// load integrations
		Loader::getInstance();
		
		$this->wedding = Wedding::getInstance();
		
		$this->api_connection = new APIConnector;
        
		if(is_admin()){
			
			Admin\App::getInstance();
		}
    }
	
	public function getApiConnection(){
		
		return $this->api_connection;
	}

	/**
	 * add post types
	 *
	 * @return void
	 */
	public function remove_native_post_type($args, $post_type){

		if ($post_type === 'post') {

			$args['public']                = false;
			$args['show_ui']               = false;
			$args['show_in_menu']          = false;
			$args['show_in_admin_bar']     = false;
			$args['show_in_nav_menus']     = false;
			$args['can_export']            = false;
			$args['has_archive']           = false;
			$args['exclude_from_search']   = true;
			$args['publicly_queryable']    = false;
			$args['show_in_rest']          = false;
		}

		return $args;
	}
	
	/**
	 * add post types
	 *
	 * @return void
	 */
	public function add_cpt(){
		
		// Let's leave this out
		/* register_post_type( 'packages', [
			'labels' => [
				'name' => __( 'Packages' ),
				'singular_name' => __( 'Package' )
			],
			'public' => true,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-products',
			'supports' => [ 'title', 'page-attributes', 'custom-fields', 'editor' ]
		]); */
		
		
		register_post_type( 'dynamic_prices', [
			'labels' => [
				'name' => __( 'Dynamic Prices' ),
				'singular_name' => __( 'Rule' )
			],
			'public' => true,
			'has_archive' => false,
			'supports' => [ 'title', 'excerpt', 'page-attributes', 'custom-fields' ],
			'show_in_menu' => false,
			'show_in_nav_menus' => false
		]);
	}
	
	public function on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ){
		
		switch_to_blog( $blog_id );
        
		
		
        restore_current_blog();
	}

	/**
	 * return the logger
	 *
	 * @return Logger
	 */
	public function getLogger(){

		return $this->logger;
	}

	/**
	 * return the wedding instance
	 *
	 * @return Wedding
	 */
	public function getWedding(){

		return $this->wedding;
	}

	/**
	 * returns plugin data object
	 *
	 * @return Helpers\PluginData
	 */
	public function getPluginData(){

		return PluginData::getInstance();
	}
}