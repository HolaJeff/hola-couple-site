<?php

namespace HWZoho\Admin\Pages;

use DateTime;
use HW\Helpers\Assets\Noty;
use HW\Traits\Singleton;

class MissingZohoIDTool{

    use Singleton;

    public function __construct(){

        add_action( 'network_admin_menu', [ $this, 'add_network_menu' ]);
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 10);
        add_action( 'wp_ajax_hwzi_get_missing_zoho_id_by_resource', [$this, 'get_missing_zoho_id_by_resource']);
        add_action( 'wp_ajax_hwzi_update_missing_zoho_id_by_resource', [$this, 'update_missing_zoho_id_by_resource']);
    }

    /**
     * enqueue style
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts($hook){
  
        if( 'zoho-crm_page_zoho-missing-zoho-id' == $hook ){

            Noty::import();

            wp_enqueue_style( 'hwzi-jquery-ui' );

            wp_enqueue_script( 'hwzi-jquery-ui' );
            wp_enqueue_script( 'hwzi-missing-zoho-id-tool', HW_ZOHO_ADMIN_ASSETS_URL . '/js/missing-zoho-id-tool.js', ['jquery'] );
        }
    }

    /**
     * Add menu page to network menu 
     *
     * @return void
     */
    public function add_network_menu(){

        add_submenu_page( 'zoho-options', __( 'Missing Zoho ID', 'hw-zoho' ), __( 'Missing Zoho ID', 'hw-zoho' ), 'manage_options', 'zoho-missing-zoho-id', [ $this, 'display' ] );
    }

    /**
     * display import wedding page
     *
     * @return void
     */
    public function display(){

        hwzi_get_template('admin/pages/missing-zoho-id-tool');
    }

    /**
     * search resource with missing zoho id
     *
     * @return json
     */
    public function get_missing_zoho_id_by_resource(){

        global $wpdb;

        if(!isset($_GET['resource']) || empty($_GET['resource'])){

            wp_send_json(['success' => false, 'message' => 'Missing resource']);
        }

        $html = '';
        $resource = $_GET['resource'];
        $statuses = $_GET['status'] ?? [ 'publish' ];
        $wedding_date_input = $_GET['wedding_date'] ?? '';
       
        if(!in_array($resource, ['quotes', 'invoices', 'room-blocks'])){

            wp_send_json(['success' => false, 'message' => 'Invalid resource']);
        }

        $wedding_date_filter = !empty($wedding_date_input) ? new DateTime($wedding_date_input) : DateTime::createFromFormat('Y-m-d', 'NOW');
        $resource_field_key = $this->get_field_by_resource($resource);
        $post_type = $this->get_post_type_by_resource($resource);

        // get sites
        $sites = get_sites([
            'number' => 9999
        ]);

        $html_results = [];

        foreach($sites as $site){

            switch_to_blog($site->blog_id);

            $site_wedding_date = get_option('hw_date');

            // if the site has no wedding date set continue
            if(empty($site_wedding_date)){
                
                continue;
            }

            $site_wedding_date = new DateTime($site_wedding_date);

            // skip weddings which date is earlier than the filter date
            if($site_wedding_date < $wedding_date_filter){

                continue;
            }

            // build status clause
            $status_clauses = [];
            foreach($statuses as $status){

                $status_clauses[] = "{$wpdb->posts}.post_status = '{$status}'";
            }
            $status_clause = implode(' OR ', $status_clauses);

            $query = "SELECT {$wpdb->posts}.*
			    FROM {$wpdb->posts} 
                LEFT JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '{$resource_field_key}' ) 
                LEFT JOIN {$wpdb->postmeta} AS mt1 ON ( {$wpdb->posts}.ID = mt1.post_id )
			    WHERE 
                ( 
                    {$wpdb->postmeta}.post_id IS NULL 
                    OR 
                    ( mt1.meta_key = '{$resource_field_key}' AND mt1.meta_value = '' ) 
                    OR 
                    ( mt1.meta_key = '{$resource_field_key}' AND mt1.meta_value IS NULL )
                ) 
                AND {$wpdb->posts}.post_type = '{$post_type}' 
                AND (( {$status_clause} ))
                GROUP BY {$wpdb->posts}.ID
                ORDER BY {$wpdb->posts}.post_date DESC";

            $results = $wpdb->get_results( $query );

            ob_start();

            if(!empty($results)): 
                ob_start();
                ?>
                <div class="p-3 mb-3 bg-white rounded border">
                    <h3><?= $site->domain ?></h3>
                    <table class="table table-striped border rounded">
                        <thead>
                            <tr>
                                <th><?= __('Post Title', 'hw-zoho') ?></th>
                                <th><?= __('Post Status', 'hw-zoho') ?></th>
                                <th><?= __('ID', 'hw-zoho') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $row): 
                                $post_id = $row->ID;
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= get_edit_post_link($post_id) ?>" target="_blank">
                                            <?= get_the_title($post_id) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= get_post_status($post_id) ?>
                                    </td>
                                    <td>
                                        <?= $post_id ?>
                                    </td>
                                    <td>
                                        <form class="update-zoho-id-form">
                                            <input type="hidden" name="action" value="hwzi_update_missing_zoho_id_by_resource" />
                                            <input type="hidden" name="resource" value="<?= $resource ?>" />
                                            <input type="hidden" name="blog_id" value="<?= $site->blog_id ?>" />
                                            <input type="hidden" name="post_id" value="<?= $post_id ?>" />
                                            <div class="input-group">
                                                <input type="text" name="zoho_id" class="form-control d-inline-block" placeholder="<?= __('Insert Zoho Id here') ?>" />
                                                <button class="btn btn-outline-primary" type="submit"><?= __('Save', 'hw-zoho') ?></button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php 
                $html_results[] = ob_get_clean();
            endif; 

        }

        $html = !empty($html_results) ? implode('', $html_results) : __('No Results', 'hw-zoho');

        restore_current_blog();

        wp_send_json(['success' => true, 'html' => $html]);
    }

    /**
     * update a resource
     *
     * @return void
     */
    public function update_missing_zoho_id_by_resource(){

        if(!isset($_POST['resource']) || empty($_POST['resource'])){

            wp_send_json(['success' => false, 'message' => 'Missing resource']);
        }

        if(!isset($_POST['post_id']) || empty($_POST['post_id'])){

            wp_send_json(['success' => false, 'message' => 'Missing post id']);
        }

        if(!isset($_POST['blog_id']) || empty($_POST['blog_id'])){

            wp_send_json(['success' => false, 'message' => 'Missing blog id']);
        }

        if(!isset($_POST['zoho_id']) || empty($_POST['zoho_id'])){

            wp_send_json(['success' => false, 'message' => 'Missing zoho id']);
        }

        $resource = $_POST['resource'];
        $post_id = $_POST['post_id'];
        $zoho_id = $_POST['zoho_id'];
        $blog_id = $_POST['blog_id'];

        if(!in_array( $resource, ['quotes', 'invoices', 'room-blocks'])){

            wp_send_json(['success' => false, 'message' => 'Invalid resource']);
        }

        $field_to_upate = $this->get_field_by_resource($resource);

        switch_to_blog($blog_id);

        if( false == update_post_meta($post_id, $field_to_upate, $zoho_id)){

            wp_send_json(['success' => true, 'message' => 'Something went wrong while saving the value']);
        }

        restore_current_blog();

        wp_send_json(['success' => true, 'message' => 'Value saved correctly!']);
    }

    /**
     * return the field corresponding to the resource
     *
     * @param string $resource
     * @return string
     */
    protected function get_field_by_resource( $resource ){

        switch($resource){
            case 'quotes':
                return 'zoho_reservation_id';
                break;
            case 'invoices':
                return 'zoho_payment_id';
                break;
            case 'room-blocks':
                return 'hwzi_room_block_id';
                break;
        }
    }

    /**
     * return the post type corresponding to the resource
     *
     * @param string $resource
     * @return string
     */
    protected function get_post_type_by_resource( $resource ){

        switch($resource){
            case 'quotes':
                return 'quote';
                break;
            case 'invoices':
                return 'invoices';
                break;
            case 'room-blocks':
                return 'room_blocks';
                break;
        }
    }
}