<?php

namespace HW\Elementor\Widgets;

class HotelContacts extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'hotel_contacts';
	}

	public function get_title() {
		return esc_html__( 'Hotel Contacts','hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-mail';
	}

	public function get_categories() {
		return [ 'hola-hotel' ];
	}

	public function get_keywords() {
		return [ 'hola', 'hotel', 'contact' ];
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
			'section_hotel_contacts',
			[
				'label' => esc_html__( 'Hotel Contacts', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display hotel contact information.','hola-weddings' ),
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
		<div class="elementor-hotel-contacts">
			<?= hw_get_template('template-parts/hotel/contacts'); ?>
		</div>
		<?php
	}
}