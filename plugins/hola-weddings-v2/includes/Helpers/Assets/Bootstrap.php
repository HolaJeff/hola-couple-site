<?php

namespace HW\Helpers\Assets;

class Bootstrap{

    public static function import(){

        wp_enqueue_style( 'hw-bootstrap', HW_VENDOR_URL . 'bootstrap/bootstrap.min.css' );
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'hw-bootstrap', HW_VENDOR_URL . 'bootstrap/bootstrap.bundle.min.js', ['jquery'] );
    }
}