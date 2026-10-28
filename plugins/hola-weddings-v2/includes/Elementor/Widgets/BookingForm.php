<?php

namespace HW\Elementor\Widgets;

class BookingForm extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'booking_form';
	}

	public function get_title() {
		return esc_html__( 'Booking Form', 'hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-cart-light';
	}

	public function get_categories() {
		return [ 'hola' ];
	}

	public function get_keywords() {
		return [ 'hola', 'wedding', 'booking', 'form' ];
	}
    
    public function get_style_depends() {
        return ['hw-elementor'];
    }
    
    public function get_script_depends() {
        
        enqueue_cart();
        
        return ['hw-cart'];
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
			'section_booking_form',
			[
				'label' => esc_html__( 'Booking Form', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display the booking form.', 'hola-weddings' ),
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
		<div class="elementor-booking-form">
			<?php require_once( HW_TEMPLATE_PARTS . '/booking-form/form.php' ); ?>
		</div>
		<?php
	}
}