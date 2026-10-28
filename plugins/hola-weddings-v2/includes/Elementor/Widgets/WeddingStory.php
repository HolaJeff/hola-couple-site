<?php

namespace HW\Elementor\Widgets;

class WeddingStory extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'wedding_story';
	}

	public function get_title() {
		return esc_html__( 'Wedding Story', 'hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-text';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'wedding', 'story' ];
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
			'section_wedding_date',
			[
				'label' => esc_html__( 'Wedding Story', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display Wedding story.', 'hola-weddings' ),
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
        
		?>
		<div class="elementor-wedding-story">
			<?= hw_get_option('hw_story'); ?>
		</div>
		<?php
	}
}