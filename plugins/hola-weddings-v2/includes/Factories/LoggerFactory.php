<?php

namespace HW\Factories;

use HW\Utils\Logger;

class LoggerFactory{

    /**
     * create a new logger
     *
     * @param string $dir
     * @param string $filename
     * @param string $ext
     * @param boolean $write_log
     * @param boolean $print_log
     * @return Logger
     */
    public static function make($dir, $filename = 'log', $ext = 'txt', $write_log = true, $print_log = false){

        return new Logger($dir, $filename, $ext, $write_log, $print_log);
    }

    /**
     * create a new website logger
     *
     * @param string $filename
     * @param string $ext
     * @param boolean $write_log
     * @param boolean $print_log
     * @return Logger
     */
    public static function makeWebsiteLogger( $filename = 'log', $ext = 'txt', $dir = '', $write_log = true, $print_log = false){

        // set always the log path to relative site path in the logs folder
        if(empty($dir)){
            $dir = HW_PLUGIN_PATH . '/logs/';
        }

        // always append the website domain
        $dir .=  wp_parse_url( home_url(), PHP_URL_HOST );

        return self::make($dir, $filename, $ext, $write_log, $print_log);
    }
}