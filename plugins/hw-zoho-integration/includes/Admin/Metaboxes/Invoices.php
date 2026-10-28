<?php

namespace HWZoho\Admin\Metaboxes;

use HWZoho\Traits\Singleton;

class Invoices{

    use Singleton;

    protected $post_type = 'invoices';

    public function __construct(){

        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_invoices', [ $this, 'save' ] );
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
            'invoices_zoho',
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

        $invoice = hw_get_invoice($post->ID);
        $quote_id = $invoice->getQuoteID();
        $payment_id = get_post_meta( $post->ID, 'zoho_payment_id', true );

        hwzi_get_template('admin/metaboxes/invoices/payment', ['post' => $post, 'quote_id' => $quote_id, 'payment_id' => $payment_id]);
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

        if(isset($_POST['zoho_payment_id'])){
            update_post_meta($post_id, 'zoho_payment_id', $_POST['zoho_payment_id']);
        }
    }
}