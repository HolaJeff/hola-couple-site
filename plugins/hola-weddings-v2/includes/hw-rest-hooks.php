<?php

/**
 * change the API root
 */
add_filter( 'rest_url_prefix', function(){
	return 'api';
});

/**
 * Answer the booking form's REST calls in the language of the page that made
 * them. TranslatePress picks the language from the page URL, which a REST call
 * doesn't carry, so the form sends it in X-HW-Locale (see booking-form/index.jsx).
 *
 * A locale filter rather than switch_to_locale() in a REST hook: it is in place
 * before any text domain loads, and it doesn't need WordPress's own language
 * pack installed for that locale, which switch_to_locale() refuses without.
 * Late priority so it wins over TranslatePress for these requests.
 */
add_filter( 'locale', function( $locale ){

	static $requested = null;

	if ( $requested === null ) {
		$uri       = rawurldecode( $_SERVER['REQUEST_URI'] ?? '' );
		$header    = $_SERVER['HTTP_X_HW_LOCALE'] ?? '';
		$is_hw_api = str_contains( $uri, '/hw/v1/' ) || str_contains( $uri, 'rest_route=/hw/v1/' );
		$requested = $is_hw_api && hw_is_supported_locale( $header ) ? $header : false;
	}

	return $requested ?: $locale;
}, 100 );

/**
 * Translate room names and descriptions in the booking form's /rooms response
 * with the translations stored in TranslatePress (the same strings it
 * translates on the legacy form).
 *
 * This has to run on rest_pre_echo_response: during a REST request,
 * TranslatePress's translate_page() returns content untranslated unless the
 * current filter is rest_pre_echo_response, oembed_response_data or wp_mail, so
 * calling it from the endpoint callback does nothing. TranslatePress's own
 * handler on this filter only covers routes it knows (WP search, comments,
 * WooCommerce) and reads the language from the URL, hence our own.
 */
add_filter( 'rest_pre_echo_response', function( $result, $server, $request ){

	if ( $request->get_route() !== '/hw/v1/rooms' || ! is_array( $result ) ) {
		return $result;
	}

	foreach ( $result as &$room ) {
		if ( ! is_array( $room ) ) {
			continue;
		}
		foreach ( [ 'name', 'description' ] as $field ) {
			if ( isset( $room[ $field ] ) ) {
				$room[ $field ] = hw_trp_translate( $room[ $field ] );
			}
		}
	}
	unset( $room );

	return $result;
}, 20, 3 );