<?php

namespace HW\Admin\Metaboxes;

use HW\Traits\Singleton;
use HW\Utils\Card;
use HW\Utils\CardBuilder;

class Quote{

    use Singleton;

    public function __construct(){
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_quote', [ $this, 'save' ], 10, 3 );
        add_action( 'admin_enqueue_scripts', [$this,'enqueue_scripts']);
    }

    /**
     * enqueue specific scripts
     *
     * @return void
     */
    public function enqueue_scripts(){

        global $current_screen, $post_type;

        if($current_screen->base == 'post' && $post_type == 'quote'){

            wp_enqueue_style('hw-jquery-ui', '//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.min.css');

            wp_enqueue_script('hw-jquery-ui', 'https://code.jquery.com/ui/1.13.1/jquery-ui.min.js', ['jquery']);
            wp_enqueue_script('hw-quote', HW_ASSETS_URL . 'js/admin/quote.js', ['hw-jquery-ui']);
        }
    }

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        global $post;

        $quote = hw_get_quote($post->ID);
        $user = wp_get_current_user();

        add_meta_box(
            'reservation_details',
            __( 'Reservation Details', 'hola-weddings' ),
            [ $this, 'render_reservation_details' ],
            'quote',
            'advanced',
            'low'
        );

        add_meta_box(
            'guests',
            __( 'Guests Details', 'hola-weddings' ),
            [ $this, 'render_guests' ],
            'quote',
            'advanced',
            'low'
        );

        add_meta_box(
            'customer_information',
            __( 'Customer Information', 'hola-weddings' ),
            [ $this, 'render_customer_information' ],
            'quote',
            'advanced',
            'default'
        );

        if(( current_user_can('administrator') || in_array('quote-manager', $user->roles) )){
            
            add_meta_box(
                'payment_details',
                __( 'Payment Details', 'hola-weddings' ),
                [ $this, 'render_payment_details' ],
                'quote',
                'advanced',
                'default'
            );
        }

        add_meta_box(
            'room_rates',
            __( 'Room Rates', 'hola-weddings' ),
            [ $this, 'render_room_rates' ],
            'quote',
            'advanced',
            'low'
        );

        if(!empty($quote->getAdditionalNotes())){

            add_meta_box(
                'additional_notes',
                __( 'Additional Notes', 'hola-weddings' ),
                [ $this, 'render_additional_notes' ],
                'quote',
                'advanced',
                'low'
            );
        }

        /**
         * Side
         */

        add_meta_box(
            'quote_summary',
            __( 'Summary', 'hola-weddings' ),
            [ $this, 'render_summary' ],
            'quote',
            'side',
            'default'
        );

        add_meta_box(
            'quote_status',
            __( 'Status', 'hola-weddings' ),
            [ $this, 'render_status' ],
            'quote',
            'side',
            'default'
        );

        add_meta_box(
            'quote_pdf',
            __( 'PDF', 'hola-weddings' ),
            [ $this, 'render_pdf' ],
            'quote',
            'side',
            'default'
        );

        add_meta_box(
            'signature',
            __( 'Signature', 'hola-weddings' ),
            [ $this, 'render_signature' ],
            'quote',
            'side',
            'default'
        );
    }

    /**
     * render additional notes
     *
     * @return void
     */
    public function render_additional_notes(){

        global $post;

        $quote = hw_get_quote($post->ID);
        ?>
        <textarea class="form-control" cols="3" name="data[hw_additional_notes]"><?= $quote->getAdditionalNotes() ?></textarea>
        <?php
    }

    /**
     * render room details
     *
     * @return void
     */
    public function render_reservation_details(){

        global $post;

        $quote = hw_get_quote($post->ID);
        hw_get_template('templates/admin/metaboxes/quote/reservation-details', ['quote' => $quote]);
    }

    /**
     * render the guests
     *
     * @return void
     */
    public function render_guests(){

        global $post;

        $quote = hw_get_quote($post->ID);
        $guests = $quote->getGuests();
        hw_get_template('templates/admin/metaboxes/quote/guests', ['guests' => $guests]);
    }

    /**
     * Customers meta box
     *
     * @return void
     */
    public function render_customer_information(){
        
        global $post;

        $quote = hw_get_quote($post->ID);
        
        hw_get_template('templates/admin/metaboxes/quote/customer-information', ['quote' => $quote]);
    }

    /**
     * payment details
     *
     * @return void
     */
    public function render_payment_details(){

        global $post;

        $quote = hw_get_quote($post->ID);

        hw_get_template('templates/admin/metaboxes/quote/payment', ['quote' => $quote]);
    }

    /**
     * render signature
     *
     * @return void
     */
    public function render_signature(){

        global $post;

        $signature = get_post_meta($post->ID, 'hw_signature', true);

        if($signature): ?>
            <div class="p-3">
                <img src="<?= $signature ?>" style="max-width:100%" />
            </div>
        <?php else:
            echo 'No Signature';
        endif;
    }

    /**
     * render PDF
     *
     * @return void
     */
    public function render_pdf(){
        
        global $post;
        ?>
            <a class="btn btn-primary d-block" href="<?= add_query_arg('hw_action', 'hw_quote_pdf', get_edit_post_link()) ?>" target="_blank">
                <span class="dashicons dashicons-media-document align-text-bottom"></span>
                <?= __('View PDF', 'hola-weddings') ?>
            </a>
        <?php
    }

    /**
     * render status
     *
     * @return void
     */
    public function render_status(){

        global $post;
        
        $status = get_post_meta($post->ID, 'hw_quote_status', true);
        $statuses = hw_get_quote_statuses();
        ?>
        <select class="form-select" name="data[hw_quote_status]">
            <?php foreach($statuses as $slug => $name): ?>
                <option <?= $slug == $status ? 'selected' : '' ?> value="<?= $slug ?>"><?= $name; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * render rates per day
     *
     * @return void
     */
    public function render_room_rates(){

        global $post;

        $quote = hw_get_quote($post->ID);

        hw_get_template('templates/admin/metaboxes/quote/rates', ['quote' => $quote]);
    }

    /**
     * render the summary
     *
     * @return void
     */
    public function render_summary(){

        global $post;

        $quote = hw_get_quote($post->ID);
        ?>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <tbody>
                <tr>
                    <th><?= __('Room Total', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($quote->getRoomTotal()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Insurance Total', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($quote->getInsuranceTotal()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Service Fee', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($quote->getServiceFee()) ?></td>
                    <tr>
                    <th><?= __('Total Amount', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($quote->getTotal()) ?></td>
                </tr>
                </tr>
            </tbody>
        </table>
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
    public function save( $post_id, $post, $update ){

        if(!isset($_POST['data']) || empty($_POST['data'])){
            return;
        }

        $data = $_POST['data'];
        $quote = hw_get_quote($post_id);

        if(!empty($data['card'])){

            $cardData = $data['card'];
            $card = CardBuilder::build($cardData['number'], $cardData['month'], $cardData['year'], $cardData['ccv'], $cardData['type'], $cardData['holder']);
            
            unset($data['card']);

            $quote->setCardData($card);
        }

        $quote->setProperties($data);
    }
}