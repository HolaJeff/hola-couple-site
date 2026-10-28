<?php

namespace HW\Helpers;

use HW\Traits\Singleton;

class PluginData{

    use Singleton;
    
    protected $plugin_data;

    /**
     * constructor
     */
    public function __construct(){

        $this->plugin_data = get_plugin_data(HW_PLUGIN_FILE);
    }

    /**
     * return the plugin version
     *
     * @return void
     */
    public function getVersion(){

        return $this->plugin_data['Version'] ?? '';
    }
}