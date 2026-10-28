<?php

namespace HWZoho\Admin\Pages;

use HW\Traits\Singleton;
use HWZoho\API\CRM\Importers\RoomBlock as RoomBlockImporter;
use HW\Models\RoomBlock;

class ImportRoomBlock{

    use Singleton;

    public function __construct(){

        add_action( 'admin_menu', [ $this, 'add_menu_page' ]);
        add_action( 'wp_ajax_hwzi_import_room_block', [$this, 'handle_import']);
    }

    /**
     * register the page
     *
     * @return void
     */
    public function add_menu_page(){

        $hook = add_submenu_page( 'zoho-crm', 'Import Room Block from Zoho CRM', 'Import Room Block', 'manage_options', 'zoho-crm-import-room-block', [$this, 'display_page']);
    }

    /**
     * display the page
     */
    public function display_page(){

        hwzi_get_template('admin/pages/import-room-block');
    }

    /**
     * handle import
     *
     * @return json
     */
    public function handle_import(){

        if(!isset($_POST['id']) || empty(trim($_POST['id']))){

            wp_send_json(['success' => false, 'message' => 'Invalid ID'] );
        }

        $id = $_POST['id'];
        $importer = new RoomBlockImporter();

        $model = $importer->import($id);

        if(is_wp_error($model)){

            wp_send_json(['success' => false, 'message' => $model->get_error_message()] );
        }

        wp_send_json(['success' => true, 'message' => sprintf('Room block created successfully. <a href="%s" target="_blank">Edit</a>', $model->getEditLink())], JSON_HEX_QUOT);
    }
}