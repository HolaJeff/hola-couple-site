<?php

/**
 * display the network terms and conditions
 */
add_shortcode('network-terms-conditions', function($attrs){

    $terms = get_network_option(null, 'hw_global_terms_conditions');
    return apply_filters('the_content', $terms);
});

/**
 * display the love story timeline
 */
add_shortcode('love-story-timeline', function($attrs = []){

    $data = [
        'timeline' => get_field('hw_lovestory_timeline', 'options') ?: [],
        'display_image' =>  get_field('hw_lovestory_image_display', 'options') ?? true,
        'image_position' => get_field('hw_lovestory_image_position', 'options') ?: 'opposite',
    ];
    
    ob_start();
    hw_get_template( 'template-parts/shortcodes/love-story-timeline', $data );
    return ob_get_clean();
});

/**
 * render countdown
 */
add_shortcode('countdown', function($attrs = []){

    $default_attrs = [
        'skin' => 'default'
    ];

    $attrs = shortcode_atts($default_attrs, $attrs);

    // make sure the countdown script was loaded
    wp_enqueue_script('simple-countdown');

    $date = hw()->getWedding()->getDate();
    $uid = 'hw-countdown-' . uniqid();
    ob_start();
    hw_get_template('template-parts/shortcodes/countdown', [ 'date' => $date, 'id' => $uid, 'attrs' => $attrs ]);
    return ob_get_clean();
});