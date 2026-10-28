<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Modules\DynamicTags\Module;

/**
 * Wedding story — hw_story.
 *
 * The setting is edited with wp_editor, so the value is HTML.
 */
class WeddingStory extends Tag {

    public function get_name() {

        return 'hw-wedding-story';
    }

    public function get_title() {

        return esc_html__( 'Wedding Story', 'hola-weddings' );
    }

    public function get_categories() {

        return [ Module::TEXT_CATEGORY ];
    }

    public function render() {

        echo wp_kses_post( hw_get_option( 'hw_story', '' ) );
    }
}
