<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

/**
 * Shared behaviour for the bride and groom name tags.
 *
 * Both settings hold a full name, and headings on a wedding site often want
 * just the first one, so the choice is exposed as a control rather than as two
 * extra tags.
 */
abstract class NameTag extends Tag {

    /**
     * The name as it was typed in the wedding settings.
     *
     * @return string
     */
    abstract protected function get_full_name();

    /**
     * Only the first word of the configured name.
     *
     * @return string
     */
    abstract protected function get_first_name();

    public function get_categories() {

        return [ Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {

        $this->add_control(
            'name_format',
            [
                'label'   => esc_html__( 'Name', 'hola-weddings' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'full'  => esc_html__( 'Full name', 'hola-weddings' ),
                    'first' => esc_html__( 'First name only', 'hola-weddings' ),
                ],
                'default' => 'full',
            ]
        );
    }

    public function render() {

        $name = 'first' === $this->get_settings( 'name_format' )
            ? $this->get_first_name()
            : $this->get_full_name();

        echo esc_html( $name );
    }
}
