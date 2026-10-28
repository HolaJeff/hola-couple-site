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