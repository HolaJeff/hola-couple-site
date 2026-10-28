<?php

namespace HW\Admin\Metaboxes;

use HW\Models\CRM\BillingInformation;
use HW\Traits\Singleton;
use HW\Utils\CardBuilder;

class Transactions{

    use Singleton;

    protected $post_type = 'transactions';

    public function __construct(){
        
        add_action( 'add_meta_boxes', [ $this, 'register_metaboxes' ] );
        add_action( 'save_post_transactions', [ $this, 'save' ], 10, 3 );
        add_action('admin_init', [$this, 'handle_admin_actions']);
    }

    /**
     * Register metaboxes
     *
     * @return void
     */
    public function register_metaboxes(){

        $user = wp_get_current_user();

        // invoice
        add_meta_box(
            'transactions',
            __( 'Transactions', 'hola-weddings' ),
            [ $this, 'render_transactions' ],
            'invoices',
            'advanced',
            'low'
        );

        // transactions
        add_meta_box(
            'billing_information',
            __( 'Billing Information', 'hola-weddings' ),
            [ $this, 'render_billing_information' ],
            'transactions',
            'advanced',
            'low'
        );

        add_meta_box(
            'card',
            __( 'Card', 'hola-weddings' ),
            [ $this, 'render_card' ],
            'transactions',
            'advanced',
            'low'
        );

        add_meta_box(
            'signature',
            __( 'Signature', 'hola-weddings' ),
            [ $this, 'render_signature' ],
            'transactions',
            'side',
            'low'
        );

        add_meta_box(
            'failed_error',
            __( 'Notes', 'hola-weddings' ),
            [ $this, 'render_notes' ],
            'transactions',
            'side',
            'low'
        );

        add_meta_box(
            'transaction_pdf',
            __( 'UCC Form', 'hola-weddings' ),
            [ $this, 'render_pdf' ],
            'transactions',
            'side',
            'default'
        );
    }

    /**
     * render the signature
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
     * render notes
     *
     * @return void
     */
    public function render_notes(){

        global $post;

        $model = hw_get_transaction($post->ID);
        $notes = $model->getNotes();
        $notes = array_reverse($notes, true);
        
        ?>
        <div class="form-group">
            <?php foreach($notes as $id => $note): ?>
                <div id="<?= $id ?>" class="note mb-3">
                    <div class="message-wrapper rounded border bg-light p-3 mb-1">
                        <?= $note['message'] ?>
                    </div>
                    <div class="meta">
                        <small class="author text-muted">by <span class="text-dark fw-bold"><?= $note['author_name'] ?></span></small>
                        <?php if($note['timestamp']): ?>
                            •
                            <small class="author text-muted"><?= date('d F Y - H:m:s', $note['timestamp']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render dynamic prices
     *
     * @return void
     */
    public function render_transactions(){

        global $post;

        $query = new \WP_Query([
            'post_type' => 'transactions',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'hw_invoice_id',
                    'value' => $post->ID
                ]
            ]
        ]);

        $columns = hw_get_transaction_columns(); ?>
        <div class="my-3">
            <a class="btn btn-primary" href="/wp-admin/post-new.php?post_type=transactions" target="_blank"><?= __('Add Transaction') ?></a>
        </div>
        <?php if($query->have_posts()): ?>
            <table class="wp-list-table widefat fixed striped table-view-list posts">
                <thead>
                    <tr>
                        <?php foreach($columns as $slug => $column): ?>
                            <th><?= $column ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($query->posts as $post): ?>
                        <tr>
                            <?php foreach($columns as $slug => $column): ?>
                                <td><?= hw_display_transaction_columns($post, $slug) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?= __('There are no transactions for this invoice.', 'hola-weddings') ?></p>
        <?php endif;
    }

    /**
     * render billing information
     *
     * @return void
     */
    public function render_billing_information(){

        global $post;

        $data = json_decode( get_post_meta($post->ID, 'hw_billing_information', true), true );
        $billing_information = new BillingInformation($data);

        hw_get_template('template-parts/admin/partials/forms/billing-form', ['billing_information' => $billing_information, 'readonly' => true]);
    }

    /**
     * render card
     *
     * @return void
     */
    public function render_card(){

        global $post;

        $data = json_decode( get_post_meta($post->ID, 'hw_card', true), true );
        $card = CardBuilder::build($data['number'], $data['exp_month'], $data['exp_year'], '', $data['type'], $data['holder']);

        hw_get_template('template-parts/admin/partials/forms/card-form', [ 'card' => $card, 'readonly' => true, 'hide_ccv' => true, 'hide_number' => true]);
    }

    /**
     * render PDF
     *
     * @return void
     */
    public function render_pdf(){
        
        global $post;
        ?>
            <a class="btn btn-primary d-block" href="<?= add_query_arg('hw_action', 'hw_transaction_ucc_pdf', get_edit_post_link()) ?>" target="_blank">
                <span class="dashicons dashicons-media-document align-text-bottom"></span>
                <?= __('View PDF', 'hola-weddings') ?>
            </a>
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

        if(isset($_POST['hw_status']) && $_POST['hw_status'] != null){

            update_post_meta($post_id, 'hw_status', $_POST['hw_status']);
        }
    }

    /**
     * Handle admin actions
     *
     * @return void
     */
    public function handle_admin_actions(){

        global $post_type, $pagenow;

        if(isset($_REQUEST['hw_action'])){

            switch($_REQUEST['hw_action']){
                case 'hw_transaction_ucc_pdf':
                    if($pagenow == 'post.php' && get_post_type( $_GET['post'] ) == 'transactions'){
                        
                        $quotePdf = new \HW\PDF\AdminTransactionUCC($_GET['post']);
                        $quotePdf->output('Reservation_#'.$_GET['post'].'.pdf');
                        die;
                    }
                    break;
            }
        }
    }
}