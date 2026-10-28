<?php

namespace HWZoho\Admin\Pages;

use HW\Traits\Singleton;
use HWZoho\API\CRM\Importers\RoomBlock as RoomBlockImporter;
use HW\Models\RoomBlock;

class Logs{

    use Singleton;

    public function __construct(){

        add_action( 'admin_menu', [ $this, 'add_menu_page' ]);
    }

    /**
     * register the page
     *
     * @return void
     */
    public function add_menu_page(){

        $logs_hook = add_submenu_page( 'zoho-crm', 'Logs', 'Logs', 'manage_options', 'hwzi-logs', [ $this, 'display_page' ]);
        add_action( 'load-' . $logs_hook, [ $this, 'do_clear_log' ] );
    }

    /**
     * display the page
     */
    public function display_page(){

        $logs = $this->get_log_list();

        hwzi_get_template('admin/pages/logs', ['logs' => $logs]);
    }

    /**
     * return a list of logs
     *
     * @return array
     */
    public function get_log_list(){

        return [
            'default' => [
                'key' => 'default',
                'page_title' => 'Plugin Log',
                'filepath' => HW_ZOHO_PLUGIN_PATH . '/logs/' .  get_blog_details()->domain . '/log.log'
            ],
        ];
    }

    /**
     * clear the log
     *
     * @return void
     */
    public function do_clear_log(){

        if( isset($_POST['action']) && $_POST['action'] == 'hwzi_clear_log' && isset($_POST['log_key'])){
			
            $key = $_POST['log_key'];
            $logs = $this->get_log_list();

            if(isset($logs[$key])){

                $log = $logs[$key];
                if(file_exists($log['filepath'])){
                    
                    file_put_contents($log['filepath'], '');
                }
            }
		}
    }
}