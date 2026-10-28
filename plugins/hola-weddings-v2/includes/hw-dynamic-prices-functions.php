<?php

/*
 * Get dynamic prices by room
 *
 * Return WP_Query object
 */
function hw_get_dynamic_prices( $room_block_id, $date = null, $args = [] ){
    
    $default_args = [
        'post_type' => 'dynamic_prices',
        'posts_per_page' => -1,
        'orderby' => 'menu_order ID',
        'order' => 'ASC',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key'   => 'hw_room_block',
                'value' => $room_block_id
            ]
        ]
    ];
    
    $args = wp_parse_args($args, $default_args);
    
    if(!empty($date)){
        
        $args['meta_query'][] = [
            'key'   => 'from',
            'value' => $date,
            'compare' => '<=',
            'type' => 'DATE'
        ];
		  
		  $args['meta_query'][] = [
            'key'   => 'to',
            'value' => $date,
            'compare' => '>=',
            'type' => 'DATE'
        ];
    }
    
    return new \WP_Query($args);
}

/*
 * Get date first dynamic price
 *
 * Return WP_Query object
 */
function hw_get_date_dynamic_price( $room, $date = null ){
    
    return hw_get_dynamic_prices($room, $date, ['posts_per_page' => 1]);
}

/*
 * Add hotel column header
 */
add_filter('manage_dynamic_prices_posts_columns', function($columns) {

    return array_slice($columns, 0, 2, true) +
        [
            'room_block' => __('Room Block', 'hola-weddings'),
            'from' => __('From', 'hola-weddings'),
            'to' => __('To', 'hola-weddings')
        ] +
        array_slice($columns, 2, count($columns)-2, true);
});

/*
 * Add hotel column content
 */
add_action('manage_dynamic_prices_posts_custom_column', function($column_key, $post_id) {
    
    switch($column_key){
        case 'room_block':
            $room_block_id = get_field( 'hw_room_block', $post_id );
            
            if(!empty($room_block_id)): ?>
                <a href="<?= get_edit_post_link($room_block_id) ?>"><?= get_the_title($room_block_id) ?></a>
            <?php endif;
            break;
        case 'from':
            $date = get_field('from', $post_id);
            
            echo !empty($date) ? (new DateTime($date))->format('d F Y') : '--';
            break;
        case 'to':
            
            $date = get_field('to', $post_id);
            
            echo !empty($date) ? (new DateTime($date))->format('d F Y') : '--';
            break;
    }
}, 10, 2);


/*
 * Add hotel filter
 */
if (is_admin()){
   
   //this hook will create a new filter on the admin area for the specified post type
   add_action( 'restrict_manage_posts', function(){
        global $wpdb, $table_prefix;

        $post_type = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';
 
        //only add filter to post type you want
        if ($post_type == 'dynamic_prices'){

            $room_blocks = hw_get_room_blocks();

            if(!empty($room_blocks)){

                $current = isset($_GET['room_block_filter'])? $_GET['room_block_filter'] : '';

                ?>
                <select name="room_block_filter">
                    <option value="">- Select Room Block -</option>
                    <?php  foreach ($room_blocks as $room_block) {
                        printf(
                            '<option value="%s"%s>%s</option>',
                            $room_block->getID(),
                            $room_block->getID() == $current ? ' selected="selected"' : '',
                            $room_block->getTitle()
                        );
                    } ?>
                </select>
                <?php
            }
        }
    });

    //this hook will alter the main query according to the user's selection of the custom filter we created above:
    add_filter( 'parse_query', function($query){
        global $pagenow;
 
        $post_type = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';

        if ($post_type == 'dynamic_prices' && $query->get('post_type') == 'dynamic_prices'
            && $pagenow=='edit.php' && isset($_GET['room_filter']) && !empty($_GET['room_filter'])) {
            
            $query->set( 'meta_query', array(
                array(
                      'key'     => 'hw_room',
                       'compare' => '=',
                       'value'   => $_GET['room_filter'],
                       'type'    => 'numeric',
                  )
            ) ); 
        }

        if ($post_type == 'dynamic_prices' && $query->get('post_type') == 'dynamic_prices'
            && $pagenow=='edit.php' && isset($_GET['room_block_filter']) && !empty($_GET['room_block_filter'])) {
            
            $query->set( 'meta_query', array(
                array(
                      'key'     => 'hw_room_block',
                       'compare' => '=',
                       'value'   => $_GET['room_block_filter'],
                       'type'    => 'numeric',
                  )
            ) ); 
        }
        
        return $query;
    });
}