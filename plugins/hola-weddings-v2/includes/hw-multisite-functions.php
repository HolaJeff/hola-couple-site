<?php

use HW\API\Operations\Hotels;

/**
 * return hotel when on multisite
 *
 * @param int $blog_id
 * @return array
 */
function hw_get_site_hotel( $blog_id ){

    if ( false === ( $hotel = HW\Cache::get_multisite( 'hotel', $blog_id ) ) ) {
        
        $hotel = hw_update_site_hotel($blog_id);
    }

    return $hotel;
}

/**
 * update site hotel
 *
 * @return array
 */
function hw_update_site_hotel($blog_id){

    $id = get_blog_option( $blog_id, 'hw_hotel_id', null );
    
    // Get it from API
    $hotel = Hotels::getHotel($id);

    switch_to_blog($blog_id);

    // Set transient
    HW\Cache::set( 'hotel', $hotel );

    restore_current_blog();

    return $hotel;
}
/**
 * give a user a role on the site they are currently on
 *
 * Users are network-wide but roles are per-site, so someone who registers or
 * signs in on a wedding site they have never visited would otherwise land
 * logged in with no capabilities at all.
 *
 * A no-op on single site, where wp_insert_user() already assigned the role.
 *
 * @param int $user_id
 * @return void
 */
function hw_add_user_to_current_blog( $user_id ){

    $user_id = (int) $user_id;

    if ( $user_id <= 0 || ! is_multisite() ) {
        return;
    }

    $blog_id = get_current_blog_id();

    if ( is_user_member_of_blog( $user_id, $blog_id ) ) {
        return;
    }

    add_user_to_blog( $blog_id, $user_id, get_option( 'default_role', 'subscriber' ) );
}
