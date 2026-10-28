<?php

namespace HW\Integrations\ClassicVacations\API;

/**
 * This class uses the SDK constants name reference.
 */
class Constants
{
    const CLIENT_ID = "client_id";

    const CLIENT_SECRET = "client_secret";

    const REDIRECT_URL = "redirect_url";

    const REDIRECT_URI = "redirect_uri";

    const GRANT_TYPE = "grant_type";

    const REFRESH_TOKEN = "refresh_token";

    const EXPIRES_IN = "expires_in";

    const EXPIRES_IN_SEC = "expires_in_sec";

    const ACCESS_TOKEN = "access_token";

    const REQUEST_METHOD_POST = "POST";

    const REQUEST_METHOD_GET = "GET";

    const REQUEST_METHOD_PUT = "PUT";

    const REQUEST_METHOD_PATCH = "PATCH";

    const REQUEST_METHOD_DELETE = "DELETE";

    const SAVE_TOKEN_ERROR = "Exception in saving tokens : ";

    const CODE = "code";

    const AUTHORIZATION = "Authorization";

    const CANT_DISCLOSE = " ## can't disclose ## ";

    const OAUTH_HEADER_PREFIX = "Bearer ";

    const MANDATORY_VALUE_ERROR = "MANDATORY VALUE ERROR";

    const GET_TOKEN_BY_USER_NAME_ERROR = "Exception in getToken() - OAuthToken : Given user_name token details not found, Please give required item";

    const OAUTH_MANDATORY_KEYS2 = array(Constants::GRANT_TOKEN, Constants::REFRESH_TOKEN);

    const GRANT_TOKEN = "grant_token";

    const INVALID_TOKEN_ERROR = "INVALID TOKEN ERROR" ;

    const ERROR = "error";

    const NO_ACCESS_TOKEN_ERROR = "ACCESS TOKEN IS NOT PRESENT IN RESPONSE" ;

    const TOKEN = "\$TOKEN";

    const GRANT_TYPE_AUTH_CODE = "authorization_code";

    const USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13';

    const INITIALIZATION_SUCCESSFUL = "Initialization successful ";

    const INITIALIZATION_EXCEPTION = "Initialization exception ";

    const SDK_UNINITIALIZATION_ERROR = "SDK UNINITIALIZED ERROR";

    const SDK_UNINITIALIZATION_MESSAGE = "SDK is UnInitialized";

    const CONTENT_TYPE = "content-type";

    const CONTENT_TYPE_1 = "Content-Type";

    const CONTENT_TYPE_2 = "ontent-Type";

    const URL = "URL";

	const PARAMS = "PARAMS";

	const PARAMETER_NULL_ERROR = "NULL PARAMETER ERROR";

	const HEADER_NULL_ERROR = "NULL HEADER ERROR";

	const PARAM_NAME_NULL_ERROR = "NULL PARAM NAME ERROR";

	const HEADER_NAME_NULL_ERROR = "NULL HEADER NAME ERROR";

	const PARAM_NAME_NULL_ERROR_MESSAGE = "Param Name MUST NOT be null";

	const HEADER_NAME_NULL_ERROR_MESSAGE = "Header Name MUST NOT be null";

	const NULL_VALUE_ERROR_MESSAGE = " MUST NOT be null";

	const PARAM_INSTANCE_NULL_ERROR = "Param Instance MUST NOT be null";

	const HEADER_INSTANCE_NULL_ERROR = "Header Instance MUST NOT be null";

    const HEADERS = "headers";

    const SET_API_URL_EXCEPTION = "Exception in setting API URL";

    const AUTHENTICATION_EXCEPTION = "Exception in authenticating current request : ";

    const API_ERROR_RESPONSE = "Error response :";

    const API_CALL_EXCEPTION = "Exception in current API call execution : ";

    const HTTP_CODE = "http_code";

    const RESPONSE = "response";

    const BODY = "body";

    const TOKEN_EXPIRATION_TIME = 600;
    
}