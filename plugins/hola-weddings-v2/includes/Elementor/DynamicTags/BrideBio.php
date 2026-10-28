<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Modules\DynamicTags\Module;

/**
 * Bride bio — hw_bride_bio.
 */
class BrideBio extends Tag {

    public function get_name() {

        return 'hw-bride-bio';
    }

    public function get_title() {

        return esc_html__( 'Bride Bio', 'hola-weddings' );
    }

    public function get_categories() {

        return [ Module::TEXT_CATEGORY ];
    }

    public function render() {

        echo wp_kses_post( wedding()->getBrideBio() );
    }
}
