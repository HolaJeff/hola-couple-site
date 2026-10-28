<?php

namespace HW\Admin;

use DateTime;
use HW\Admin\Controllers\Quote as ControllersQuote;
use HW\Admin\Metaboxes\RoomBlock;
use HW\Admin\Metaboxes\Invoices;
use HW\Admin\Metaboxes\Quote;
use HW\Admin\Metaboxes\Transactions;
use HW\Helpers\Assets\Bootstrap;

class App{
    
    protected static $instance;
	
	public $settings;
    
    public static function getInstance(){
        
        if(!isset(self::$instance)){
            
            self::$instance = new static(); 
        }
        
        return self::$instance;
    }
    
    public function __construct(){
        
		add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
        add_action( 'admin_menu', [ $this, 'custom_menu' ] );
        add_action( 'admin_bar_menu', [ $this, 'add_cache_options' ], 99 );

        // Network admin sites custom columns
        add_filter( 'wpmu_blogs_columns', [ $this, 'add_blog_columns' ] );
        add_action( 'manage_sites_custom_column', [ $this, 'add_columns' ], 10, 2 );
        add_action( 'manage_blogs_custom_column', [ $this, 'add_columns' ], 10, 2 );
			
		$this->settings = Settings::getInstance();
		FeedbackSettings::getInstance();
		GoogleAuthSettings::getInstance();
        NetworkSettings::getInstance();
        UserVerification::getInstance();
        //$this->menus = Menus::getInstance();

        // Add metaboxes
        Quote::getInstance();
        Invoices::getInstance();
        RoomBlock::getInstance();
        Transactions::getInstance();

        // Controllers
        ControllersQuote::getInstance();
    }

     /**
     * add cache options to admin bar
     *
     * @param Admin_Bar $admin_bar
     * @return void
     */
    function add_cache_options($admin_bar){

        if(!current_user_can('administrator')){
            return;
        }

        $args = array(
            'id'    => 'hw_cache',
            'title' => 'Cache',
            'href'  => null,
        );
        $admin_bar->add_node( $args );

        if(!is_network_admin()){
            $args = array(
                'id'    => 'hw_clear_hotel_cache',
                'title' => 'Refresh Hotel Cache',
                'href'  => '#',
                'parent' => 'hw_cache',
            );
            $admin_bar->add_node( $args );
        }

        $args = array(
            'id'    => 'hw_clear_rooms_cache',
            'title' => 'Clear Rooms Cache',
            'href'  => '#',
            'parent' => 'hw_cache'
        );
        $admin_bar->add_node( $args );

        
    }

    /**
     * add columns to blogs view
     *
     * @param array $columns
     * @return array
     */
    public function add_blog_columns( $columns ) {
        
        $columns['hotel'] = __('Hotel', 'hola-weddings');
        $columns['names'] = __('Couple Names');
        $columns['wedding-date'] = __('Wedding Date');

        return $columns;
    }

    /**
     * display columns in blogs view
     *
     * @param string $column_name
     * @param int $blog_id
     * @return void
     */
    public function add_columns( $column_name, $blog_id ) {
        
        switch($column_name){
            case 'hotel':
                
                /* $hotel = hw_get_site_hotel($blog_id);

                echo !is_wp_error( $hotel ) ? $hotel['name'] : 'No hotel'; */
                break;
            case 'names':
                $hisname = get_blog_option($blog_id, 'hw_his_name', false);
                $hername = get_blog_option($blog_id, 'hw_her_name', false);
                if(!empty($hisname) && !empty($hername)){
                    echo sprintf('%s & %s', $hisname, $hername);
                }
                
                break;
            case 'wedding-date':
                $date = get_blog_option($blog_id, 'hw_date', false);
                if($date){
                    echo (new DateTime($date))->format('d F Y');
                }
                break;
        }
    }

    /**
     * include scripts
     *
     * @return void
     */
    public function enqueue_scripts(){

        wp_enqueue_style( 'hw-admin', HW_ASSETS_URL . 'style/admin.css' );
        wp_enqueue_style('hw-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

        wp_register_script( 'image-upload', HW_URL . 'assets/js/imageupload.js', [ 'jquery' ] );
        wp_enqueue_script( 'hw-utils', HW_URL . 'assets/js/utils.js', [ 'jquery' ] );
        wp_enqueue_script( 'hw-admin', HW_URL . 'assets/js/admin.js', [ 'jquery' ] );
        wp_enqueue_script('hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, true, ['strategy' => 'defer'] );

        Bootstrap::import();

        $hw_data = [
            'assetsurl' => HW_ASSETS_URL
        ];
        wp_localize_script('hw-admin', 'hw', $hw_data );
    }
    
    /**
     * Add admin menu
     *
     * @return void
     */
    public function custom_menu() {
        
        $settings_hook = add_menu_page('Wedding Settings', 'Wedding Settings', 'manage_options', 'holaweddings', [ $this->settings, 'submenu_page_callback' ] , HW_ASSETS_URL . 'img/hw-icon.png' , 2);
		add_submenu_page( 'holaweddings', 'Wedding Settings', 'Settings', 'manage_options', 'holaweddings', [ $this->settings, 'submenu_page_callback' ], 0);
		
        $hook = add_submenu_page( 'holaweddings', 'Dynamic Prices', 'Dynamic Prices', 'manage_options', 'edit.php?post_type=dynamic_prices');
        $logs_hook = add_submenu_page( 'holaweddings', 'Logs', 'Logs', 'manage_options', 'hw-site-logs', [ $this, 'display_logs' ]);

        add_action( 'load-' . $settings_hook, [ $this->settings, 'save_options' ] );
        add_action( 'load-' . $logs_hook, [ $this, 'do_clear_log' ] );
    }

    /**
     * return a list of logs
     *
     * @return array
     */
    public function get_log_list(){

        return [
            'default' => [
                'key' => 'default',
                'page_title' => 'Site Log',
                'filepath' => HW_PLUGIN_PATH . '/logs/' .  get_blog_details()->domain . '/log.txt'
            ],
            'pos-log' => [
                'key' => 'pos-log',
                'page_title' => 'PoS Log',
                'filepath' => HW_PLUGIN_PATH . '/logs/' .  get_blog_details()->domain . '/pos_log.txt'
            ]
        ];
    }

    /**
     * display logs
     *
     * @return void
     */
    public function display_logs(){

        $logs = $this->get_log_list();

        hw_get_template('templates/admin/logs', ['logs' => $logs]);
    }

    /**
     * clear the log
     *
     * @return void
     */
    public function do_clear_log(){

        if( isset($_POST['action']) && $_POST['action'] == 'hw_clear_log' && isset($_POST['log_key'])){
			
            $key = $_POST['log_key'];
            $logs = $this->get_log_list();

            if(isset($logs[$key])){

                $log = $logs[$key];
                if(file_exists($log['filepath'])){
                    
                    file_put_contents($log['filepath'], '');
                }
            }
		}
    }

}