<?php

namespace HWZoho\API\Auth;

use com\zoho\api\authenticator\OAuthBuilder;
use com\zoho\api\authenticator\store\FileStore;
use com\zoho\crm\api\InitializeBuilder;
use com\zoho\crm\api\dc\USDataCenter;
use com\zoho\api\logger\LogBuilder;
use com\zoho\api\logger\Levels;
use com\zoho\crm\api\SDKConfigBuilder;
use \HWZoho\API\Auth\Configuration;

class Initializer
{
    public static function initialize($token)
    {
        $logger = (new LogBuilder())
            ->level(Levels::INFO)
            ->filePath( HW_ZOHO_PLUGIN_PATH . "/logs/php_sdk_log.log")
            ->build();
    
        $environment = USDataCenter::PRODUCTION();

        $token = (new OAuthBuilder())
            ->clientId(Configuration::get_client_id())
            ->clientSecret(Configuration::get_secret_id())
            ->grantToken($token)
            ->build();

        $tokenstore = new FileStore( Configuration::get_store_path() );

        $autoRefreshFields = false;

        $pickListValidation = false;

        $enableSSLVerification = true;

        $connectionTimeout = 2;//The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.

        $timeout = 2;//The maximum number of seconds to allow cURL functions to execute.

        $sdkConfig = (new SDKConfigBuilder())
            ->autoRefreshFields($autoRefreshFields)
            ->pickListValidation($pickListValidation)
            ->sslVerification($enableSSLVerification)
            ->connectionTimeout($connectionTimeout)
            ->timeout($timeout)
            ->build();

        $resourcePath = HW_ZOHO_PLUGIN_PATH . "/zoho/resources";

        (new InitializeBuilder())
            ->environment($environment)
            ->token($token)
            ->store($tokenstore)
            ->SDKConfig($sdkConfig)
            ->resourcePath($resourcePath)
            ->logger($logger)
            ->initialize();
    }
}