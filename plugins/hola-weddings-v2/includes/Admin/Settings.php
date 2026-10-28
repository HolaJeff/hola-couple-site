<?php

namespace HW\Admin;

use HW\Resource;
use HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\Noty;
use HW\Helpers\Assets\SortableJS;

class Settings extends Resource{

    protected $tabs;
    
    private $settings = [
        'hw_her_name',
        'hw_his_name',
        'hw_date',
        'hw_start_date',
        'hw_end_date',
        'hw_events',
        'hw_story',
        'hw_hotel_id',
        'hw_rooms',
        'hw_product_image_gallery',
        'hw_main_image',
        'hw_story_image',
        'hw_cancellation_policy',
        'hw_terms',
        'hw_insurance',
        'hw_service_fee',
        'hw_enable_payment',
        'hw_currency',
        'hw_currency_symbol',
        'hw_cart_mode',
        'hw_enable_teen',
        'hw_enable_2_teen',
        'hw_enable_2_child',
        'hw_enable_basic_price',
        'hw_date_unavailable',
        'hw_cart_max_adults',
        'hw_cart_max_teen',
        'hw_cart_max_children',
        'hw_enable_inventory',
        'hw_enable_dynamic_prices',
        'hw_alternative_email',
        'hw_min_nights',
        'hw_ages',
        'hw_deposit_type',
        'hw_min_deposit',
        'hw_deposit_nights_multiplier',
        'hw_deposit_increase_amount',
        'hw_deposit_increase_date',
        'hw_has_transfer',
        'hw_transfer_multiplier',
        'hw_enable_additional_notes',
        'hw_additional_notes_label',
        'hw_additional_notes_required',
        'hw_label_rates_disclosure',
        'hw_service_fee_child',
        'hw_bride_image',
        'hw_groom_image',
        'hw_bride_bio',
        'hw_groom_bio',
        'hw_default_quote_status',
        'hw_default_quote_availability',
        'hw_force_quote_status',
        'hw_forced_quote_status',
        'hw_dress_code',
        'hw_booking_form_hide_login_cta',
        'hw_pos_debug_level',
        'hw_invoice_payment_btn_action',
        'hw_invoice_payment_custom_message',
        'hw_invoice_payment_message_alternative_email',
        'hw_additional_events',
        'hw_extra_checkboxes',
        'hw_insurance_decline_text',
        'hw_bed_preferences',
        'hw_transfer_optional'
    ];
    
    /**
     * hook actions and filters
     *
     * @return void
     */
    public function init(){
        
        add_action( 'init', [ $this, 'init_tabs' ] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 99);
        add_action( 'hw_on_save_settings', [$this, 'do_post_save_actions']);
        add_action( 'wp_ajax_hw_save_settings', [$this, 'ajax_save_options'] );
    }
    
    /**
     * Add scripts
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts($hook){
        
        if( 'toplevel_page_holaweddings' == $hook || 'hola-weddings_page_hw_dynamic_prices' == $hook  ){
            
            if ( ! did_action( 'wp_enqueue_media' ) ) {
                wp_enqueue_media();
            }
        
            wp_enqueue_script( 'image-upload' );
            wp_register_script( 'hw-gallery', HW_URL . 'assets/js/admin/gallery.js', [ 'jquery' ], false, true );
            wp_enqueue_script( 'hw-gallery' );
            wp_register_script( 'hw-datepicker', HW_URL . 'assets/js/admin/datepicker.js', [ 'jquery', 'hw-jquery-ui' ], false, true );
            wp_enqueue_script( 'hw-datepicker' );
            
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
            wp_enqueue_script('hw-settings', HW_URL . 'assets/js/admin-settings.js', ['jquery','hw-jquery-ui', 'bootstrap', 'js-cookie', 'blockui'], true );
            SortableJS::import();
            wp_enqueue_script('hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, true, ['strategy' => 'defer'] );
        }
    }
	
    /**
     * Display settings page content
     *
     * @return void
     */
	public function submenu_page_callback(){

		$args = [
			'tabs' => $this->get_tabs(),
            'action' => 'hw_save_settings',
            'title' => 'Wedding Settings'
		];
		
		hw_get_template( 'templates/admin/settings', $args );
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
		
		if( 'POST' !== $_SERVER['REQUEST_METHOD'] ){
			
			return false;
		}
        
        foreach( $this->settings as $setting ){
            
            if(isset($_POST[$setting])){
                
                $value = $_POST[$setting];
                $value = is_array($value) ? stripslashes_deep($value) : stripslashes($value);

            }else{
                
                $value = '';
            }
            
            update_option( $setting, $value );
        }

        // Let other plugins to save their settings
        do_action('hw_on_save_settings');
		
        // no need to add a notice if doing ajax
        if(!wp_doing_ajax()){

            $this->add_notice( 'success', __( 'Options saved correctly' ) );
        }

        return true;
	}
    
    /**
     * Init default tabs
     *
     * @return void
     */
    public function init_tabs(){
        
        $tabs = [
            'general' => [
                'label' => 'General',
                'icon' => '<i class="fas fa-sliders-h"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'events' => [
                'label' => 'Events',
                'icon' => '<i class="fas fa-calendar"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'bridegroom' => [
                'label' => 'Bride & Groom',
                'icon' => '<i class="fas fa-male"></i><i class="fas fa-female"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'hotel' => [
                'label' => 'Hotel',
                'icon' => '<i class="fas fa-umbrella-beach"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'images' => [
                'label' => 'Images',
                'icon' => '<i class="far fa-images"></i>',
                'callback' => [ $this, 'display_tab' ]
            ]
            ,
            'labels' => [
                'label' => 'Labels & Texts',
                'icon' => '<i class="fas fa-language"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'booking' => [
                'label' => 'Booking',
                'icon' => '<i class="fas fa-calendar-alt"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'payment' => [
                'label' => 'Payment',
                'icon' => '<i class="far fa-money-bill-alt"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'invoicepayment' => [
                'label' => 'Invoice Payment',
                'icon' => '<i class="far fa-money-bill-alt"></i>',
                'callback' => [ $this, 'display_tab' ]
            ],
            'pos' => [
                'label' => 'Point of Sale (PoS)',
                'icon' => '<i class="fas fa-cash-register"></i>',
                'callback' => [ $this, 'display_tab' ]
            ]
        ];
        
        $this->tabs = apply_filters('hola_weddings_settings_tabs', $tabs );
    }
    
    public function get_tabs(){
        
        return $this->tabs;
    }

    /**
     * Call back to display plugin settings tabs
     *
     * @param string $tab
     * @return void
     */
    public function display_tab( $tab ){
        
        hw_get_template('templates/admin/settings/' . $tab .'-tab' );
    }

    /**
     * do post settings save actions
     *
     * @return void
     */
    public function do_post_save_actions(){

        hw_update_hotel_transient();
    }
    
}