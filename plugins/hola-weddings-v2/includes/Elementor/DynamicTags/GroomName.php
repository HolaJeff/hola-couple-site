<?php

namespace HW\Elementor\DynamicTags;

/**
 * Groom name — hw_his_name.
 */
class GroomName extends NameTag {

    public function get_name() {

        return 'hw-groom-name';
    }

    public function get_title() {

        return esc_html__( 'Groom Name', 'hola-weddings' );
    }

    protected function get_full_name() {

        return wedding()->getHisName();
    }

    protected function get_first_name() {

        return wedding()->getGroomFirstName();
    }
}
