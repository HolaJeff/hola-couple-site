<?php

namespace HW\Elementor\Widgets;

class WeddingOverview extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'wedding_overview';
	}

	public function get_title() {
		return esc_html__( 'Wedding Overview', 'hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-handle';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'wedding', 'overview' ];
	}
    
    public function get_style_depends() {
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
			'section_wedding_overview',
			[
				'label' => esc_html__( 'Wedding Overview', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display Wedding overview.', 'hola-weddings' ),
				'content_classes' => 'alert',
			]
		);
        
        $this->add_control(
			'layout',
			[
				'label' => esc_html__( 'Layout', 'plugin-name' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'default' => 'vertical',
				'options' => [
					'vertical'  => esc_html__( 'Vertical', 'hola-weddings' ),
					'horizontal' => esc_html__( 'Horizontal', 'hola-weddings' ),
				],
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
		<div class="elementor-wedding-overview <?= $this->get_settings_for_display( 'layout' ); ?>">
			<?= hw_get_template('template-parts/home/overview/wedding-details'); ?>
		</div>
		<?php
	}
}