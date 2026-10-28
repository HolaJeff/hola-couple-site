<?php

namespace HW;

use \HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\JQueryValidate;
use HW\Helpers\Assets\Noty;

class Scripts{
    
    use Traits\Singleton;
    
    public function __construct(){
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    public function enqueue_scripts(){

        $plugin_data = hw()->getPluginData();
        
        wp_enqueue_style('bootstrap', HW_URL . 'assets/vendor/bootstrap4/bootstrap.min.css');
        wp_enqueue_style('jg', HW_URL . 'assets/vendor/justifiedgallery/style.css');
        wp_enqueue_style('slick', HW_URL . 'assets/vendor/slick/slick.css');
        wp_enqueue_style('slick-theme', HW_URL . 'assets/vendor/slick/slick-theme.css');
        wp_enqueue_style('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css');
        wp_enqueue_style('hw-main', HW_URL . 'assets/dist/css/main.min.css', ['bootstrap'], $plugin_data->getVersion());
        wp_enqueue_style('hw-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
        wp_enqueue_style('hw-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css');
        wp_enqueue_style('account-icons', HW_URL . 'assets/style/account/icons.min.css');

        /*
         * Scripts
         */ 
        wp_enqueue_script( 'hw-utils', HW_URL . 'assets/js/utils.js', [ 'jquery' ] );
        wp_register_script('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js', ['jquery']);
        wp_register_script('hw', HW_URL . 'assets/dist/js/app.js', ['jquery', 'fancybox', 'hw-utils', 'bootstrap'], $plugin_data->getVersion());
        wp_register_script('hw-cart', HW_URL . 'assets/js/cart.js', ['jquery', 'hw', 'air-datepicker'], $plugin_data->getVersion());
        wp_register_script('hw-elementor', HW_URL . 'assets/js/elementor-front.js', ['elementor-frontend', 'jquery', 'fancybox' ], $plugin_data->getVersion(), true);
        wp_register_script('simple-countdown',  HW_VENDOR_URL .'/simplycountdown/simplycountdown.umd.js');
        
        wp_localize_script('hw-cart', 'hw_cart', hw_get_cart_data() );
        wp_localize_script( 'hw-cart', 'messages', get_i18n_cart_messages());

        wp_enqueue_script('jquery');
        wp_enqueue_script('feather', 'https://unpkg.com/feather-icons');
        wp_enqueue_script('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.4.0/air-datepicker.min.js');
        wp_enqueue_script('maps', '//maps.googleapis.com/maps/api/js?key=AIzaSyA86Gyd5AFpqYP8t3xqu5GP5KSwrHxOBcs', '', [], false, true);
        wp_enqueue_script('hw-gmap', HW_URL . 'assets/js/gmap.js');
        wp_enqueue_script('bootstrap', HW_URL . 'assets/vendor/bootstrap4/bootstrap.min.js', ['jquery']);
        wp_enqueue_script('jg', HW_URL . 'assets/vendor/justifiedgallery/script.js');
        wp_enqueue_script('hcsticky', HW_URL . 'assets/vendor/hcsticky/hcsticky.js');
        wp_enqueue_script('slick', HW_URL . 'assets/vendor/slick/slick.min.js', ['jquery']);
        JQueryValidate::import();
        wp_enqueue_script('fancybox');
        wp_enqueue_script('hw');
        wp_enqueue_script('hw-jquery-ui', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', ['jquery']);

        if(!hw_is_pos()){
            BlockUI::import();
        }
        
        wp_localize_script( 'hw', 'ajax', [ 'ajax_url' => admin_url( 'admin-ajax.php' )]);
        wp_localize_script( 'hw', 'hw_messages', hw_get_i18n_messages());
        
        $hw_data = [
            'wedding_date' => hw()->getWedding()->getDate()?->format('Y-m-d'),
            'start_date' => ( new \DateTime(wedding()->getStartDate()))->format('Y-m-d'),
            'end_date' => ( new \DateTime(wedding()->getEndDate()))->format('Y-m-d'),
            'currency' => hw_get_currency(),
            'currency_symbol' => hw_get_currency_symbol(),
            'assetsurl' => HW_ASSETS_URL
        ];
        wp_localize_script('hw', 'hw', $hw_data );
        
        if( is_page_template( 'endpoints/book.php' ) ){
            
            enqueue_cart();
        }

        if(hw_is_home_endpoint()){

            wp_enqueue_script('simple-countdown');
        }

        if( hw_is_account() ){
            
            Noty::import();
            wp_enqueue_style( 'account', HW_URL . 'assets/style/account/style.css');

            wp_enqueue_script( 'account', HW_URL . 'assets/js/account.js', ['jquery'], false, true);
            wp_enqueue_script( 'hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, true, ['strategy' => 'defer'] );
        }
    }
}