<?php

namespace HW\Helpers\Assets;

class BlockUI{

    public static function import(array $dependencies = []){

        wp_enqueue_script('blockui', HW_VENDOR_URL . 'blockui/jquery.blockui.js', array_merge(['jquery'], $dependencies));
    }

    public static function importFrontEnd(array $dependencies = []){

        wp_enqueue_script('blockui', HW_VENDOR_URL . 'blockui/jquery.blockui-fe.js', array_merge(['jquery'], $dependencies));
    }
}