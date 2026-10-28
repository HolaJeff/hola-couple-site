<?php

/**
 * return whether visitors may create an account with an email and a password
 *
 * Gates the sign-up pane, the cross-pane links and the endpoint alike — the
 * endpoint checks it too, so switching this off actually closes registration
 * rather than only hiding the form.
 *
 * Defaults on so the feature works after deploy without visiting every site on
 * the network. Note this is the plugin's own switch: the flow calls
 * wp_insert_user() directly, exactly as the booking form and the Google sign-in
 * already do, so WordPress's `users_can_register` is not consulted.
 *
 * @return boolean
 */
function hw_is_signup_enabled(){

    $enabled = (bool) hw_get_option( 'hw_enable_signup', true );

    /**
     * Filters whether email/password sign-up is available on this site.
     *
     * @param bool $enabled
     */
    return (bool) apply_filters( 'hw_is_signup_enabled', $enabled );
}

/**
 * return where to send the user after a successful Google login
 *
 * @return string 'same-page'|'account'
 */
function hw_get_google_login_redirect_to(){

    $value = hw_get_option( 'hw_google_login_redirect_to', 'same-page' );

    return in_array( $value, [ 'same-page', 'account' ], true ) ? $value : 'same-page';
}

/**
 * return whether Google login is both switched on and usable
 *
 * This is the single check the frontend should gate the button on.
 *
 * @return boolean
 */
function hw_is_google_login_configured(){

    return hw_is_google_login_enabled()
        && hw_get_google_client_id() !== ''
        && hw_get_google_client_secret() !== '';
}

/**
 * return the URL the Google button points at
 *
 * Deliberately nonce free: it only starts a redirect the visitor could start
 * themselves, the callback is what is protected (single-use signed state), and
 * a nonce would go stale behind page caching.
 *
 * @param string $redirect_to where to land after login, same host only
 * @return string
 */
function hw_get_google_login_url( $redirect_to = '' ){

    $args = [ 'hw_auth' => 'google' ];

    if( ! empty( $redirect_to ) ){

        // add_query_arg() does not encode values, so a return URL carrying its own
        // query string would be truncated at the first ampersand and the rest
        // would leak out as top-level parameters.
        $args['redirect_to'] = urlencode( $redirect_to );
    }

    return add_query_arg( $args, home_url( '/' ) );
}

/**
 * return the URL a Google sign-in should come back to
 *
 * Empty when the site is set to always land on the account dashboard, letting
 * the controller apply that preference instead.
 *
 * Only meaningful while rendering a front-end page. During an admin, ajax or
 * REST request the current URL is admin-ajax.php or similar, and returning that
 * would strand the visitor on a blank response after signing in.
 *
 * @return string
 */
function hw_get_google_login_return_url(){

    if( hw_get_google_login_redirect_to() !== 'same-page' ){

        return '';
    }

    // is_admin() also covers admin-ajax.php; REST needs its own check.
    if( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ){

        return '';
    }

    $url = hw_get_current_url();

    if( $url === '' ){

        return '';
    }

    // Drop any previous failure code so retries do not accumulate one.
    return esc_url_raw( remove_query_arg( 'hw_auth_error', $url ) );
}

/**
 * return the OAuth callback URL registered in the Google Cloud console
 *
 * Always resolves to the main site so a single redirect URI serves the whole
 * network. Override for local development, where Google requires a public host:
 *   define( 'HW_GOOGLE_OAUTH_REDIRECT_URI', 'https://your-tunnel.ngrok.io/?hw_auth=google-callback' );
 *
 * @return string
 */
function hw_get_google_callback_url(){

    if( defined( 'HW_GOOGLE_OAUTH_REDIRECT_URI' ) && HW_GOOGLE_OAUTH_REDIRECT_URI ){

        return (string) HW_GOOGLE_OAUTH_REDIRECT_URI;
    }

    $base = is_multisite()
        ? get_home_url( get_main_site_id(), '/' )
        : home_url( '/' );

    return add_query_arg( 'hw_auth', 'google-callback', $base );
}

/**
 * return the URL that redeems a handoff ticket on the site the login started on
 *
 * @param int $blog_id
 * @param string $ticket
 * @return string
 */
function hw_get_google_handoff_url( $blog_id, $ticket ){

    $base = is_multisite()
        ? get_home_url( $blog_id, '/' )
        : home_url( '/' );

    return add_query_arg( [
        'hw_auth' => 'google-handoff',
        'ticket'  => $ticket,
    ], $base );
}

/**
 * return a display message for a ?hw_auth_error= code
 *
 * @param string $code
 * @return string
 */
function hw_get_google_auth_error_message( $code ){

    $messages = [
        'google_not_available'   => __( 'Signing in with Google is not available right now.', 'hola-weddings' ),
        'google_denied'          => __( 'The Google sign-in was cancelled.', 'hola-weddings' ),
        'google_state'           => __( 'That sign-in link has expired. Please try again.', 'hola-weddings' ),
        'google_rate_limited'    => __( 'Too many attempts. Please wait a few minutes and try again.', 'hola-weddings' ),
        'google_token'           => __( 'We could not complete the sign-in with Google. Please try again.', 'hola-weddings' ),
        'google_identity'        => __( 'We could not verify your Google account. Please try again.', 'hola-weddings' ),
        'google_signup_disabled' => __( 'There is no account for that Google address. Please sign in with your email and password.', 'hola-weddings' ),
        'google_account_disabled'=> __( 'This account is no longer active. Please contact us for help.', 'hola-weddings' ),
        'google_user_failed'     => __( 'We could not set up your account. Please try again or contact us.', 'hola-weddings' ),
        'google_handoff'         => __( 'The sign-in could not be completed. Please try again.', 'hola-weddings' ),
    ];

    return $messages[ $code ] ?? __( 'Something went wrong signing in. Please try again.', 'hola-weddings' );
}

/**
 * return the Google login payload for the frontend
 *
 * Shape is stable so it can be dropped into any wp_localize_script call.
 *
 * @param string|null $redirect_to Null resolves the current page, '' opts out and
 *                                 leaves the choice to the controller.
 * @return array
 */
function hw_get_google_login_data( $redirect_to = null ){

    $enabled = hw_is_google_login_configured();

    // Resolved here for the same reason hw_google_login_button() does it: every
    // caller wants the visitor back where they started, and a missing value
    // silently degrades to the referer.
    if( is_null( $redirect_to ) ){

        $redirect_to = hw_get_google_login_return_url();
    }

    return [
        'enabled' => $enabled,
        'url'     => $enabled ? hw_get_google_login_url( $redirect_to ) : '',
        'label'   => hw_get_google_login_button_label(),
    ];
}