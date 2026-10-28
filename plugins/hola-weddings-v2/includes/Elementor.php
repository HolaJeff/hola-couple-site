<?php

namespace HW;

class Elementor{
    
    use Traits\Singleton;
    
    public function __construct(){
        
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
    }
    
    public function register_widgets( $widgets_manager ){
        
        // Booking Form
        $widgets_manager->register( new Elementor\Widgets\BookingForm() );
        
        // Wedding Widgets
        $widgets_manager->register( new Elementor\Widgets\WeddingDate() );
        $widgets_manager->register( new Elementor\Widgets\WeddingStory() );
        $widgets_manager->register( new Elementor\Widgets\WeddingGallery() );
        $widgets_manager->register( new Elementor\Widgets\WeddingOverview() );
        $widgets_manager->register( new Elementor\Widgets\WeddingLovestoryTimeline() );
        $widgets_manager->register( new Elementor\Widgets\WeddingCountdown() );
        
        // Hotel Widgets
        $widgets_manager->register( new Elementor\Widgets\HotelGallery() );
        $widgets_manager->register( new Elementor\Widgets\HotelDescription() );
        $widgets_manager->register( new Elementor\Widgets\HotelOverview() );
        $widgets_manager->register( new Elementor\Widgets\HotelAmenities() );
        $widgets_manager->register( new Elementor\Widgets\HotelRooms() );
        $widgets_manager->register( new Elementor\Widgets\HotelContacts() );
        $widgets_manager->register( new Elementor\Widgets\HotelTTK() );
    }
}