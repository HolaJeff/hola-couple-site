<?php

/**
 * add custom gallery size
 *
 * @return void
 */
function hw_theme_elegant_theme_setup() {

	add_image_size( 'gallery-thumb', 600, 600, true );
}
add_action( 'after_setup_theme', 'hw_theme_elegant_theme_setup' );