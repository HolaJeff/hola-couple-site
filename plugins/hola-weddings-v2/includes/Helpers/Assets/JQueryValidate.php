<?php

namespace HW\Helpers\Assets;

class JQueryValidate{

    public static function import(){

        wp_enqueue_script('jquery-validate', HW_VENDOR_URL . 'jqueryvalidate/jquery.validate.min.js', ['jquery']);
    }
}