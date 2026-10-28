<?php

namespace HW\Elementor\Widgets;

class WeddingGallery extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'wedding_gallery';
	}

	public function get_title() {
		return esc_html__( 'Wedding Gallery','hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-image-bold';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'wedding', 'gallery' ];
	}
    
    public function get_style_depends() {
        return ['hw-elementor'];
    }
    
    public function get_script_depends() {
        return ['hw-elementor'];
    }
    
    /**
	 * Register spacer widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {
        
		$this->start_controls_section(
			'section_wedding_gallery',
			[
				'label' => esc_html__( 'Wedding Gallery', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display Wedding gallery.','hola-weddings' ),
				'content_classes' => 'alert',
			]
		);

		$this->end_controls_section();
	}
    
    /**
	 * Render spacer widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
        
        $images = hw_get_gallery();
		?>
		<div class="elementor-wedding-gallery">
			<?php if(!empty($images)): ?>
                <div class="elementor-wedding-gallery__wrapper">
                    <?php foreach($images as $image): ?>
                        <a href="<?= wp_get_attachment_image_url($image, 'full') ?>">
                            <img src="<?= wp_get_attachment_image_url($image, 'medium') ?>" />
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
		</div>
		<?php
	}
}