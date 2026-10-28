<?php

/**
 * register ACF option pages
 *
 * @return void
 */
function hw_acf_register_option_pages() {

	acf_add_options_page( [
        'page_title' => 'Love Story Timeline',
        'menu_slug' => 'hw-lovestory-timeline',
        'parent_slug' => 'holaweddings',
        'position' => 2,
        'redirect' => false,
        'menu_icon' => [
            'type' => 'dashicons',
            'value' => 'dashicons-heart',
        ],
        'autoload' => true,
        'icon_url' => 'dashicons-heart',
    ] );
}
add_action( 'acf/init', 'hw_acf_register_option_pages' );


/**
 * set today as default value for the reservation field when invoice_id is passed
 */
add_filter('acf/load_value/name=hw_reservation_id', function( $value, $post_id, $field ){
    
    if(get_post_type($post_id) == 'invoices' && metadata_exists('post', $post_id, 'hw_reservation_id') == false && empty($value)
        && isset($_GET['invoice_id'])){
        
        $value = $_GET['invoice_id'];
    }

    return $value;
}, 10, 3);

/**
 * turns the reservation field read when a value is set, 
 * this prevent the user from changing the reservation once the payment is created
 */
add_filter('acf/prepare_field/name=hw_reservation_id', function($field){

    if( !empty($field['value']) ) {

        $field['readonly'] = 1;
    };
    
    return $field;
}, 11, 1);

/*
 * Apply a filter to the field hw_room to return the list of available rooms for the package
 */
add_filter('acf/load_field/name=hw_room', function($field){
    
    $rooms = hw_get_hotel_rooms();
    
    $options = [];
    $options[] = '- Select Room -';
    
    foreach($rooms as $room){
        
        $options[$room['id']] = $room['name'];
    }
    
    $field['choices'] = $options;
    
    return $field;
});

/**
 * Conditional Logic to display field if an options page field is set
 *
 * @param array $field
 * @return mixed
 */
function hide_teen_fields($field) {
    
    if(hw_is_teen_enabled()){
        return $field;
    }else{
        return;
    }
}
add_filter('acf/prepare_field/name=teen', 'hide_teen_fields', 20);
add_filter('acf/prepare_field/name=teen_multiplier', 'hide_teen_fields', 20);

/**
 * alter title for invoice quote field
 */
add_filter('acf/fields/post_object/result/key=field_65303ffe14188', function( $text, $post, $field, $post_id ) {
    
    $ci = ( new DateTime(get_field('hw_checkin', $post->ID)) )->format('d F Y');
    $co = ( new DateTime(get_field('hw_checkout', $post->ID)) )->format('d F Y');

    $text .= ' - #' . $post->ID .  ' - Dates: ' . $ci . ' - ' . $co;
    return $text;
}, 10, 4);

/**
 * restore custom fields
 */
add_filter('acf/settings/remove_wp_meta_box', '__return_false');

/**
 * modify invoice post object title
 */
add_filter('acf/fields/post_object/result/name=hw_invoice_id', function( $text, $post, $field, $post_id ) {

    $text = sprintf('#%d - %s', $post->ID, $text  );
    return $text;
}, 10, 4);

/**
 * set default sections for the new sections field, the value is updated with default values instead of returning them 
 * this way sub fields can be retrieved with the function
 */
add_filter('acf/load_value/name=hw_homepage_sections', function( $value, $post_id, $field ){

    if(!metadata_exists('post', $post_id, 'hw_homepage_sections')){

        
        $value = [
            [
                "acf_fc_layout" => "couple",
                "disable" => false,
                "title" => "Getting Married",
                "subtitle" => "We Are",
                "display_pictures" => true,
            ],
            [
                "acf_fc_layout" => "story",
                "disable" => false,
                "title" => "Our Story",
            ],
            [
                "acf_fc_layout" => "overview",
                "disable" => false
            ],
            [
                "acf_fc_layout" => "gallery",
                "disable" => false,
                "columns" => "4"
            ]
        ];
        update_field('hw_homepage_sections', $value, $post_id);

        $value = get_field('hw_homepage_sections');
    }

    return $value;
}, 10, 3);