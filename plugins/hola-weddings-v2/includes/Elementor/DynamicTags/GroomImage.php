<?php

namespace HW\Elementor\DynamicTags;

/**
 * Groom picture — hw_groom_image.
 */
class GroomImage extends ImageTag {

    public function get_name() {

        return 'hw-groom-image';
    }

    public function get_title() {

        return esc_html__( 'Groom Image', 'hola-weddings' );
    }

    protected function get_attachment_id() {

        return wedding()->getGroomPhoto();
    }
}
