<?php

namespace HW\CPT;

use HW\Traits\Singleton;

class Quote{

    use Singleton;

    public function __construct(){
        
        add_action( 'init', [ $this, 'register_cpt'] );

        add_action( 'manage_quote_posts_custom_column', [$this, 'display_columns'], 10, 2);
        add_filter( 'manage_quote_posts_columns', [$this, 'add_columns']);
    }

    /**
     * Register custom post type
     *
     * @return void
     */
    public function register_cpt(){

        register_post_type( 'quote',
            [
                'labels' => array(
                    'name' => __( 'Quotes' ),
                    'singular_name' => __( 'Quote' )
                ),
                'public' => true,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_nav_menus' => false,
                'show_in_menu' => 'holaweddings',
                'supports' => [
                    'title',
                    'custom-fields',
                    'author'
                ],
                'map_meta_cap' => true,
                'capability_type' => ['quote', 'quotes']
            ]
        );
    }

    /**
     * add custom columns
     *
     * @param array $columns
     * @return array
     */
    public function add_columns($columns){
        
        $columns = array_slice($columns, 0, 2, true) +
        [
            'main_guest' => __( 'Main Guest', 'hola-weddings' ),
            'checkin' => __( 'Check-In', 'hola-weddings' ),
            'checkout' => __( 'Check-Out', 'hola-weddings' ),
            'occupation' => __('Occupation', 'hola-weddings'),
            'total' => __( 'Total', 'hola-weddings' ),
            'status' => __('Status', 'hola-weddings'),
            'customer' => __('Customer', 'hola-weddings'),
        ] +
        array_slice($columns, 2, count($columns)-2, true);
        
        /*if(hw_is_teen_enabled()){
            
            $new_columns['teen'] = __('Teen', 'hola-weddings');
        }*/
    
        return $columns;
    }
    
    /**
     * display columns content
     *
     * @param array $column
     * @param int $post_id
     * @return void
     */
    public function display_columns( $column, $post_id ){
        
        $quote = hw_get_quote($post_id);

        switch ( $column ) {

            case 'main_guest' :
                $guest = $quote->getMainGuest();
                if($guest){
                    echo sprintf('%s <br> %s yrs', ucwords(hw_guest_full_name($guest)), $guest['age']);
                }
                break;
            case 'total':
                echo hw_format_price($quote->getTotal());
                break;
            case 'status':
                $status = $quote->getStatus();
                echo hw_quote_status_label($status);
                break;
            case 'occupation':
                echo sprintf('Adults: %s<br>Teens: %s<br>Children: %s<br>Infants: %s', $quote->getAdults(), $quote->getTeen(), $quote->getChildren(), $quote->getInfants());
                break;
            case 'checkin':
                echo $quote->getCheckin()->format('d F Y');
                break;
            case 'checkout':
                echo $quote->getCheckout()->format('d F Y');
                break;
            case 'customer':
                $owner = get_userdata( $quote->getOwnerID() );
                if( $owner ){
                    echo sprintf( '<a href="%s" target="_blank">%s (%s)</a>', get_edit_user_link( $owner->ID ), esc_html( trim( $owner->first_name . ' ' . $owner->last_name ) ?: $owner->display_name ), esc_html( $owner->user_email ) );
                }
                break;
        }
    }
}