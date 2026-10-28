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