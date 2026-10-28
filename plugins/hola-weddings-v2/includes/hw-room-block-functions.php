<?php

use HW\Models\RoomBlock;

/**
 * create a new Room block instance
 *
 * @param int $id
 * @return HW\Models\RoomBlock
 */
function hw_get_room_block($id){
    return new RoomBlock($id); 
}

/**
 * return a list of room blocks
 *
 * @return HW\Models\RoomBlocks[]
 */
function hw_get_room_blocks( $args = [] ){

    $room_blocks = [];
    $default_args = [
        'post_type' => 'room_blocks',
        'posts_per_page' => -1,
        'post_status' => [ 'publish', 'draft' ],
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    ];

    $args = wp_parse_args($args, $default_args);

    $query = new \WP_Query($args);

    if($query->have_posts()){
        foreach($query->posts as $post){
            $room_blocks[$post->ID] = new RoomBlock($post);
        }
    }

    return $room_blocks;
}

/**
 * return a list of published room blocks
 *
 * @return HW\Models\RoomBlocks[]
 */
function hw_get_published_room_blocks( $args = [] ){

    $default_args = [
        'post_status' => 'publish'
    ];

    $args = wp_parse_args($args, $default_args);

    return hw_get_room_blocks($args);
}