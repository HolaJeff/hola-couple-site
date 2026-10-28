<?php

namespace HW\Admin\Metaboxes;

use HW\Traits\Singleton;
use HW\API\Operations\HotelRooms;

class RoomBlock{

    use Singleton;

    protected $post_type = 'room_blocks';

    public function __construct(){

        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_room_blocks', [ $this, 'save' ], 10, 3 );
    }

    /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(){}

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        add_meta_box(
            'room_block_settings',
            __( 'Settings', 'hola-weddings' ),
            [ $this, 'render_settings' ],
            $this->post_type,
            'advanced',
            'high'
        );

        add_meta_box(
            'room_block_occupancy_rules',
            __( 'Occupancy Rules', 'hola-weddings' ),
            [ $this, 'render_occupancy_rules_metabox' ],
            $this->post_type,
            'advanced',
            'high'
        );

        add_meta_box(
            'room_block_inventory',
            __( 'Inventory', 'hola-weddings' ),
            [ $this, 'render_inventory_metabox' ],
            $this->post_type,
            'advanced',
            'high'
        );

        add_meta_box(
            'room_block_dynamic_prices',
            __( 'Dynamic Prices', 'hola-weddings' ),
            [ $this, 'render_dynamic_prices' ],
            $this->post_type,
            'advanced',
            'high'
        );
    }

    /**
     * render settings
     *
     * @return void
     */
    public function render_settings(){

        global $post;

        $hotel_id = hw_get_hotel_id();

        if(empty($hotel_id)){

            echo '<p class="alert alert-warning">' . __('Select an hotel in Settings') . '</p>';
            return;
        }

        $rooms = hw_get_rooms($hotel_id);
            
        if( is_wp_error($rooms) ){

            echo '<p class="alert alert-warning">' . esc_html($rooms->get_error_message()) . '</p>';
            return;
        }

        // structure array for a select
        $rooms = array_map(function($room){
            return [
                'id' => $room->getID(),
                'name' => $room->getName()
            ];
        }, $rooms);

        // add the first option
        $rooms = [ [ 'id' => 0, 'name' => '- Select Room -' ] ] + $rooms;
        $room_id = get_post_meta( $post->ID, 'hw_room_id', true );

        // render the template
        hw_get_template('templates/admin/metaboxes/roomblock/settings', [ 'rooms' => $rooms, 'current' => $room_id ]);
    }

    /**
     * render occupancy rules
     *
     * @return void
     */
    public function render_occupancy_rules_metabox(){

        global $post;

        $room_id = get_post_meta( $post->ID, 'hw_room_id', true );

        if(empty($room_id)){

            echo '<p class="alert alert-warning">' . __('Select a room', 'hola-weddings') . '</p>';
            return;
        }

        $room = hw_get_room($room_id);
        $occupancy_rules = get_post_meta( $post->ID, 'hw_occupancy_rules', true );
        

        hw_get_template('templates/admin/metaboxes/roomblock/occupancyrules', [ 'room' => $room, 'occupancy_rules' => $occupancy_rules ]);
    }

    /**
     * render inventory
     *
     * @return void
     */
    public function render_inventory_metabox(){

        global $post;

        $room_block = hw_get_room_block($post->ID);
        $room_id = get_post_meta( $post->ID, 'hw_room_id', true );

        if(empty($room_id)){

            echo '<p class="alert alert-warning">' . __('Select a room', 'hola-weddings') . '</p>';
            return;
        }

        $dates = hw_get_dates_from_range(get_start_date(), get_end_date());
        $booked_units = $room_block->getBookedUnits( get_start_date(), get_end_date());
        $inventory = $room_block->getInventory();

        hw_get_template('templates/admin/metaboxes/roomblock/inventory', [ 'room_id' => $room_id, 'dates' => $dates, 'booked_units' => $booked_units, 'inventory' => $inventory ]);
    }

    /**
     * Render dynamic prices
     *
     * @return void
     */
    public function render_dynamic_prices(){

        global $post;

        $query = new \WP_Query([
            'post_type' => 'dynamic_prices',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'hw_room_block',
                    'value' => $post->ID
                ]
            ]
        ]);

        hw_get_template('templates/admin/metaboxes/roomblock/dynamicprices', [ 'query' => $query ]);
    }

    /**
     * save post meta
     *
     * @param int $post_id
     * @param WP_Post $post
     * @param bool $update
     * @return void
     */
    public function save( $post_id, $post, $update ){

        if(isset($_POST['hw_room_id']) && $_POST['hw_room_id'] != null){

            update_post_meta($post_id, 'hw_room_id', $_POST['hw_room_id']);
        }
        
        if(isset($_POST['hw_occupancy_rules']) && $_POST['hw_occupancy_rules'] != null){

            update_post_meta($post_id, 'hw_occupancy_rules', $_POST['hw_occupancy_rules']);
        }

        if(isset($_POST['hw_inventory']) && $_POST['hw_inventory'] != null){

            update_post_meta($post_id, 'hw_inventory', $_POST['hw_inventory']);
        }
    }
}