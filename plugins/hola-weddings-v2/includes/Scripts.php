<?php

namespace HW;

use \HW\Helpers\Assets\BlockUI;
use HW\Helpers\Assets\JQueryValidate;
use HW\Helpers\Assets\Noty;

class Scripts{
    
    use Traits\Singleton;
    
    public function __construct(){
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 5);
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
        wp_enqueue_style('air-datepicker', 'https://cdn.jsdelivr.net/npm/air-datepicker@3.4.0/air-datepicker.min.css');
        wp_enqueue_style('hw-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css');

        /*
         * Scripts
         */ 
        wp_enqueue_script( 'hw-utils', HW_URL . 'assets/js/utils.js', [ 'jquery' ] );
        wp_register_script('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js', ['jquery']);
        wp_register_script('hw', HW_URL . 'assets/dist/js/app.js', ['jquery', 'fancybox', 'hw-utils', 'bootstrap'], $plugin_data->getVersion());
        wp_register_script('hw-cart', HW_URL . 'assets/js/cart.js', ['jquery', 'hw', 'air-datepicker'], $plugin_data->getVersion());
        wp_register_script('hw-elementor', HW_URL . 'assets/js/elementor-front.js', ['elementor-frontend', 'jquery', 'fancybox' ], $plugin_data->getVersion(), true);
        wp_register_script('simple-countdown',  HW_VENDOR_URL .'/simplycountdown/simplycountdown.umd.js');

        // Alpine. Enqueued globally below rather than on demand: the auth modal
        // ships in every page footer and Alpine drives its pane switching.
        wp_register_script('hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, $plugin_data->getVersion(), ['strategy' => 'defer']);
        wp_register_script('hw-search-bar', HW_URL . 'assets/js/search-bar.js', ['air-datepicker'], $plugin_data->getVersion(), true);
        wp_localize_script('hw-search-bar', 'hwSearchBar', [
            'bookingUrl'    => hw_get_booking_form_url(),
            'minNights'     => (int) hw_get_min_nights(),
            'isTeenEnabled' => hw_is_teen_enabled(),
            'isAdultsOnly'  => (bool) ( hw_get_hotel()['adults_only'] ?? false ),
        ]);

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
        // Deferred, so it runs after `hw` has registered its alpine:init listener.
        wp_enqueue_script('hw-alpinejs');
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
            'assetsurl' => HW_ASSETS_URL,
            'google_login' => hw_get_google_login_data()
        ];
        wp_localize_script('hw', 'hw', $hw_data );
        
        // Legacy booking form
        if( hw_is_legacy_booking_form() ){
            enqueue_cart();
        }

