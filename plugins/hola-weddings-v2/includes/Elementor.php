<?php

namespace HW;

class Elementor{

    use Traits\Singleton;

    /**
     * Group the plugin's dynamic tags are listed under in the editor panel.
     */
    const DYNAMIC_TAGS_GROUP = 'hola-weddings';

    public function __construct(){

        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/dynamic_tags/register', [ $this, 'register_dynamic_tags' ] );
    }

    public function register_widgets( $widgets_manager ){

        // Booking Form
        $widgets_manager->register( new Elementor\Widgets\BookingForm() );
        $widgets_manager->register( new Elementor\Widgets\SearchBar() );

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

    /**
     * Expose the wedding settings to any Elementor control that accepts
     * dynamic content.
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     * @return void
     */
    public function register_dynamic_tags( $dynamic_tags_manager ){

        $dynamic_tags_manager->register_group( self::DYNAMIC_TAGS_GROUP, [
            'title' => esc_html__( 'Hola Weddings', 'hola-weddings' ),
        ] );

        // Wedding
        $dynamic_tags_manager->register( new Elementor\DynamicTags\WeddingDate() );
        $dynamic_tags_manager->register( new Elementor\DynamicTags\WeddingStory() );

        // Bride
        $dynamic_tags_manager->register( new Elementor\DynamicTags\BrideName() );
        $dynamic_tags_manager->register( new Elementor\DynamicTags\BrideImage() );
        $dynamic_tags_manager->register( new Elementor\DynamicTags\BrideBio() );

        // Groom
        $dynamic_tags_manager->register( new Elementor\DynamicTags\GroomName() );
        $dynamic_tags_manager->register( new Elementor\DynamicTags\GroomImage() );
        $dynamic_tags_manager->register( new Elementor\DynamicTags\GroomBio() );
    }
}
