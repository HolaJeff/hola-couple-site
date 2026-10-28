<?php

namespace HWZoho\Admin;

use HWZoho\Traits\Singleton;

class Columns{

    use Singleton;

    public function __construct()
    {
        add_action('manage_quote_posts_custom_column', [$this, 'display_quote_columns'], 11, 2);
        add_action('manage_room_blocks_posts_custom_column', [$this, 'display_room_block_columns'], 11, 2);
        add_action('manage_invoices_posts_custom_column', [$this, 'display_invoices_columns'], 11, 2);

        add_filter('manage_quote_posts_columns', [$this, 'add_quote_columns'], 11);
        add_filter('manage_room_blocks_posts_columns', [$this, 'add_room_block_columns'], 11);
        add_filter('manage_invoices_posts_columns', [$this, 'add_invoice_columns'], 11);
    }

    /**
     * filter and add quote columns
     *
     * @param array $columns
     * @return array
     */
    public function add_quote_columns ($columns) {

        return array_slice($columns, 0, 8, true) +
        [ 'zoho_id' => __('Zoho Reservation ID', 'hw-zoho') ] +
        array_slice($columns, 8, count($columns)-2, true);
    }

    /**
     * display quote columns
     *
     * @param string $column_key
     * @param int $post_id
     * @return void
     */
    public function display_quote_columns($column_key, $post_id) {
        
        switch($column_key){
            case 'zoho_id':
                $id = get_field('zoho_reservation_id', $post_id);
                echo $id ?: '';
                break;
        }
    }

    /**
     * filter and add columns
     *
     * @param array $columns
     * @return array
     */
    public function add_room_block_columns ($columns) {

        return array_slice($columns, 0, 3, true) +
        [ 'zoho_id' => __('Zoho ID', 'hw-zoho') ] +
        array_slice($columns, 3, count($columns)-2, true);
    }

    /**
     * display columns
     *
     * @param string $column_key
     * @param int $post_id
     * @return void
     */
    public function display_room_block_columns($column_key, $post_id) {
        
        switch($column_key){
            case 'zoho_id':
                $zoho_id = get_field( 'hwzi_room_block_id', $post_id );
                
                echo $zoho_id ?: '';
                break;
        }
    }

    /**
     * filter and add columns
     *
     * @param array $columns
     * @return array
     */
    public function add_invoice_columns ($columns) {

        return array_slice($columns, 0, count($columns)-2, true) +
        [ 'zoho_payment_id' => __('Zoho Payment ID', 'hola-weddings') ] +
        array_slice($columns, 2, count($columns)-1, true);

        return $columns;
    }

    /**
     * display columns
     *
     * @param string $column_key
     * @param int $post_id
     * @return void
     */
    public function display_invoices_columns($column_key, $post_id) {
        
        switch($column_key){
            case 'zoho_payment_id':
                $id = get_field('zoho_payment_id', $post_id);
                echo $id ?: '';
                break;
        }
    }
}