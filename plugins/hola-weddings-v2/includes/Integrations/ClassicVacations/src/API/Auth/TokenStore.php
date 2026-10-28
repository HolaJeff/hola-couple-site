<?php

namespace HW\Integrations\ClassicVacations\API\Auth;

use HW\Traits\Singleton;

class TokenStore{

    use Singleton;

    /**
     * create a token from data
     *
     * @param array $data
     * @return OAuthToken
     */
    public function createToken(array $data = null){

        $class = new \ReflectionClass(OAuthToken::class);

        $oauthToken = $class->newInstanceWithoutConstructor();

        if(!empty($data)){
            $this->setMergeData($oauthToken, $data);
        }

        return $oauthToken;
    }

    /**
     * return the token
     *
     * @return OAuthToken
     */
    public function getToken(){

        $data =  get_network_option(null, 'hw_classic_vacation_token', []);

        return $this->createToken($data);
    }

    /**
     * store the token
     *
     * @param OAuthToken $token
     * @return void
     */
    public function saveToken(OAuthToken $token){
        update_network_option(null, 'hw_classic_vacation_token', self::serialize($token));
    }

    /**
     * serialize the token
     *
     * @param OAuthToken $token
     * @return array
     */
    public function serialize(OAuthToken $token){
        return [
            'access_token' => $token->getAccessToken(),
            'token_type' => 'bearer',
            'expires_in' => $token->getExpiresIn(),
            'refresh_token' => $token->getRefreshToken(),
            'grant_token' => $token->getGrantToken(),
        ];
    }

    /**
     * delete token
     *
     * @return void
     */
    public function deleteToken(){

        delete_network_option(null, 'hw_classic_vacation_token');
    }

    /**
     * return data value
     *
     * @param String $value
     * @return void
     */
    private function getData($value)
    {
        return ($value != null && !empty($value) && strlen($value) > 0) ? $value : null;
    }

    /**
     * merge the data
     *
     * @param OAuthToken $oauthToken
     * @param array $nextRecord
     * @return void
     */
    private function setMergeData(OAuthToken $oauthToken, array $record)
    {
        if ($oauthToken->getClientId() == null)
        {
            $oauthToken->setClientId(hw_cv_get_client_id());
        }

        if ($oauthToken->getClientSecret() == null)
        {
            $oauthToken->setClientSecret(hw_cv_get_client_secret());
        }

        if ($oauthToken->getRefreshToken() == null)
        {
            $oauthToken->setRefreshToken($this->getData($record['refresh_token']));
        }

        if ($oauthToken->getAccessToken() == null)
        {
            $oauthToken->setAccessToken($this->getData($record['access_token']));
        }
        
        if ($oauthToken->getGrantToken() == null)
        {
            $oauthToken->setGrantToken($this->getData($record['grant_token']));
        }

        if ($oauthToken->getExpiresIn() == null)
        {
            $expiresIn = $this->getData($record['expires_in']);
            
			if ($expiresIn != null)
            {
                $oauthToken->setExpiresIn(StrVal($expiresIn));
            }
		}

        if ($oauthToken->getRedirectURL() == null)
        {
            $oauthToken->setRedirectURL(hw_cv_get_redirect_uri());
        }
    }
}