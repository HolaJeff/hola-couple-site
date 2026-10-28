<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Modules\DynamicTags\Module;

/**
 * Groom bio — hw_groom_bio.
 */
class GroomBio extends Tag {

    public function get_name() {

        return 'hw-groom-bio';
    }

    public function get_title() {

        return esc_html__( 'Groom Bio', 'hola-weddings' );
    }

    public function get_categories() {

        return [ Module::TEXT_CATEGORY ];
    }

    public function render() {

        echo wp_kses_post( wedding()->getGroomBio() );
    }
}
