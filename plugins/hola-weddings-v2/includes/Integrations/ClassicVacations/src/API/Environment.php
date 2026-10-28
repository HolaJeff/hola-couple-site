<?php

namespace HW\Integrations\ClassicVacations\API;

use HW\Traits\Singleton;

/**
 * The abstract class represents the properties of Zoho CRM Environment.
 */
class Environment
{
    use Singleton;

    public $url = 'https://connect.classicvacations.com/ResourceServer';

    public $accountUrl = 'https://connect.classicvacations.com/AuthorizationServer/OAuth/Token';

    /**
     * This method to get Zoho CRM API URL.
     * @return string A string representing the Zoho CRM API URL.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * This method to get Zoho CRM Accounts URL.
     * @return string A string representing the accounts URL.
     */
    public function getAccountsUrl()
    {
        return $this->accountUrl;
    }
}