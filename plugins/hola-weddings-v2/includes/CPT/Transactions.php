<?php

namespace HW\CPT;

use HW\Traits\Singleton;

class Transactions{

    use Singleton;

    public function __construct(){
        
        add_action( 'init',[ $this, 'register_cpt']);
        add_action( 'template_redirect', [$this, 'redirect_single_view']);
        add_action( 'manage_transactions_posts_custom_column', [$this, 'display_columns'], 10, 2);
        add_action( 'restrict_manage_posts', [$this, 'add_filters']);
        add_action( 'pre_get_posts', [$this,'apply_filters'], 10);

        add_filter('manage_transactions_posts_columns', [$this, 'add_columns']);
    }

    /**
     * Register custom post type
     *
     * @return void
     */
    public function register_cpt(){

        register_post_type( 'transactions', [
			'labels' => [
				'name' => __( 'Transactions' ),
				'singular_name' => __( 'Transaction' )
			],
			'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_menu' => 'holaweddings',
            'show_in_rest' => false,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-money-alt',
			'supports' => [ 'title', 'custom-fields', 'revisions', 'author' ]
		]);
    }

    /**
     * redirect the single room blocj view to the homepage
     *
     * @return void
     */
    public function redirect_single_view(){
        
        if(is_singular('transactions')){
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
        [ 'invoice' => __('Invoice', 'hola-weddings') ] +
        [ 'status' => __('Status', 'hola-weddings') ] +
        [ 'gateway' => __('Gateway', 'hola-weddings') ] +
        [ 'transaction_id' => __('Transaction ID', 'hola-weddings') ] +
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
                echo $amount ? hw_format_price($amount, get_field('hw_currency', $post_id)) : '';
                break;
            case 'invoice':
                $invoice_id = get_field('hw_invoice_id', $post_id);
                echo $invoice_id ? sprintf('<a href="%s" target="_blank">#%d - %s</a>', get_edit_post_link($invoice_id), $invoice_id, get_post_field('post_title', $invoice_id)) : '';
                break;
            case 'status':
                $date = get_field('hw_status', $post_id);
                echo $date ? hw_get_transaction_status_label($date) : '';
                break;
            case 'gateway':
                $gateway = get_field('hw_gateway', $post_id);
                echo $gateway ?: '';
                break;
            case 'transaction_id':
                $t_id = get_field('hw_transaction_id', $post_id);
                echo $t_id ?: '';
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

        if($post_type != 'transactions'){
            return;
        }

        $query = new \WP_Query([
            'post_type' => 'invoices',
            'posts_per_page' => -1,
            'order' => 'ASC'
        ]);

        $selected = '';
      
        if(isset($_GET['invoice'])){
            
            $selected = sanitize_text_field($_GET['invoice']);
        }

        ?>
        <select id="quote" name="quote">
            <option value="0">All Invoices</option>
            <option value="-1">Without Invoice</option>
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
        if($pagenow == 'edit.php' && $post_type == 'transactions' && $query->is_main_query()){
  
          // if not allow filtering by workgroup filter
          if(!empty($_GET['invoice'])){
          
            $quote = sanitize_text_field($_GET['invoice']);
            $query->query_vars['meta_query']['relation'] = 'AND';
            
            if($quote != -1){
  
                $query->query_vars['meta_query'][] = [
                    'key'  => 'invoice_id',
                    'value' => $quote
                ];
            }elseif($quote == -1){
                $query->query_vars['meta_query'][] = [
                    'key'  => 'invoice_id',
                    'value' => '',
                    'compare' => ''
                ];
            }
          }
        }
      }
}