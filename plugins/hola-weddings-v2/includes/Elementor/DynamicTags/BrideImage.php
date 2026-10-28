<?php

namespace HW\Elementor\DynamicTags;

/**
 * Bride picture — hw_bride_image.
 */
class BrideImage extends ImageTag {

    public function get_name() {

        return 'hw-bride-image';
    }

    public function get_title() {

        return esc_html__( 'Bride Image', 'hola-weddings' );
    }

    protected function get_attachment_id() {

        return wedding()->getBridePhoto();
    }
}
