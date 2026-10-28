<?php

namespace HW\Elementor\DynamicTags;

use Elementor\Controls_Manager;
use Elementor\Modules\DynamicTags\Module;
use HW\Elementor;

/**
 * Shared behaviour for the bride and groom picture tags.
 *
 * Both settings store an attachment id, so subclasses only have to say which
 * one they read. The id is returned alongside the url so the consuming widget
 * can still apply its own image size.
 */
abstract class ImageTag extends \Elementor\Core\DynamicTags\Data_Tag {

    /**
     * Attachment id held by the wedding setting this tag exposes.
     *
     * @return int|string|null
     */
    abstract protected function get_attachment_id();

    public function get_group() {

        return Elementor::DYNAMIC_TAGS_GROUP;
    }

    public function get_categories() {

        return [ Module::IMAGE_CATEGORY ];
    }

    /**
     * Data tags do not get the advanced section that text tags do, so the
     * fallback image is registered here.
     */
    protected function register_controls() {

        $this->add_control(
            'fallback',
            [
                'label' => esc_html__( 'Fallback', 'hola-weddings' ),
                'type'  => Controls_Manager::MEDIA,
            ]
        );
    }

    public function get_value( array $options = [] ) {

        $attachment_id = $this->get_attachment_id();
        $url           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

        if ( empty( $url ) ) {

            $fallback = $this->get_settings( 'fallback' );

            return [
                'id'  => ! empty( $fallback['id'] ) ? (int) $fallback['id'] : null,
                'url' => ! empty( $fallback['url'] ) ? $fallback['url'] : '',
            ];
        }

        return [
            'id'  => (int) $attachment_id,
            'url' => $url,
        ];
    }
}
