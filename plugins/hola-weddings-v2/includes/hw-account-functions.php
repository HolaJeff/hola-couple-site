<?php

use \HW\Routes;
use \HW\Quotes;
use \HW\PDF\Quote as QuotePDF;

/**
 * Edit page title according to account templates
 */
add_filter( 'document_title_parts', function($title){

    if(hw_is_account()){

        $view = Routes::get_view();

        switch($view){
            case 'dashboard':
                $title['title'] = __('Dashboard', 'hola-weddings');
                break;
            case 'reservations':
                $title['title'] = __('Your Reservations', 'hola-weddings');
                break;
            case 'reservation':
                $title['title'] = sprintf(__('%s Reservation', 'hola-weddings'), get_the_title());
                break;
            case 'profile':
                $title['title'] = __('My Profile', 'hola-weddings');
                break;
        }
    }

    return $title;
}, 10, 1);

/**
 * return account view
 *
 * @return string
 */
function hw_get_account_view(){
    return Routes::get_view();
}

/**
 * return account url
 *
 * @return string
 */
function hw_get_account_url(){
    return '/account/';
}

/**
 * return account reservations url
 *
 * @return string
 */
function hw_get_reservations_url(){
    return home_url( '/account/reservations/' );
}

/**
 * return edit account url
 *
 * @return string
 */
function hw_get_account_profile_url(){
    return '/account/profile/';
}

/**
 * return current user quotes
 *
 * @return WP_Query
 */
function hw_get_user_quotes( $posts_per_page = 20, $paged = null ){

    $user_id    = get_current_user_id();
    $is_admin   = hw_user_has_role( 'administrator' );

    // fall back to the built-in pagination query var (?paged=N)
    if ( is_null( $paged ) ) {
        $paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
    }

    $meta_query = [
        'relation' => 'AND',
        [
            'key'     => 'hw_quote_status',
            'value'   => [ 'confirmed', 'pending', 'canceled' ],
            'compare' => 'IN',
        ],
    ];

    if ( ! $is_admin ) {
        $meta_query[] = [
            'key'   => 'hw_owner_user_id',
            'value' => $user_id,
            'type'  => 'NUMERIC',
        ];
    }

    return new WP_Query( [
        'post_type'      => 'quote',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'post_status'    => [ 'publish' ],
        'meta_query'     => $meta_query,
        'orderby'        => 'ID',
        'order'          => 'DESC',
    ] );
}

/**
 * format reservation permalink
 *
 * @param int $id
 * @return string
 */
function hw_get_reservation_permalink($id){

    return home_url('/account/reservation/' . $id . '/');
}

/**
 * create quote pdf url with security nonce
 *
 * @param int $id
 * @return string
 */
function hw_get_reservation_pdf_permalink($id){

    return wp_nonce_url( hw_get_reservation_permalink($id) . 'pdf', 'quote-pdf-' . $id );
}

/**
 * return a list of tabs
 *
 * @return array
 */
function hw_get_single_reservation_tabs(){

    return [
        'guests' => [
            'title' => __('Guests', 'hola-weddings'),
            'icon' => 'fas fa-users'
        ],
        'insurance' => [
            'title' => __('Insurance', 'hola-weddings'),
            'icon' => 'fas fa-user-shield'
        ],
        'customer' => [
            'title' => __('Customer Information', 'hola-weddings'),
            'icon' => 'far fa-address-card'
        ],
        'invoices' => [
            'title' => __('Upcoming Payments', 'hola-weddings'),
            'icon' => 'far fa-credit-card'
        ]
    ];
}

/**
 * Set post type for reservation view
 */
add_action( 'pre_get_posts', function($query){

    if($query->is_main_query() && hw_is_reservation()){

        $query->set('post_type', 'quote');
    }
});

/**
 * Add user account security
 */
