<?php

namespace HW\CPT;

use HW\Traits\Singleton;

class RoomBlock{

    use Singleton;

    public function __construct(){
        
        add_action('init',[ $this, 'register_cpt']);
        add_action( 'template_redirect', [$this, 'redirect_single_view']);
        add_action('manage_room_blocks_posts_custom_column', [$this, 'display_columns'], 10, 2);

        add_filter('manage_room_blocks_posts_columns', [$this, 'add_columns']);
    }

    /**
     * Register custom post type
     *
     * @return void
     */
    public function register_cpt(){

        register_post_type( 'room_blocks', [
			'labels' => [
				'name' => __( 'Room Blocks' ),
				'singular_name' => __( 'Room Block' )
			],
			'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-admin-home',
			'supports' => [ 'title', 'page-attributes', 'custom-fields' ]
		]);
    }

    /**
     * redirect the single room blocj view to the homepage
     *
     * @return void
     */
    public function redirect_single_view(){

        if(is_singular('room_blocks')){
            wp_redirect(home_url(), 301);
        }
    }

    /**
     * filter and add columns
     *
     * @param array $columns
     * @return array
     */
    public function add_columns ($columns) {

        return array_slice($columns, 0, 2, true) +
        [ 'october_room' => __('API Room', 'hola-weddings') ] +
        [ 'order' => __('Order', 'hola-weddings') ] +
        array_slice($columns, 2, count($columns)-2, true);
    }

    /**
     * display columns
     *
     * @param string $column_key
     * @param int $post_id
     * @return void
     */
    public function display_columns($column_key, $post_id) {
        
        switch($column_key){
            case 'october_room':
                $room_id = get_field( 'hw_room_id', $post_id );

                if($room_id){
                    $api_room = hw_get_room($room_id);

                    if($api_room == false){
                        echo __('API Error or room not found', 'hola-weddings' );
                    }else{
                        echo !empty($room_id) ? sprintf('#%s - %s', $room_id, $api_room['name']) : '';
                    }
                }
                
                break;
            case 'order':
                echo get_post_field('menu_order', $post_id);
                break;
        }
    }
}