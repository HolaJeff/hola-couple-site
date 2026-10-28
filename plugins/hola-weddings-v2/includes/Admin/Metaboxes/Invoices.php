<?php

namespace HW\Admin\Metaboxes;

use HW\Traits\Singleton;

class Invoices{

    use Singleton;

    protected $post_type = 'invoices';

    public function __construct(){
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        //add_action( 'save_post_invoices', [ $this, 'save' ], 10, 3 );
    }

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        add_meta_box(
            'invoices',
            __( 'Invoices', 'hola-weddings' ),
            [ $this, 'render_invoices' ],
            'quote',
            'advanced',
            'low'
        );

        add_meta_box(
            'invoice_summary',
            __( 'Summary', 'hola-weddings' ),
            [ $this, 'render_summary' ],
            'invoices',
            'side',
            'low'
        );
    }

    /**
     * render the summary
     *
     * @return void
     */
    public function render_summary(){

        global $post;

        $invoice = hw_get_invoice($post->ID);
        ?>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <tbody>
                <tr>
                    <th><?= __('Total Amount', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($invoice->getAmount(), $invoice->getCurrency()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Paid Amount', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($invoice->getPaidAmount(), $invoice->getCurrency()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Due Amount', 'hola-weddings') ?></th>
                    <td class="text-end"><?= hw_format_price($invoice->getDueAmount(), $invoice->getCurrency()) ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render dynamic prices
     *
     * @return void
     */
    public function render_invoices(){

        global $post;

        $query = new \WP_Query([
            'post_type' => 'invoices',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'hw_reservation_id',
                    'value' => $post->ID
                ]
            ]
        ]);

        ?>
        <div class="my-3">
            <a class="btn btn-primary" href="/wp-admin/post-new.php?post_type=invoices&invoice_id=<?= $post->ID ?>" target="_blank">
                <span class="dashicons dashicons-plus"></span>
                <?= __('Add Invoice') ?>
            </a>
            <?= do_action('hw_quote_invoices_metabox_actions', $post->ID) ?>
        </div>
        <div id="quote-invoices-wrapper">
            <?= hw_get_template('templates/admin/metaboxes/quote/invoices-table', ['query' => $query]) ?>
        </div>
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
    }
}