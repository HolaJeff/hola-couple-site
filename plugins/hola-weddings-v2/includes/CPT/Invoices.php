<?php

namespace HW\CPT;

use DateTime;
use HW\Models\Invoice;
use HW\Traits\Singleton;

class Invoices{

    use Singleton;

    public function __construct(){
        
        add_action( 'init', [ $this, 'register_cpt']);
        add_action( 'template_redirect', [$this, 'redirect_single_view']);
        add_action( 'manage_invoices_posts_custom_column', [$this, 'display_columns'], 10, 2);
        add_action( 'restrict_manage_posts', [$this, 'add_filters']);
        add_action( 'pre_get_posts', [$this,'apply_filters'], 10);

        add_filter('manage_invoices_posts_columns', [$this, 'add_columns']);
    }

    /**
     * Register custom post type
     *
     * @return void
     */
    public function register_cpt(){

        register_post_type( 'invoices', [
			'labels' => [
				'name' => __( 'Invoices' ),
				'singular_name' => __( 'Invoice' )
			],
			'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-media-document',
			'supports' => [ 'title', 'custom-fields', 'revisions', 'author' ]
		]);
    }

    /**
     * redirect the single room blocj view to the homepage
     *
     * @return void
     */
    public function redirect_single_view(){
        
        if(is_singular('invoices')){
            wp_redirect(home_url(), 301);
        }
    }

    /**
     * filter and add columns
     *
     * @param array $columns
     * @return array
     */
    public function add_columns ($columns) {

        return array_slice($columns, 0, 2, true) +
        [ 'amount' => __('Amount', 'hola-weddings') ] +
        [ 'paid_amount' => __('Paid Amount', 'hola-weddings') ] +
        [ 'due_date' => __('Due Date', 'hola-weddings') ] +
        [ 'quote' => __('Quote', 'hola-weddings') ] +
        [ 'status' => __('Status', 'hola-weddings') ] +
        [ 'type' => __('Type', 'hola-weddings') ] +
        array_slice($columns, 2, count($columns)-2, true);

        return $columns;
    }

    /**
     * display columns
     *
     * @param string $column_key
     * @param int $post_id
     * @return void
     */
    public function display_columns($column_key, $post_id) {
        
        switch($column_key){
            case 'amount':
                $amount = get_field('hw_amount', $post_id);
                echo $amount ? hw_format_price($amount) : '';
                break;
            case 'paid_amount':
                $invoice = hw_get_invoice( $post_id);
                echo hw_format_price($invoice->getPaidAmount());
                break;
            case 'due_date':
                $date = get_field('hw_due_date', $post_id);
                echo $date ? (new DateTime($date))->format('d F Y') : '';
                break;
            case 'quote':
                $quote_id = get_field('hw_reservation_id', $post_id);
                echo $quote_id ? sprintf('<a href="%s" target="_blank">#%d - %s</a>', get_edit_post_link($quote_id), $quote_id, get_post_field('post_title', $quote_id)) : '';
                break;
            case 'status':
                $status = get_field('hw_invoice_status', $post_id);
                echo is_array($status) ? $status['label'] : $status;
                break;
            case 'type':
                $type = get_field('hw_type', $post_id);
                echo is_array($type) ? $type['label'] : $type;
                break;
        }
    }

    /**
     * display filters
     *
     * @return void
     */
    public function add_filters(){

        global $post_type;

        if($post_type != 'invoices'){
            return;
        }

        $query = new \WP_Query([
            'post_type' => 'quote',
            'posts_per_page' => -1,
            'order' => 'ASC'
        ]);

        $selected = '';
      
        if(isset($_GET['quote'])){
            
            $selected = sanitize_text_field($_GET['quote']);
        }

        ?>
        <select id="quote" name="quote">
            <option value="0">All Quotes</option>
            <option value="-1">Without Quote</option>
            <?php if($query->have_posts()): ?>
            <?php foreach($query->posts as $post): ?>
                <option value="<?= $post->ID?>" <?= $selected == $post->ID ? 'selected' : '' ?>>
                    <?= sprintf('#%d - %s', $post->ID, $post->post_title) ?>
                </option>
            <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php
    }

    /**
     * apply filters to main query
     *
     * @param WP_Query $query
     * @return void
     */
    public function apply_filters($query){
      
        global $post_type, $pagenow;
        
        //if we are currently on the edit screen of the post type listings
        if($pagenow == 'edit.php' && $post_type == 'invoices' && $query->is_main_query()){
  
          // if not allow filtering by workgroup filter
          if(!empty($_GET['quote'])){
          
            $quote = sanitize_text_field($_GET['quote']);
            $query->query_vars['meta_query']['relation'] = 'AND';
            
            if($quote != -1){
  
                $query->query_vars['meta_query'][] = [
                    'key'  => 'hw_reservation_id',
                    'value' => $quote
                ];
            }elseif($quote == -1){
                $query->query_vars['meta_query'][] = [
                    'key'  => 'hw_reservation_id',
                    'value' => '',
                    'compare' => ''
                ];
            }
          }
        }
    }
}