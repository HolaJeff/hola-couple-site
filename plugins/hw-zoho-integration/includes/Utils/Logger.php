<?php

namespace HWZoho\Utils;

use HW\Utils\Logger as HWLogger;

class Logger extends HWLogger{

    public  $log_level = 'debug';

    /**
     * Constructor
     *
     * @param string $dir
     * @param string $filename
     * @param string $ext
     */
    public function __construct($dir = '', $filename = 'plugin_log', $ext = 'txt', $write_log = true, $print_log = false)
    {

        if(empty($dir)){
            $dir = HW_ZOHO_PLUGIN_PATH . '/logs';
        }

        parent::__construct($dir, $filename, $ext, $write_log, $print_log);
    }
}