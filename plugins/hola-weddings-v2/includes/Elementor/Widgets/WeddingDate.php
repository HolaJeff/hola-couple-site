<?php

namespace HW\Elementor\Widgets;

class WeddingDate extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'wedding_date';
	}

	public function get_title() {
		return esc_html__( 'Wedding Date','hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'hotel', 'wedding', 'date' ];
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
				'label' => esc_html__( 'Wedding Date', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display Wedding Date.','hola-weddings' ),
				'content_classes' => 'alert',
			]
		);
        
        $this->add_control(
			'date_format',
			[
				'label' => esc_html__( 'Format', 'plugin-name' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => 'F d, Y',
				'placeholder' => esc_html__( 'Type the format','hola-weddings' ),
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
        
        $format = $this->get_settings_for_display( 'date_format' );
        
        $date = hw_get_date(true, $format);
		?>
		<div class="elementor-wedding-date">
			<?= $date ?>
		</div>
		<?php
	}
}