add_action( 'template_redirect', function(){

    global $post, $wp;

    if(hw_is_account()){
        
        // check if user is not logged in then redirect to login page
        if(!is_user_logged_in()){

            $redirect = wp_login_url(site_url($wp->request));
            
            header( "Location: $redirect", true, 302 );
            exit;
        }

        /**
         * Restricted account state for anyone who has not confirmed their email.
         *
         * They stay signed in and can reach the verification screen to read the
         * message, request another email or log out — everything that actually
         * exposes customer data is held back until the address is proven.
         *
         * hw_can_access_verified_account_features() is the only check here on
         * purpose: it already answers true for administrators and for accounts
         * that predate this feature, so neither group is ever redirected.
         */
        if( ! hw_can_access_verified_account_features() && Routes::get_view() !== 'verify-email' ){

            wp_safe_redirect( hw_get_verify_email_url() );
            exit;
        }

        // conditional checks for reservations
        if(hw_is_reservation()){

            $format = get_query_var('hw_format', 'default');

            // if post is not defined and not of type quote
            if(!$post instanceof \WP_Post || $post->post_type != 'quote'){

                wp_safe_redirect(home_url(hw_get_account_url()));
            }

            $user_id = get_current_user_id();

            // if user is not owner of the quote then redirect
            if ( ! hw_user_has_role( 'administrator' ) && hw_get_quote( $post->ID )->getOwnerID() !== $user_id ) {
                wp_safe_redirect( home_url( hw_get_account_url() ) );
                exit;
            }

            if($format == 'pdf'){

                // check nonce, only if the link has a valid nonce can pass. This ensure the link was generated in account
                if(!wp_verify_nonce( $_GET['_wpnonce'], 'quote-pdf-' . $post->ID )){
                    
                    die('The link has expired.');
                }

                $status = get_post_meta($post->ID, 'quote_status', true);
                // if the quote hasn't been confirmed yet do not let print the pdf
                if($status == Quotes::$pending_status ){
                    
                    hw_add_notice(__('Not authorized.', 'hola-weddings'), 'error');
                    wp_safe_redirect(home_url(hw_get_account_url()));
                }

                $quotePdf = new QuotePDF($post->ID);
                $quotePdf->output('Reservation_#'.$post->ID.'.pdf');
                die;
            }
        }
    }
}, 11);

/**
 * Print notices
 */
add_action('hw_account_before_content', function(){

    hw_print_notices();
});

/**
 * hook to change user password
 */
add_action('init', function(){

    if(is_user_logged_in() && isset($_POST['action']) && $_POST['action'] == 'change_user_password' 
        && wp_verify_nonce( $_POST['_wpnonce'], 'change-password_' . get_current_user_id() )){

        $old_password = trim($_POST['old-password']);
        $password = trim($_POST['new-password']);
        $confirm_password = trim($_POST['confirm-password']);
        $user = wp_get_current_user();

        // check for empty values
        if(empty($old_password) || empty($password) || empty($confirm_password)){

            hw_add_notice(__('Missing fields', 'hola-weddings'), 'error');
            wp_safe_redirect(home_url(hw_get_account_profile_url()));
            exit;
        }

        // check if old password is correct
        if(!wp_check_password( $old_password, $user->data->user_pass, $user->ID ) ){

            hw_add_notice(__('Old password is wrong', 'hola-weddings'), 'error');
            wp_safe_redirect(home_url(hw_get_account_profile_url()));
            exit;
        }

        // check if password if confirmed
        if($password != $confirm_password){

            hw_add_notice(__('Please confirm password', 'hola-weddings'), 'error');
            wp_safe_redirect(home_url(hw_get_account_profile_url()));
            exit;
        }

        // update password
        wp_set_password($password, get_current_user_id());

        // Persist user
        wp_set_auth_cookie(
            $user->ID,
            False// << This is the "remember me" option.
        );
        
        hw_add_notice(__('Password changed correctly', 'hola-weddings'));

        // safely redirect
        wp_safe_redirect(home_url(hw_get_account_profile_url()));
        exit;
    }
});

/**
 * hook to update the user's saved billing information
 */
add_action('init', function(){

    if(is_user_logged_in() && isset($_POST['action']) && $_POST['action'] == 'hw_update_billing'
        && isset($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'hw_update_billing_' . get_current_user_id() )){

        // Billing (payer) name is saved to user meta, separate from the account
        // identity — the WP user's name/email are not modified here.
        $billing = new \HW\Models\CRM\BillingInformation([
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name'  => sanitize_text_field($_POST['last_name'] ?? ''),
            'phone'      => sanitize_text_field($_POST['phone_number'] ?? ''),
            'address'    => sanitize_text_field($_POST['address'] ?? ''),
            'city'       => sanitize_text_field($_POST['city'] ?? ''),
            'state'      => sanitize_text_field($_POST['state'] ?? ''),
            'country'    => sanitize_text_field($_POST['country'] ?? ''),
            'zip'        => sanitize_text_field($_POST['zip'] ?? ''),
        ]);

        hw_update_user_billing_information( get_current_user_id(), $billing );

        hw_add_notice(__('Billing information updated.', 'hola-weddings'));

        wp_safe_redirect(home_url(hw_get_account_profile_url()));
        exit;
    }
});

/**
 * If User is Subscriber, It can not login in Dashboard
 */
add_action('init', function()
{
    $user = wp_get_current_user();

    if( is_admin() && !defined('DOING_AJAX') && in_array('subscriber', $user->roles) )
    {
        wp_redirect(home_url());
        exit;
    }
});