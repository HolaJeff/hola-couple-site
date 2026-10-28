<?php
namespace HW\Integrations\ClassicVacations\API\Auth;

use HW\Integrations\ClassicVacations\API\Auth\TokenStore;
use HW\Integrations\ClassicVacations\API\Connector;
use HW\Integrations\ClassicVacations\API\Logger;
use HW\Integrations\ClassicVacations\API\Constants;
use HW\Integrations\ClassicVacations\API\Environment;
use Exception;
use HW\Integrations\ClassicVacations\API\HTTPConnector;
use HW\Integrations\ClassicVacations\App;

/**
 * This class gets the tokens and checks the expiry time.
 * @template T
 */
class OAuthToken
{
    private $clientID = null;

    private $clientSecret = null;

    private $redirectURL = null;

    private $grantToken = null;

    private $refreshToken = null;

    private $accessToken = null;

    private $expiresIn = null;

    private $scope = null;

    /**
     * Creates an OAuthToken class instance with the specified parameters.
     * 
     * @param string $clientID A string containing the OAuth client id.
     * @param string $clientSecret A string containing the OAuth client secret.
     * @param string $grantToken A string containing the GRANT token.
     * @param string $refreshToken A string containing the Refresh token.
     * @param string $redirectURL A string containing the OAuth redirect URL.
     * @param string $id A string
     */
    public function __construct($clientID, $clientSecret, $grantToken, $refreshToken = null, $redirectURL=null, $accessToken=null, $scope=null)
    {
        $this->clientID = $clientID;

        $this->clientSecret = $clientSecret;

        $this->grantToken = $grantToken;

        $this->refreshToken = $refreshToken;

        $this->redirectURL = $redirectURL;

        $this->accessToken = $accessToken;

        $this->scope = $scope;
    }

    /**
     * This is a getter method to get OAuth client id.
     * @return string A string representing the OAuth client id.
     */
    public function getClientId()
    {
        return $this->clientID;
    }

    /**
     * This is a getter method to get OAuth client secret.
     * @return string A string representing the OAuth client secret.
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * This is a getter method to get OAuth redirect URL.
     * @return string A string representing the OAuth redirect URL.
     */
    public function getRedirectURL()
    {
        return $this->redirectURL;
    }

    /**
     * This is a getter method to get grant token.
     * @return NULL|string A string representing the grant token.
     */
    public function getGrantToken()
    {
        return $this->grantToken;
    }

    /**
     * This is a getter method to get refresh token.
     * @return NULL|string|mixed A string representing the refresh token.
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * This is a getter method to get scope.
     * @return NULL|string|mixed A string representing the refresh token.
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * This is a getter method to get access token.
     * @return string A string representing the access token.
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * This is a getter method to get token expire time.
     * @return string A string representing the token expire time.
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * This is a setter method to set refresh token.
     * @param string $refreshToken A string containing the refresh token.
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * This is a getter method to set OAuth redirect URL.
     * @param string A string representing the OAuth redirect URL.
     */
    public function setRedirectURL($redirectURL)
    {
        $this->redirectURL = $redirectURL;

        return $this;
    }

    /**
     * This is a setter method to set OAuth client id.
     * @param string A string representing the OAuth client id.
     */
    public function setClientId($clientID)
    {
        $this->clientID = $clientID;

        return $this;
    }

    /**
     * This is a getter method to set OAuth client secret.
     * @param string A string representing the OAuth client secret.
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * This is a setter method to set grant token.
     * @param string A string representing the grant token.
     */
    public function setGrantToken($grantToken)
    {
        $this->grantToken = $grantToken;

        return $this;
    }

    /**
     * This is a setter method to set access token.
     * @param string $accessToken A string containing the access token.
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * This is a setter method to set token expire time.
     * @param string $expiresIn A string containing the token expire time.
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * This is a setter method to set the scope
     * @param string $scope A string containing the scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Generate a token
     *
     * @return void
     */
    public function generateToken()
    {
        $this->getToken();
    }

    /**
     * flow to get or refresh an access token
     *
     * @return void
     */
    public function getToken()
    {

        $logger = App::getInstance()->getLogger();

        if ($this->getAccessToken() == null && strlen($this->getAccessToken()) == 0)
        {
            if ($this->getRefreshToken() != null && strlen($this->getRefreshToken()) > 0)
            {
                $logger->info('Access token can be generated by refresh token', self::class . '::' . __METHOD__ );
                $this->refreshAccessToken();
            }
            else 
            {
                $logger->info('Access token can be generated by grant token.', self::class . '::' . __METHOD__ );
                $this->generateAccessToken();
            }
        }
        elseif ($this->getExpiresIn() != null && strlen($this->getExpiresIn())>0 && $this->isAccessTokenExpired($this->getExpiresIn()))
        {
            $logger->info('Access Token has expired. Hence refreshing.', self::class . '::' . __METHOD__ );
            $this->refreshAccessToken()->getAccessToken();
        }
        elseif ($this->getExpiresIn() == null && $this->getAccessToken() != null)
        {
            TokenStore::getInstance()->saveToken($this);
        }
        return $this->getAccessToken();
    }


    public function checkTokenDetails()
    {
        if ($this->getGrantToken() == null && $this->getRefreshToken() == null)
        {
            throw new Exception(Constants::MANDATORY_VALUE_ERROR, Constants::GET_TOKEN_BY_USER_NAME_ERROR . " - " . (join(", ", Constants::OAUTH_MANDATORY_KEYS2)));
        }
        return true;
    }
    
