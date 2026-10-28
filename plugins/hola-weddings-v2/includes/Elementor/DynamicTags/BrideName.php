<?php

namespace HW\Elementor\DynamicTags;

/**
 * Bride name — hw_her_name.
 */
class BrideName extends NameTag {

    public function get_name() {

        return 'hw-bride-name';
    }

    public function get_title() {

        return esc_html__( 'Bride Name', 'hola-weddings' );
    }

    protected function get_full_name() {

        return wedding()->getHerName();
    }

    protected function get_first_name() {

        return wedding()->getBrideFirstName();
    }
}