        // react booking form
        if( hw_is_react_booking_form() ){

            $is_dev = defined( 'HW_REACT_DEV' ) && HW_REACT_DEV;

            if ( ! $is_dev ) {
                // Depends on hw-main: the form's buttons are styled by the
                // plugin's .btn rules, and the few overrides that remain in this
                // bundle have to land after them.
                wp_enqueue_style(
                    'hw-booking-form',
                    HW_URL . 'assets/dist/booking-form/booking-form.css',
                    [ 'hw-main' ],
                    $plugin_data->getVersion()
                );
            }
            $src    = $is_dev
                ? 'http://localhost:8887/index.js'
                : HW_URL . 'assets/dist/booking-form/index.js';

            // react/react-dom are listed explicitly because the bundle now treats
            // them as externals — see webpack.config.cjs. wp-element happens to
            // pull them in too, but that is its implementation detail, not a
            // guarantee.
            wp_enqueue_script(
                'hw-booking-react',
                $src,
                [ 'react', 'react-dom', 'react-jsx-runtime', 'wp-element', 'wp-api-fetch', 'wp-i18n' ],
                $is_dev ? null : $plugin_data->getVersion(),
                true
            );

            // Loads languages/hola-weddings-{locale}-hw-booking-react.json for the
            // current locale (set per request by TranslatePress). Built by
            // `npm run i18n`.
            wp_set_script_translations( 'hw-booking-react', 'hola-weddings', HW_PLUGIN_PATH . '/languages' );

            wp_localize_script( 'hw-booking-react', 'hwBooking', [
                'nonce'          => wp_create_nonce( 'wp_rest' ),
                'restUrl'        => get_rest_url( null, 'hw/v1/' ),
                // wpLocale is sent back on every REST call (X-HW-Locale) so the
                // server answers in the page's language; locale drives Intl formatting.
                'wpLocale'       => determine_locale(),
                'locale'         => hw_get_intl_locale(),
                'weddingDate'    => hw()->getWedding()->getDate()?->format( 'Y-m-d' ),
                'hotelName'      => ( function() { $h = hw_get_hotel(); return is_array( $h ) ? ( $h['name'] ?? '' ) : ''; } )(),
                'hotelData'      => ( function() {
                    $h = hw_get_hotel();
                    if ( ! is_array( $h ) ) return null;
                    return [
                        'name'        => $h['name']         ?? '',
                        'mainImage'   => $h['main_image']   ?? '',
                        'images'      => $h['images']       ?? [],
                        'description' => $h['description']  ?? '',
                        'features'    => $h['features']     ?? [],
                        'address'     => $h['address']      ?? [],
                        'phone'       => $h['phone_number'] ?? '',
                        'email'       => $h['email']        ?? '',
                        'travelInfo'  => array_merge(
                            array_values( $h['state']['travel_info']   ?? [] ),
                            array_values( $h['country']['travel_info'] ?? [] ),
                        ),
                    ];
                } )(),
                'currency'       => hw_get_currency(),
                'currencySymbol' => hw_get_currency_symbol(),
                'assetsUrl'      => HW_ASSETS_URL,
                'personTypes'       => \HW\Booking\Configs\PersonTypeConfig::getAll(),
                'debug'             => current_user_can( 'administrator' ),
                'isLoggedIn'        => is_user_logged_in(),
                'hideLoginCta'      => (bool) hw_hide_form_login_cta(),
                'hideCreateAccount' => (bool) hw_booking_form_hide_create_account(),
                // { enabled, url, label } — render the button only when enabled.
                'googleLogin'       => hw_get_google_login_data(),
                'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
                'isPaymentEnabled'  => is_payment_enabled(),
                'isAdultsOnly'      => (bool) ( hw_get_hotel()['adults_only'] ?? false ),
                'isTeenEnabled'     => hw_is_teen_enabled(),
                'isTeen2Enabled'    => hw_is_2_teen_enabled(),
                'isChild2Enabled'   => hw_is_2_child_enabled(),
                'transportationMode' => hw_get_transportation_mode(),
                'hasTransfer'        => hw_is_transfer_enabled(),
                'serviceFee'         => hw_get_service_fee(),
                'childServiceFee'    => hw_get_child_service_fee(),
                'minDeposit'             => hw_get_min_deposit(),
                'depositType'            => hw_get_deposit_type(),
                'depositNightsMultiplier'=> hw_get_deposit_nights_multiplier(),
                'cancellationPolicy' => hw_get_cancellation_policy(),
                'paymentPolicy'      => hw_get_terms(),
                'globalTerms'        => get_network_option( null, 'hw_global_terms_conditions' ),
                'additionalNotes'    => [
                    'enabled'     => hw_is_additional_notes_enabled(),
                    'label'       => hw_get_additional_notes_label(),
                    'required'    => hw_is_additional_notes_is_required(),
                    'description' => hw_get_additional_notes_description(),
                ],
                'insuranceData'     => ( function() {
                    $ins = hw_get_insurance();
                    if ( is_wp_error( $ins ) || empty( $ins ) ) return null;
                    return [
                        'id'          => $ins['id']          ?? null,
                        'mode'        => $ins['mode']        ?? null,
                        'plans'       => $ins['plans']       ?? [],
                        'description' => $ins['description'] ?? '',
                        'image'       => $ins['main_image']  ?? '',
                    ];
                } )(),
                'minNights'         => (int) hw_get_min_nights(),
                'hasCustomer'       => is_user_logged_in(),
                'customerBilling'   => ( function() {
                    if ( ! is_user_logged_in() ) return null;
                    $b = hw_get_user_billing_information( get_current_user_id() );
                    if ( ! $b ) return null;
                    return [
                        'firstName' => $b->getFirstName(),
                        'lastName'  => $b->getLastName(),
                        'email'     => $b->getEmail(),
                        'phone'     => $b->getPhone(),
                        'address'   => $b->getAddress(),
                        'city'      => $b->getCity(),
                        'zip'       => $b->getPostalCode(),
                        'country'   => $b->getCountry(),
                        'state'     => $b->getState(),
                    ];
                } )(),
            ] );

        }

