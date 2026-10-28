<?php

namespace HWZoho\Admin\Metaboxes;

use HWZoho\Traits\Singleton;

class RoomBlock{

    use Singleton;

    protected $post_type = 'room_blocks';

    public function __construct(){

        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_room_blocks', [ $this, 'save' ] );
    }

    /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(){}

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        add_meta_box(
            'room_block_zoho',
            __( 'Zoho CRM', 'hola-weddings' ),
            [ $this, 'render_fields' ],
            $this->post_type,
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

        $room_block_id = get_post_meta( $post->ID, 'hwzi_room_block_id', true );
        ?>
            <label><?= __('Room block ID', 'hola-weddings') ?></label>
            <input class="form-control" type="number" name="hwzi_room_block_id" value="<?= $room_block_id ?>" />
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

        update_post_meta($post_id, 'hwzi_room_block_id', $_POST['hwzi_room_block_id']);
    }
}