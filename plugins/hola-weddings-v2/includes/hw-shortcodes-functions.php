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

/**
 * render the booking search bar
 *
 * Mirrors the booking-form app SearchBox (dates + guests + button). On submit
 * it redirects to the booking-form page with the search params in the query
 * string, which the React booking app bootstraps from on load.
 *
 * Attributes:
 *  - layout:  "horizontal" (default) | "vertical"
 *  - page_id: optional booking-form page id. When it points to a published
 *             page its permalink is used; otherwise the booking-form template
 *             is auto-detected.
 */
add_shortcode('hw-search-bar', function($attrs = []){

    $default_attrs = [
        'layout'  => 'horizontal',
        'page_id' => '',
    ];

    $attrs  = shortcode_atts($default_attrs, $attrs);
    $layout = in_array($attrs['layout'], ['horizontal', 'vertical'], true) ? $attrs['layout'] : 'horizontal';

    // Resolve the redirect target: an explicit published page id wins,
    // otherwise auto-detect the booking-form page.
    $booking_url = '';
    if ( ! empty($attrs['page_id']) ) {
        $page = get_post( (int) $attrs['page_id'] );
        if ( $page && 'page' === $page->post_type && 'publish' === $page->post_status ) {
            $booking_url = get_permalink($page);
        }
    }
    if ( empty($booking_url) ) {
        $booking_url = hw_get_booking_form_url();
    }

    // Alpine + the search-bar controller (styles ride on the global hw-main sheet).
    wp_enqueue_script('hw-alpinejs');
    wp_enqueue_script('hw-search-bar');

    ob_start();
    hw_get_template('template-parts/shortcodes/search-bar', [
        'layout'      => $layout,
        'booking_url' => $booking_url,
    ]);
    return ob_get_clean();
});