<?php

namespace HW;

class Cache{

    use Traits\Singleton;

    protected static $_prefix = 'hw_transient_';

    /**
     * clear all cache
     *
     * @return void
     */
    public function clear(){

        global $wpdb;

        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s",
            '%' . $wpdb->esc_like(self::$_prefix ) . '%'
        );
        
        $wpdb->query($sql);
    }

    /**
     * clear all cache
     *
     * @return void
     */
    public function clear_network( string $key ){

        global $wpdb;

        if ( wp_using_ext_object_cache() ) {
            // if the app is using object cache PRO there's no way we can run a query to delete transient containing the 'key'
            // so we need to flush all site transients
            wp_cache_flush_group('site-transient'); 
        }else{

             $sql = $wpdb->prepare(
                "DELETE FROM {$wpdb->sitemeta}
                WHERE meta_key LIKE %s",
                '%' . $wpdb->esc_like( $key ) . '%'
            );
            
            $wpdb->query($sql);
        }
    }

    /**
     * Return cached value for a route key
     *
     * @param string $key
     * @return array|bool
     */
    public static function get( $key ){

        $option_key = self::$_prefix . $key;

        if ( false === ( $value = get_transient( $option_key ) ) ) {
            
            return false;
        }
        
        return $value;
    }

    /**
     * set transient
     *
     * @param string $key
     * @param array|object $data
     * @return void
     */
    public static function set( $key, $data, $expiration = DAY_IN_SECONDS ){

        $option_key = self::$_prefix . $key;

        set_transient( $option_key, $data, $expiration );
    }

    /**
     * Return network (global) cached value for a key
     *
     * @param string $key
     * @return array|bool
     */
    public static function get_network( $key ){

        if ( false === ( $value = get_site_transient( $key ) ) ) {
            
            return false;
        }
        
        return $value;
    }

    /**
     * set network (global) cached value for a key
     *
     * @param string $key
     * @param array|object $data
     * @return void
     */
    public static function set_network( $key, $data, $expiration = DAY_IN_SECONDS ){

        set_site_transient( $key, $data, $expiration );
    }


    /**
     * return a value when on multisite
     *
     * @param string $key
     * @param int $blog_id
     * @return void
     */
    public static function get_multisite($key, $blog_id){

        $option_key = '_transient_' . self::$_prefix . $key;
        
        return get_blog_option( $blog_id, $option_key, false );
    }
}