<?php

function hw_network_on_site_delete_remove_tables( $site ){

    global $wpdb;

    $table_name = $wpdb->prefix . 'rooms_inventory';

    if($wpdb->query('DROP TABLE ' . $table_name) === false){

        error_log( sprintf('Could not delete table %s from the database. Error: %s' , $table_name, $wpdb->last_error));
    }
}
add_action('wp_delete_site', 'hw_network_on_site_delete_remove_tables');