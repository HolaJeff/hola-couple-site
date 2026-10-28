<?php

namespace HWZoho\API\Auth;
use com\zoho\api\authenticator\OAuthBuilder;
use com\zoho\api\authenticator\store\FileStore;
use com\zoho\api\logger\Levels;
use com\zoho\api\logger\LogBuilder;
use com\zoho\crm\api\dc\USDataCenter;
use \HWZoho\API\Auth\Configuration;
use com\zoho\crm\api\InitializeBuilder;
use Exception;

class OAuth{

    /**
     * initialize OAuth
     *
     * @return void
     */
    public static function initialize(){

        $stored_token = self::get_last_token();

        if($stored_token == false){
            
            throw new \Exception(__('OAuth token doesn\'t exist. Use Grant Token to inizialize Auth process.'));
        }

        $resourcePath = HW_ZOHO_PLUGIN_PATH . "/zoho/resources";
        $logdir = HW_ZOHO_PLUGIN_PATH . "/logs/";
        $logfilePath = $logdir . 'php_sdk_log.log';

        if(!is_dir($resourcePath)){

            mkdir($resourcePath, 0777, true);
        }

        if(!is_dir($logdir)){

            mkdir($logdir, 0777, true);
        }

        $logger = (new LogBuilder())
        ->level(Levels::INFO)
        ->filePath( $logfilePath )
        ->build();

        $token = (new OAuthBuilder())
        ->clientId(Configuration::get_client_id())
        ->clientSecret(Configuration::get_secret_id())
        ->refreshToken($stored_token->getRefreshToken())
        ->build();

        $environment = USDataCenter::PRODUCTION();
        (new InitializeBuilder())
            ->environment($environment)
            ->token($token)
            ->resourcePath($resourcePath)
            ->logger($logger)
            ->initialize();
            
    }

    /**
     * return last stored token
     *
     * @return \com\zoho\api\authenticator\OAuthToken
     */
    public static function get_last_token(){

        $tokenstore = new FileStore( HW_ZOHO_PLUGIN_PATH . "/auth/php_sdk_token.txt");
        $tokens = $tokenstore->getTokens();
        return end($tokens);
    }
}