<?php

namespace HW\Admin;
use HW\Resource;
use Formr\Formr;
use HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\Noty;
use HW\Traits\Singleton;

class NetworkSettings{

    use Singleton;
    
    protected $form;

    protected $tabs;
    
    /**
     * hook actions and filters
     *
     * @return void
     */
    public function __construct(){
        
        $this->form = new Formr('bootstrap', false );
        
        add_action( 'network_admin_menu', [ $this, 'add_network_menu' ], 9);
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 99);
        add_action( 'wp_ajax_hw_save_network_settings', [$this, 'ajax_save_options'] );
        add_action( 'wp_ajax_hw_network_refresh_permalinks', [$this, 'refresh_permalinks']);
    }

    /**
     * Add network admin menus
     *
     * @return void
     */
    public function add_network_menu(){

        $hook = add_menu_page( 
            __( 'Hola Weddings Settings', 'hola-weddings' ),
            'Hola Weddings Settings',
            'manage_options',
            'hw-network-settings',
            [ $this, 'render_settings_page' ],
            HW_ASSETS_URL . '/img/hw-icon.png'
        ); 

        add_action( 'load-' . $hook, [ $this, 'save_options' ] );
    }

    /**
     * Display settings page content
     *
     * @return void
     */
	public function render_settings_page(){

		$args = [
			'tabs' => $this->get_tabs(),
            'form' => $this->form,
            'action' => 'hw_save_network_settings',
            'title' => 'Network Settings'
		];
		
		hw_get_template( 'templates/admin/settings', $args );
	}
    
    /**
     * Add scripts
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts($hook){
        
        if( 'toplevel_page_hw-network-settings' == $hook  ){
            
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
            wp_enqueue_script('hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, true, ['strategy' => 'defer'] );
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
        do_action('hw_on_save_network_settings');

        return true;
	}
    
    /**
     * Return the tabs
     *
     * @return array
     */    
    public function get_tabs(){

        $tabs = [
            'terms-conditions' => [
                'label' => 'Terms & Conditions',
                'icon' => '<i class="far fa-handshake"></i>',
                'callback' => [ $this, 'display_network_page' ]
            ],
            'tools' => [
                'label' => 'Tools',
                'icon' => '<i class="fas fa-tools"></i>',
                'callback' => [ $this, 'display_network_page' ]
            ],
            'api' => [
                'label' => 'API',
                'icon' => '<i class="fas fa-database"></i>',
                'callback' => [ $this, 'display_network_page' ]
            ],
            'social-icons' => [
                'label' => 'Social Icons',
                'icon' => '<i class="fas fa-share-alt"></i>',
                'callback' => [ $this, 'display_network_page' ]
            ],
        ];
        
        return apply_filters('hw_network_settings_tabs', $tabs );
    }

    /**
     * Call back to display plugin settings tabs
     *
     * @param string $tab
     * @return void
     */
    public function display_network_page( $tab ){
        
        hw_get_template('templates/admin/network-settings/' . $tab .'-tab', ['form' => $this->form ] );
    }

    /**
     * refresh the permalinks
     *
     * @return json
     */
    public function refresh_permalinks(){

        global $wp_rewrite;

        $sites = get_sites([
            'number' => 9999
        ]);

        $i = 0;

        foreach ( $sites as $site ) {
            
            switch_to_blog( $site->blog_id );
            $wp_rewrite->init();

            flush_rewrite_rules();

            $i++;

            restore_current_blog();
        }
        $wp_rewrite->init();

        wp_send_json(['success' => true, 'message' => sprintf('Flushed %d sites', $i)]);
    }
    
}