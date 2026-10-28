<?php
/**
 * On-request booking flow.
 *
 * Bookings against unavailable inventory already land as quote_status
 * `pending` (Quotes.php) — but silently. These hooks make that a real
 * "on request" flow:
 *   1. guest gets a "request received — being confirmed" email
 *   2. the advisor gets an ACTION-REQUIRED alert
 *   3. pending quotes get a one-click "Confirm booking" row action in
 *      wp-admin that flips the status and emails the guest
 *
 * Confirmation fires the `hw_quote_confirmed` action for downstream sync.
 */

use HW\Quotes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * From/recipient defaults — same tenant values the rest of the plugin uses
 * (see send_email_to_admin); centralized here so the multi-tenant pass can
 * swap them in one place.
 */
function hw_onrequest_from_header(){
    // tenant branding pushed from the platform wins (hw-email-templates
    // mu-plugin network option); Hola's address is only the legacy fallback
    if ( function_exists('hw_email_from_header') ) {
        $from = hw_email_from_header();
        if ( ! empty($from) ) return $from;
    }
    return function_exists('hw_agency_ops_from') ? hw_agency_ops_from() : 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'; // phase4-ops-email
}

function hw_onrequest_admin_recipients(){
    $alt = hw_get_option('hw_alternative_email', '');
    $ops = function_exists('hw_agency_ops_email') ? hw_agency_ops_email() : get_option('admin_email');
    return $ops . ( ! empty($alt) ? ',' . $alt : '' );
}

/**
 * 1+2 — notify guest + advisor when a booking lands on request (pending).
 * Runs AFTER the Zoho push listener (priority 10) so it never delays it.
 *
 * @param int   $post_id
 * @param array $data       processed form data (incl. customer + quote_status)
 * @param mixed $room_block
 * @return void
 */
function hw_onrequest_notify( $post_id, $data, $room_block ){

    if ( ( $data['quote_status'] ?? '' ) !== Quotes::$pending_status ) {
        return;
    }

    $customer = $data['customer'] ?? [];
    $guest_email = $customer['email'] ?? get_post_meta( $post_id, 'hw_email', true );
    $guest_name  = trim( ( $customer['first_name'] ?? '' ) . ' ' . ( $customer['last_name'] ?? '' ) );
    $headers = [ 'Content-Type: text/html; charset=UTF-8', hw_onrequest_from_header() ];

    // guest: request received
    if ( ! empty( $guest_email ) ) {
        $subject = __( 'Your booking request has been received', 'hola-weddings' );
        $body = sprintf(
            '<p>%s</p><p>%s</p><p>%s</p>',
            sprintf( __( 'Hi %s,', 'hola-weddings' ), esc_html( $guest_name ?: __( 'there', 'hola-weddings' ) ) ),
            __( 'Thank you for your booking request. The room you selected is currently <b>on request</b>: we are confirming availability with the resort and will email you as soon as your reservation is confirmed.', 'hola-weddings' ),
            __( 'Your card has NOT been charged — payment details are only used once your reservation is confirmed.', 'hola-weddings' )
        );
        wp_mail( $guest_email, $subject, $body, $headers );
    }

    // advisor: action required
    $edit_link = get_admin_url( get_current_blog_id(), 'edit.php?post_type=quote' );
    $subject = sprintf( __( 'ACTION REQUIRED: on-request booking #%d — %s', 'hola-weddings' ), $post_id, $guest_name );
    $body = sprintf(
        '<p>%s</p><p><a href="%s">%s</a></p>',
        sprintf(
            __( 'Booking #%1$d from %2$s &lt;%3$s&gt; came in for inventory that is not currently available and is waiting as <b>on request</b>. Confirm availability with the supplier, then use the <b>Confirm booking</b> action on the quote.', 'hola-weddings' ),
            $post_id, esc_html( $guest_name ), esc_html( $guest_email )
        ),
        esc_url( $edit_link ),
        __( 'Open the quotes list', 'hola-weddings' )
    );
    wp_mail( hw_onrequest_admin_recipients(), $subject, $body, $headers );
}
add_action( 'hw_after_create_quote', 'hw_onrequest_notify', 20, 3 );

/**
 * 3 — "Confirm booking" row action on pending quotes.
 */
function hw_onrequest_row_action( $actions, $post ){

    if ( $post->post_type === 'quote'
        && get_post_meta( $post->ID, 'hw_quote_status', true ) === Quotes::$pending_status
        && current_user_can( 'manage_options' ) ) {

        $url = wp_nonce_url(
            admin_url( 'admin.php?action=hw_confirm_quote&quote=' . $post->ID ),
            'hw-confirm-quote-' . $post->ID
        );
        $actions['hw_confirm'] = '<a href="' . esc_url( $url ) . '" style="color:#0a7d32;font-weight:600;">'
            . __( 'Confirm booking', 'hola-weddings' ) . '</a>';
    }
    return $actions;
}
add_filter( 'post_row_actions', 'hw_onrequest_row_action', 10, 2 );

function hw_onrequest_confirm(){

    $post_id = intval( $_GET['quote'] ?? 0 );

    if ( ! $post_id || ! current_user_can( 'manage_options' )
        || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'hw-confirm-quote-' . $post_id ) ) {
        wp_die( __( 'Not allowed.', 'hola-weddings' ) );
    }

    update_post_meta( $post_id, 'hw_quote_status', Quotes::$confirmed_status );

    // guest: confirmed
    $guest_email = get_post_meta( $post_id, 'hw_email', true );
    if ( empty( $guest_email ) ) {
        $customer = get_post_meta( $post_id, 'hw_customer_data', true );
        $guest_email = is_array( $customer ) ? ( $customer['email'] ?? '' ) : '';
    }
    if ( ! empty( $guest_email ) ) {
        // tenant template override (hw-email-templates mu-plugin)
        $GLOBALS['hw_email_role'] = 'wp-booking-confirmed';
        $GLOBALS['hw_email_ctx'] = [
            'GUEST_NAME' => trim( (string) get_post_meta( $post_id, 'hw_first_name', true ) ) ?: 'there',
        ];
        wp_mail(
            $guest_email,
            __( 'Your reservation is confirmed!', 'hola-weddings' ),
            '<p>' . __( 'Great news — availability has been confirmed and your reservation is now <b>confirmed</b>. You will receive your payment schedule and travel details by email.', 'hola-weddings' ) . '</p>',
            [ 'Content-Type: text/html; charset=UTF-8', hw_onrequest_from_header() ]
        );
    }

    // downstream sync point (e.g. push the status change to Zoho later)
    do_action( 'hw_quote_confirmed', $post_id );

    wp_safe_redirect( add_query_arg( 'hw_confirmed', $post_id,
        admin_url( 'edit.php?post_type=quote' ) ) );
    exit;
}
add_action( 'admin_action_hw_confirm_quote', 'hw_onrequest_confirm' );

function hw_onrequest_confirm_notice(){

    if ( ! empty( $_GET['hw_confirmed'] ) ) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            sprintf( __( 'Booking #%d confirmed — the guest has been emailed.', 'hola-weddings' ),
                intval( $_GET['hw_confirmed'] ) )
        );
    }
}
add_action( 'admin_notices', 'hw_onrequest_confirm_notice' );
