<?php

namespace HWZoho\Admin\Metaboxes;

use HWZoho\Traits\Singleton;
use HWZoho\Helpers\Assets\Noty;

class Quote{

    use Singleton;

    protected $post_type = 'room_blocks';

    public function __construct(){

        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_quote', [ $this, 'save' ] );
    }

    /**
     * enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts(){
     
        Noty::import();

        wp_enqueue_script('jquery');
        wp_enqueue_script('hwzi-payments', HW_ZOHO_ASSETS_URL . '/admin/js/payments.js', ['jquery'] );
    }

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        add_meta_box(
            'zoho_crm',
            __( 'Zoho CRM', 'hw-zoho' ),
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
            <label class="form-label"><?= __('Reservation ID'); ?></label> <br>
            <input type="text"  class="form-control" value="<?= hwzi_get_quote_zoho_reservation_id($post->ID) ?>" name="zoho_reservation_id" />
            <label class="form-label"><?= __('Contact ID'); ?></label> <br>
            <input type="text" class="form-control" value="<?= hwzi_get_quote_zoho_contact_id($post->ID) ?>" name="zoho_contact_id" />
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

        update_post_meta($post_id, 'zoho_reservation_id', $_POST['zoho_reservation_id']);
        update_post_meta($post_id, 'zoho_contact_id', $_POST['zoho_contact_id']);
    }
}