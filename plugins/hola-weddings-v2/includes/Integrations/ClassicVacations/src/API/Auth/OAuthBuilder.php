<?php

namespace HW\Integrations\ClassicVacations\API\Auth;

use Exception;

class OAuthBuilder
{
    private $clientID;

    private $clientSecret;

    private $redirectURL;

    private $refreshToken;

    private $grantToken;

    private $accessToken;

    public function clientId(string $clientID)
    {
        $this->clientID = $clientID;

        return $this;
    }

    public function clientSecret(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function redirectURL(string $redirectURL)
    {
        $this->redirectURL = $redirectURL;

        return $this;
    }

    public function refreshToken(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function grantToken(string $grantToken)
    {
        $this->grantToken = $grantToken;

        return $this;
    }

    public function accessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function build()
    {
        if($this->grantToken == null && $this->refreshToken == null && $this->accessToken == null)
        {
            throw new Exception( __( 'OAuthBuilder: Missing mandatory properties'));
        }

        if ($this->grantToken != null || $this->refreshToken != null)
        {
            if ($this->clientID == null && $this->clientSecret == null)
            {
                 throw new Exception( __( 'OAuthBuilder: Missing clientID and clientSecret properties'));
            }
        }
        
        $class = new \ReflectionClass(OAuthToken::class);

        $constructor = $class->getConstructor();

        $constructor->setAccessible(true);

        $object = $class->newInstanceWithoutConstructor();

        $constructor->invoke($object, $this->clientID, $this->clientSecret, $this->grantToken, $this->refreshToken, $this->redirectURL, $this->accessToken);

        return $object;
    }
}