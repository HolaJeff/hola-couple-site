<?php

namespace HW\Helpers\Assets;

class Noty{

    public static function import(){

        wp_enqueue_style('noty', HW_VENDOR_URL . 'noty/noty.css');
        wp_enqueue_script('noty', HW_VENDOR_URL . 'noty/noty.js');
    }
}