        if(hw_is_home_endpoint()){

            wp_enqueue_script('simple-countdown');
        }

        if( hw_is_account() ){

            Noty::import();
            // Account styles now live in assets/src/style/_account.scss and ship
            // with the compiled main bundle (hw-main), scoped under .hw-template-account.

            wp_enqueue_script( 'account', HW_URL . 'assets/js/account.js', ['jquery'], false, true);
            wp_enqueue_script( 'hw-alpinejs', HW_URL . 'assets/vendor/alpinejs/alpinejs.min.js', null, true, ['strategy' => 'defer'] );
        }

        // Feedback widget — admin only, all frontend pages.
        if ( current_user_can( 'manage_options' ) ) {
            $this->enqueue_feedback_widget( $plugin_data );
        }
    }

    /**
     * enqueue feedback widhet
     *
     * @param object $plugin_data
     * @return void
     */
    private function enqueue_feedback_widget( $plugin_data ): void {
        $is_dev = defined( 'HW_REACT_DEV' ) && HW_REACT_DEV;

        if ( ! $is_dev ) {
            wp_enqueue_style(
                'hw-feedback',
                HW_URL . 'assets/dist/feedback/feedback.css',
                [],
                $plugin_data->getVersion()
            );
        }

        $src = $is_dev
            ? 'http://localhost:8888/index.js'
            : HW_URL . 'assets/dist/feedback/index.js';

        wp_enqueue_script(
            'hw-feedback',
            $src,
            [ 'react', 'react-dom', 'react-jsx-runtime', 'wp-element' ],
            $is_dev ? null : $plugin_data->getVersion(),
            true
        );

        $project_key = (string) get_network_option( null, 'hw_jira_project_key', '' );
        $allowed     = array_values( array_filter( (array) get_network_option( null, 'hw_jira_allowed_issue_types', [] ) ) );

        $reporters = [];
        if ( \HW\Feedback\JiraTokenStore::is_connected() && $project_key !== '' ) {
            $cache_key = 'hw_jira_members_roles2_' . md5( $project_key );
            $cached    = get_transient( $cache_key );
            if ( $cached === false ) {
                $result = ( new \HW\Feedback\JiraClient() )->get_project_members( $project_key );
                $cached = is_wp_error( $result ) ? [] : $result;
                set_transient( $cache_key, $cached, HOUR_IN_SECONDS );
            }
            $reporters = $cached;
        }

        wp_localize_script( 'hw-feedback', 'hwFeedback', [
            'nonce'             => wp_create_nonce( 'wp_rest' ),
            'endpoint'          => get_rest_url( null, 'hw/v1/feedback-report' ),
            'issuesEndpoint'    => get_rest_url( null, 'hw/v1/feedback-issues' ),
            'reportersEndpoint' => get_rest_url( null, 'hw/v1/feedback-reporters' ),
            'proxyEndpoint'     => get_rest_url( null, 'hw/v1/feedback-asset-proxy' ),
            'deleteIssueBase'   => get_rest_url( null, 'hw/v1/feedback-issue' ),
            'isConfigured'      => (bool) ( new \HW\Feedback\JiraClient() )->is_configured(),
            'settingsUrl'       => network_admin_url( 'settings.php?page=hw-network-settings&tab=jira' ),
            'categories'        => $allowed,
            'priorities'        => [ 'Highest', 'High', 'Medium', 'Low', 'Lowest' ],
            'defaultPriority'   => (string) get_network_option( null, 'hw_jira_default_priority', 'Medium' ),
            'reporters'         => $reporters,
            'debug'             => defined( 'HW_REACT_DEV' ) && HW_REACT_DEV,
        ] );
    }
}