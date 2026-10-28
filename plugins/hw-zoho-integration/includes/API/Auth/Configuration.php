<?php

namespace HWZoho\API\Auth;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Credential/config surface for the Zoho connection. NOTHING is hardcoded here
 * anymore (the previous hardcoded client id/secret + zapikey leaked via git
 * history and must be rotated). Each value is read from a wp-config.php
 * constant first (preferred — stays out of the DB and the repo), falling back
 * to a network option (`wp site option update <option> <value>`):
 *
 *   HWZI_ZOHO_CLIENT_ID     / hwzi_zoho_client_id
 *   HWZI_ZOHO_CLIENT_SECRET / hwzi_zoho_client_secret
 *   HWZI_ZOHO_API_HOST      / hwzi_zoho_api_host    (default https://www.zohoapis.com;
 *                                                     point at the sandbox on clones)
 *   HWZI_DELUGE_ZAPIKEY     / hwzi_deluge_zapikey   (Deluge function api key)
 */
class Configuration{

    private static $token_store_path = HW_ZOHO_PLUGIN_PATH . "/auth/php_sdk_token.txt";

    /**
     * constant-first config read: wp-config constant wins, network option is
     * the fallback, then $default.
     */
    private static function value( $constant, $option, $default = '' ){

        if ( defined( $constant ) && constant( $constant ) !== '' ) {
            return (string) constant( $constant );
        }

        $opt = get_site_option( $option, '' );
        return ( $opt !== '' && $opt !== false && $opt !== null ) ? (string) $opt : $default;
    }

    /**
     * like value(), but a missing credential is a hard, DESCRIPTIVE failure —
     * never a silent empty string handed to the SDK.
     */
    private static function require_value( $constant, $option ){

        $v = self::value( $constant, $option );
        if ( $v === '' ) {
            throw new \RuntimeException( sprintf(
                'Zoho credential missing: define %s in wp-config.php (or set the %s network option).',
                $constant,
                $option
            ) );
        }
        return $v;
    }

    public static function get_client_id(){

        return self::require_value( 'HWZI_ZOHO_CLIENT_ID', 'hwzi_zoho_client_id' );
    }

    public static function get_secret_id(){

        return self::require_value( 'HWZI_ZOHO_CLIENT_SECRET', 'hwzi_zoho_client_secret' );
    }

    public static function get_store_path(){

        return self::$token_store_path;
    }

    /**
     * SDK datacenter environment, derived from the SAME host config the Deluge
     * URL uses: a host containing "sandbox" -> SANDBOX, else PRODUCTION. One
     * branch serves both environments — no hardcoded repoints.
     */
    public static function get_sdk_environment(){

        $host = self::value( 'HWZI_ZOHO_API_HOST', 'hwzi_zoho_api_host', 'https://www.zohoapis.com' );

        return ( stripos( $host, 'sandbox' ) !== false )
            ? \com\zoho\crm\api\dc\USDataCenter::SANDBOX()
            : \com\zoho\crm\api\dc\USDataCenter::PRODUCTION();
    }

    /**
     * Build a Zoho Deluge function URL (…/functions/{name}/actions/execute).
     * Host + zapikey come from config, so repointing sandbox/production is a
     * config change, not a code edit. Returns '' when no zapikey is configured —
     * callers must treat that as "integration off" and NOT post anyway.
     *
     * @param string $function Deluge function name, e.g. create_reservation_and_contact
     * @return string full URL, or '' when unconfigured
     */
    public static function get_deluge_function_url( $function ){

        $key = self::value( 'HWZI_DELUGE_ZAPIKEY', 'hwzi_deluge_zapikey' );
        if ( $key === '' ) {
            return '';
        }

        $host = self::value( 'HWZI_ZOHO_API_HOST', 'hwzi_zoho_api_host', 'https://www.zohoapis.com' );

        return rtrim( $host, '/' ) . '/crm/v2/functions/' . rawurlencode( $function )
            . '/actions/execute?auth_type=apikey&zapikey=' . rawurlencode( $key );
    }
}
