<?php

namespace HW\Elementor\Widgets;

class HotelGallery extends \Elementor\Widget_Base {
    
    public function get_name() {
        
		return 'hotel_gallery';
	}

	public function get_title() {
		return esc_html__( 'Hotel Gallery','hola-weddings' );
	}

	public function get_icon() {
		return 'eicon-slider-3d';
	}

	public function get_categories() {
		return [ 'hola-hotel' ];
	}

	public function get_keywords() {
		return [ 'hola', 'hotel', 'gallery' ];
	}
    
    public function get_style_depends() {
        return ['hw-elementor'];
    }
    
    public function get_script_depends() {
        return ['hw-elementor'];
    }
    
    protected function render(){
        
        $hotel = (object) hw_get_hotel();
        ?>
        <div class="elementor-hotel-gallery">
            <?php if(!empty($hotel->images)): ?>
                <div class="elementor-hotel-gallery__wrapper">
                    <?php foreach($hotel->images as $image): ?>
                        <div class="image-holder" style="background-image: url(<?= $image ?>)"></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <img src="<?= $hotel->image ?>" />
            <?php endif; ?>
        </div>
        <?php
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
			'section_hotel_description',
			[
				'label' => esc_html__( 'Hotel Gallery', 'hola-weddings' ),
			]
		);
        
        $this->add_control(
			'important_note',
			[
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => esc_html__( 'Display the hotel gallery.','hola-weddings' ),
				'content_classes' => 'alert',
			]
		);

		$this->end_controls_section();
	}
}