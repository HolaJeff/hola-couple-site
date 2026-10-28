<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;

/**
 * Wedding date — hw_date.
 */
class WeddingDate extends Tag {

    public function get_name() {

        return 'hw-wedding-date';
    }

    public function get_title() {

        return esc_html__( 'Wedding Date', 'hola-weddings' );
    }

    public function get_categories() {

        return [ Module::TEXT_CATEGORY ];
    }

    protected function register_controls() {

        $this->add_control(
            'format',
            [
                'label'   => esc_html__( 'Format', 'hola-weddings' ),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'F d, Y'    => gmdate( 'F d, Y' ),
                    'l, F j, Y' => gmdate( 'l, F j, Y' ),
                    'M j, Y'    => gmdate( 'M j, Y' ),
                    'Y-m-d'     => gmdate( 'Y-m-d' ),
                    'm/d/Y'     => gmdate( 'm/d/Y' ),
                    'd/m/Y'     => gmdate( 'd/m/Y' ),
                    'custom'    => esc_html__( 'Custom', 'hola-weddings' ),
                ],
                'default' => 'F d, Y',
            ]
        );

        $this->add_control(
            'custom_format',
            [
                'label'       => esc_html__( 'Custom Format', 'hola-weddings' ),
                'default'     => 'F d, Y',
                'description' => sprintf(
                    '<a href="https://wordpress.org/documentation/article/customize-date-and-time-format/" target="_blank">%s</a>',
                    esc_html__( 'Documentation on date and time formatting', 'hola-weddings' )
                ),
                'condition'   => [
                    'format' => 'custom',
                ],
            ]
        );
    }

    public function render() {

        // Wedding builds its DateTime from an empty string when the setting is
        // blank, which resolves to "now". Bail instead, so an unset date prints
        // the tag fallback rather than today.
        if ( empty( hw_get_option( 'hw_date' ) ) ) {
            return;
        }

        $format = $this->get_settings( 'format' );

        if ( 'custom' === $format ) {
            $format = $this->get_settings( 'custom_format' );
        }

        if ( empty( $format ) ) {
            $format = 'F d, Y';
        }

        echo esc_html( hw_get_date( true, $format ) );
    }
}
