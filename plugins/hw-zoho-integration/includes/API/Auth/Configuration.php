<?php

namespace HWZoho\API\Auth;

use com\zoho\crm\api\fields\Private1;

class Configuration{

    private static $client_id = "1000.99TJ5ZI7HGM9LMGFY77N39W7X7FHYL";

    private static $secretId = "610f9aefaab5bae3dff7927220a85a29e1e093bb5b";

    private static $token_store_path = HW_ZOHO_PLUGIN_PATH . "/auth/php_sdk_token.txt";

    public static function get_client_id(){

        return self::$client_id;
    }

    public static function get_secret_id(){

        return self::$secretId;
    }

    public static function get_store_path(){

        return self::$token_store_path;
    }
}