    /**
     * add authentication header
     *
     * @param Connector $urlConnection
     * @return void
     */
    public function authenticate(HTTPConnector $urlConnection)
    {
        $urlConnection->addHeader(Constants::AUTHORIZATION, Constants::OAUTH_HEADER_PREFIX . $this->getToken());
    }

    /**
     * get response from server
     *
     * @param array $request_params
     * @param string $url
     * @return array
     */
    public function getResponseFromServer($request_params, $url)
    {
        $curl_pointer = curl_init();

        curl_setopt($curl_pointer, CURLOPT_URL, $url);

        curl_setopt($curl_pointer, CURLOPT_HEADER, 1);

        curl_setopt($curl_pointer, CURLOPT_POSTFIELDS, $this->getUrlParamsAsString($request_params));

        curl_setopt($curl_pointer, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl_pointer, CURLOPT_USERAGENT, Constants::USER_AGENT);

        curl_setopt($curl_pointer, CURLOPT_POST, count($request_params));

        curl_setopt($curl_pointer, CURLOPT_CUSTOMREQUEST, Constants::REQUEST_METHOD_POST);

        curl_setopt($curl_pointer, CURLOPT_SSL_VERIFYPEER, false); // needed?

        $result = curl_exec($curl_pointer);

        curl_close($curl_pointer);

        return $result;
    }

    /**
     * refresh access token
     *
     * @return OAuthToken
     */
    public function refreshAccessToken( )
    {

        $url = Environment::getInstance()->getAccountsUrl();

        $requestParams = array();

        $requestParams[Constants::CLIENT_ID] =  $this->getClientId();

        $requestParams[Constants::CLIENT_SECRET] =  $this->getClientSecret();

        $requestParams[Constants::GRANT_TYPE] = Constants::REFRESH_TOKEN;

        $requestParams[Constants::REFRESH_TOKEN] =  $this->getRefreshToken();

        $response = $this->getResponseFromServer($requestParams, $url);

        try
        {
            $this->processResponse($response);

            TokenStore::getInstance()->saveToken($this);
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
        catch (\Exception $ex)
        {
            throw new Exception(Constants::SAVE_TOKEN_ERROR . ' : ' . $ex->getMessage());
        }

        return $this;
    }

    /**
     * generate access token
     *
     * @return OAuthToken
     */
    public function generateAccessToken()
    {

        $url = Environment::getInstance()->getAccountsUrl();

        $requestParams = array();

        $requestParams[Constants::CLIENT_ID] =  $this->getClientId();

        $requestParams[Constants::CLIENT_SECRET] =  $this->getClientSecret();

        if($this->getRedirectURL() != null)
        {
            $requestParams[Constants::REDIRECT_URI] =  $this->getRedirectURL();
        }

        $requestParams[Constants::GRANT_TYPE] = Constants::GRANT_TYPE_AUTH_CODE;

        $requestParams[Constants::CODE] = $this->getGrantToken();

        $response = $this->getResponseFromServer($requestParams, $url);

        try
        {
            $this->processResponse($response);

            TokenStore::getInstance()->saveToken($this);
        }
        catch (\Exception $ex)
        {
            throw new Exception(Constants::SAVE_TOKEN_ERROR . ' : ' . $ex->getMessage());
        }

        return $this;
    }

    /**
     * process response
     *
     * @param string $response
     * @return void
     */
    public function processResponse($response)
    {
        $headerRows = explode("\n",$response);

        $responseBody = end($headerRows);

        $jsonResponse = json_decode($responseBody, true);

        if (!array_key_exists(Constants::ACCESS_TOKEN, $jsonResponse))
        {
            throw new Exception(array_key_exists(Constants::INVALID_TOKEN_ERROR . ' : ' . Constants::ERROR, $jsonResponse) ? $jsonResponse[Constants::ERROR] : Constants::NO_ACCESS_TOKEN_ERROR);
        }

        $this->setAccessToken($jsonResponse[Constants::ACCESS_TOKEN]);

        $this->setExpiresIn($this->getTokenExpiresIn($jsonResponse));

        if (array_key_exists(Constants::REFRESH_TOKEN, $jsonResponse))
        {
            $this->refreshToken = $jsonResponse[Constants::REFRESH_TOKEN];
        }

        return $this;
    }

    /**
     * return expires in
     *
     * @param array $response
     * @return int
     */
    private function getTokenExpiresIn($response){

        // convert the expiration time from seconds to milliseconds
        //$expireIn = $response[Constants::EXPIRES_IN] * 1000;

        // we are using 1800 as requested by Joe in a email. The response returns 600 seconds
        $expireIn = 1800 * 1000;

        return $this->getCurrentTimeInMillis() + $expireIn;
    }

    /**
     * return the current time in milliseconds
     *
     * @return int
     */
    public function getCurrentTimeInMillis()
    {
        return round(microtime(true) * 1000);
    }

    /**
     * return ir token has expired
     *
     * @param int $expiry_time
     * @return boolean
     */
    public function isAccessTokenExpired($expiry_time)
    {
        return ((((double)$expiry_time) - $this->getCurrentTimeInMillis()) < 10000);
    }

    /**
     * build string of params
     *
     * @param array $urlParams
     * @return string
     */
    public function getUrlParamsAsString($urlParams)
    {
        $paramsAsString = "";

        foreach ($urlParams as $key => $value)
        {
            $paramsAsString = $paramsAsString . $key . "=" . $value . "&";
        }

        $paramsAsString = rtrim($paramsAsString, "&");

        return str_replace(PHP_EOL, '', $paramsAsString);
    }

    /**
     * remove the token
     *
     * @return void
     */
    public function remove()
    {
        TokenStore::getInstance()->deleteToken($this);

        return true;
    }
}