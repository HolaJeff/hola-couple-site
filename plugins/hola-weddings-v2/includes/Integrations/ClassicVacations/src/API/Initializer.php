<?php

namespace HW\Integrations\ClassicVacations\API;

use HW\Integrations\ClassicVacations\API\Logger;
use HW\Integrations\ClassicVacations\API\Auth\TokenStore;
use HW\Integrations\ClassicVacations\API\Constants;
use HW\Integrations\ClassicVacations\App;

/**
 * This class to initialize Zoho CRM SDK.
 */
class Initializer
{

    private static $initializer;

    private $environment = null;

    private $store = null;

    private $token = null;

    /**
     * This to initialize the SDK.
     */
    public static function initialize()
    {

        $logger = App::getInstance()->getLogger();
        
        try
        {

            self::$initializer = new Initializer();

            $initializer = new Initializer();

            $initializer->environment = Environment::getInstance();

            $initializer->store = $store = TokenStore::getInstance();
            
            $token = $store->getToken();

            self::$initializer = $initializer;

            if ($token != null)
            {
                $token->generateToken();
            }

            $initializer->token = $token;
            
            self::$initializer = $initializer;

            $logger->info(Constants::INITIALIZATION_SUCCESSFUL);

        }catch (\Exception $e){

            $logger->error(Constants::INITIALIZATION_EXCEPTION . $e->getMessage(), self::class);
            throw new \Exception(Constants::INITIALIZATION_EXCEPTION . $e->getMessage());
        }
    }

    /**
     * This method to get Initializer class instance.
     *
     * @return Initializer A Initializer class instance representing the SDK configuration details.
     */
    public static function getInitializer()
    {
        if (empty(self::$initializer))
        {
            Initializer::initialize();
        }

        return self::$initializer;
    }

    /**
     * This is a getter method to get API environment.
     *
     * @return Environment A Environment representing the API environment.
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * This is a getter method to get API environment.
     *
     * @return TokenStore A TokenStore class instance containing the token store information.
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * This is a getter method to get OAuth client application information.
     *
     * @return Token A Token class instance representing the OAuth client application information.
     */
    public function getToken()
    {
        return $this->token;
    }
}