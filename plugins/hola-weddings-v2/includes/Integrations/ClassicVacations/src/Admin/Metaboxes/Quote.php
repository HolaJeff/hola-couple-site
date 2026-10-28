<?php

namespace HW\Integrations\ClassicVacations\Admin\Metaboxes;

use HW\Traits\Singleton;

class Quote{

    use Singleton;

    protected $post_type = 'room_blocks';

    public function __construct(){

        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_quote', [ $this, 'save' ] );
    }

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        add_meta_box(
            'classic_vacations',
            __( 'Classic Vacations', 'hola-weddings' ),
            [ $this, 'render_fields' ],
            'quote',
            'side',
            'low'
        );
    }

    /**
     * render settings
     *
     * @return void
     */
    public function render_fields(){

        global $post;

        ?>
            <label class="form-label"><?= __('Group ID'); ?></label> <br>
            <input type="text"  class="form-control" value="<?= hw_cv_get_quote_group_id($post->ID) ?>" name="hw_cv_group_id" />
            <label class="form-label"><?= __('Booking ID'); ?></label> <br>
            <input type="text" class="form-control" value="<?= hw_cv_get_quote_booking_id($post->ID) ?>" name="hw_cv_booking_id" />
        <?php
    }

    /**
     * save post meta
     *
     * @param int $post_id
     * @param WP_Post $post
     * @param bool $update
     * @return void
     */
    public function save( $post_id ){

        if(isset($_POST['hw_cv_group_id'])){
            update_post_meta($post_id, 'hw_cv_group_id', $_POST['hw_cv_group_id']);
        }
        
        if(isset($_POST['hw_cv_booking_id'])){
            update_post_meta($post_id, 'hw_cv_booking_id', $_POST['hw_cv_booking_id']);
        }
    }
}