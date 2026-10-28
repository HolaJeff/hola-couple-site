<?php

/**
 * return the api host
 *
 * @return string
 */
function hw_get_api_host(){

    return get_network_option(null, 'hw_api_host', 'api.hola-weddings.com');
}

/**
 * return the api host
 *
 * @return string
 */
function hw_get_api_base(){

    return get_network_option(null, 'hw_api_base', 'api/v1');
}

/**
 * return the api token
 *
 * @return string
 */
function hw_get_api_token(){

    return get_network_option(null, 'hw_api_token', '');
}

/**
 * return general company social links
 *
 * @return array
 */
function hw_get_network_social_links(){

    return get_network_option(null, 'hw_social_links', '');
}

/**
 * return the network-level Google OAuth client id
 *
 * @return string
 */
function hw_get_google_client_id(){

    if( defined( 'HW_GOOGLE_CLIENT_ID' ) && HW_GOOGLE_CLIENT_ID ){

        return (string) HW_GOOGLE_CLIENT_ID;
    }

    return (string) get_network_option( null, 'hw_google_client_id', '' );
}

/**
 * return the network-level Google OAuth client secret
 *
 * @return string
 */
function hw_get_google_client_secret(){

    if( defined( 'HW_GOOGLE_CLIENT_SECRET' ) && HW_GOOGLE_CLIENT_SECRET ){

        return (string) HW_GOOGLE_CLIENT_SECRET;
    }

    return (string) get_network_option( null, 'hw_google_client_secret', '' );
}