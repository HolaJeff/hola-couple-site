<?php

namespace HW\Elementor\Widgets;

class HotelAmenities extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'hotel_amenities';
	}

	public function get_title() {
		return esc_html__( 'Hotel Amenities', 'hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-star-o';
	}

	public function get_categories() {
		return [ 'hola-hotel' ];
	}

	public function get_keywords() {
		return [ 'hola', 'hotel', 'amenities' ];
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
			'section_hotel_amenities',
			[
				'label' => esc_html__( 'Hotel Amenities', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display the hotel amenities.', 'hola-weddings' ),
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
        
        $amenities = hw_get_hotel_amenities();
		?>
		<div class="elementor-hotel-amenities">
            <?php if(!empty($amenities)): ?>
                <div class="row">
                    <?php foreach($amenities as $amenity): ?>
                        <div class="col-md-3 amenity">
                            <i class="fas fa-check mr-2"></i>
                            <span class="name"><?= $amenity['name'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
		</div>
		<?php
	}
}