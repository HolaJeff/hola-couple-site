<?php

namespace HWZoho\REST\Routes;

use HWZoho\REST\Route;
use HWZoho\API\Auth\OAuth;
use HWZoho\Services\CoupleSite as CoupleSiteService;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Stage 2 — authenticated couple-site creation endpoint.
 *
 *   POST /wp-json/v1/zoho-plugin/couple-site   { "wedding_id": "...", ...overrides }
 *
 * Wraps the proven Services\CoupleSite path: given a Zoho wedding (Deal) id, it
 * builds the same inputs the wp-admin Import Wedding form produces, then creates
 * the couple subsite. Authenticated via WordPress Application Password (Basic
 * Auth over HTTPS) + a real capability check — NOT __return_true.
 */
class CoupleSite extends Route{

    public function register_routes(){

        $base = 'v1/zoho-plugin';

        register_rest_route( $base, '/couple-site', array(
            'methods'             => \WP_REST_Server::CREATABLE, // POST
            'callback'            => [ $this, 'create_couple_site' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'wedding_id'          => [ 'required' => true,  'type' => 'string' ],
                'hotel_id'            => [ 'required' => false, 'type' => 'string' ],
                'subdomain'           => [ 'required' => false, 'type' => 'string' ],
                'transfer_multiplier' => [ 'required' => false, 'type' => 'string' ],
                'booking_model'       => [ 'required' => false, 'type' => 'string' ],
                'promocode'           => [ 'required' => false, 'type' => 'string' ],
                'terms'               => [ 'required' => false, 'type' => 'string' ],
                'cancellation_policy' => [ 'required' => false, 'type' => 'string' ],
            ],
        ) );

        // Re-sync an EXISTING site from Zoho (updated contract -> live site).
        register_rest_route( $base, '/couple-site/resync', array(
            'methods'             => \WP_REST_Server::CREATABLE, // POST
            'callback'            => [ $this, 'resync_couple_site' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'wedding_id'          => [ 'required' => true,  'type' => 'string' ],
                'hotel_id'            => [ 'required' => false, 'type' => 'string' ],
                'transfer_multiplier' => [ 'required' => false, 'type' => 'string' ],
                'booking_model'       => [ 'required' => false, 'type' => 'string' ],
                'promocode'           => [ 'required' => false, 'type' => 'string' ],
                'terms'               => [ 'required' => false, 'type' => 'string' ],
                'cancellation_policy' => [ 'required' => false, 'type' => 'string' ],
            ],
        ) );
    }

    /**
     * Re-sync the existing couple site: rebuild inputs from the (corrected) Zoho
     * wedding and push room blocks + policies to the live site — no recreation.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function resync_couple_site( $request ){

        $wedding_id = (string) $request->get_param( 'wedding_id' );
        $overrides  = array_filter([
            'hotel_id'            => $request->get_param( 'hotel_id' ),
            'transfer_multiplier' => $request->get_param( 'transfer_multiplier' ),
            'booking_model'       => $request->get_param( 'booking_model' ),
            'promocode'           => $request->get_param( 'promocode' ),
            'terms'               => $request->get_param( 'terms' ),
            'cancellation_policy' => $request->get_param( 'cancellation_policy' ),
        ], function ( $v ) { return $v !== null && $v !== ''; });

        try {
            OAuth::initialize();
        } catch ( \Throwable $e ) {
            return $this->error( 'ZOHO_NOT_CONNECTED', $e->getMessage(), 502 );
        }

        $inputs = CoupleSiteService::build_inputs_from_wedding( $wedding_id, $overrides );
        if ( is_wp_error( $inputs ) ) {
            $data = $inputs->get_error_data();
            return $this->error( $inputs->get_error_code(), $inputs->get_error_message(),
                is_array( $data ) && isset( $data['code'] ) ? $data['code'] : 400 );
        }

        $result = CoupleSiteService::resync( $inputs['domain'], $inputs['options'], $inputs['room_block_ids'], $inputs['style'] ?? 'classic' );
        if ( is_wp_error( $result ) ) {
            $data = $result->get_error_data();
            return $this->error( $result->get_error_code(), $result->get_error_message(),
                is_array( $data ) && isset( $data['code'] ) ? $data['code'] : 500 );
        }

        return new \WP_REST_Response([
            'status'  => 'SUCCESS',
            'code'    => 'SITE_RESYNCED',
            'message' => 'Couple site re-synced from Zoho: room blocks + policies refreshed.',
            'data'    => $this->site_payload( (int) $result, $inputs['domain'] ),
        ], 200);
    }

    /**
     * REAL auth: the request must be an authenticated WP user (Application
     * Password populates the current user) with network-admin capability,
     * since creating a subsite is a network operation.
     *
     * @param \WP_REST_Request $request
     * @return true|\WP_Error
     */
    public function permission_check( $request ){

        if ( ! is_user_logged_in() ) {
            return new \WP_Error(
                'rest_not_logged_in',
                'Authentication required. Use a WordPress Application Password (Basic Auth over HTTPS).',
                [ 'status' => 401 ]
            );
        }

        if ( ! current_user_can( 'manage_network' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                'Insufficient permissions: creating a couple site requires the manage_network capability.',
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Build inputs from the wedding id and create the couple subsite.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_couple_site( $request ){

        // A JSON response: nothing may print into the body. On a brand-new
        // subsite Elementor Pro's notes cleanup queries its per-site table
        // before the table exists; with error display on, that $wpdb error
        // printed ahead of the JSON and the platform saw "non-JSON (201)".
        global $wpdb;
        $wpdb->hide_errors();
        @ini_set( 'display_errors', '0' );

        $wedding_id = (string) $request->get_param( 'wedding_id' );

        $overrides = array_filter(
            [
                'hotel_id'            => $request->get_param( 'hotel_id' ),
                'subdomain'           => $request->get_param( 'subdomain' ),
                'transfer_multiplier' => $request->get_param( 'transfer_multiplier' ),
                'booking_model'       => $request->get_param( 'booking_model' ),
                'promocode'           => $request->get_param( 'promocode' ),
                'terms'               => $request->get_param( 'terms' ),
                'cancellation_policy' => $request->get_param( 'cancellation_policy' ),
            ],
            function ( $v ) { return $v !== null && $v !== ''; }
        );

        // Connect the SDK (sandbox on this clone). Guarded so a connection
        // problem returns a clean 502 instead of an uncaught fatal.
        try {
            OAuth::initialize();
        } catch ( \Throwable $e ) {
            return $this->error( 'ZOHO_NOT_CONNECTED', $e->getMessage(), 502 );
        }

        $inputs = CoupleSiteService::build_inputs_from_wedding( $wedding_id, $overrides );
        if ( is_wp_error( $inputs ) ) {
            $data = $inputs->get_error_data();
            return $this->error( $inputs->get_error_code(), $inputs->get_error_message(), is_array( $data ) && isset( $data['code'] ) ? $data['code'] : 400 );
        }

        // Resolve the blog id — an existing subsite (idempotent) or a freshly
        // created one. Both paths then write the URL back to Zoho.
        $existing = domain_exists( $inputs['domain'], '/' );

        if ( $existing ) {
            $blog_id     = (int) $existing;
            $http_status = 200;
            $status_code = 'SITE_ALREADY_EXISTS';
            $status_word = 'EXISTS';
            $message     = 'A couple site already exists for this wedding.';
        } else {
            $created = CoupleSiteService::create(
                $inputs['domain'],
                $inputs['wedding_name'],
                $inputs['options'],
                $inputs['room_block_ids'],
                get_current_user_id(),
                $inputs['style'] ?? 'classic'
            );

            if ( is_wp_error( $created ) ) {
                return $this->error( 'SITE_CREATE_FAILED', $created->get_error_message(), 500 );
            }

            $blog_id     = (int) $created;
            $http_status = 201;
            $status_code = 'SITE_CREATED';
            $status_word = 'SUCCESS';
            $message     = 'Couple site created.';
        }

        $payload = $this->site_payload( $blog_id, $inputs['domain'] );

        // Step 4: write the new subsite URL back to the Zoho Deal's
        // Wedding_Page_URL. A failure here must NOT fail the request — the site
        // exists either way; surface the outcome instead of rolling back.
        $writeback = CoupleSiteService::write_back_page_url( $wedding_id, $payload['url'] );
        $payload['wedding_page_url_writeback'] = is_wp_error( $writeback )
            ? 'FAILED: ' . $writeback->get_error_message()
            : 'OK';

        return new \WP_REST_Response(
            [
                'status'  => $status_word,
                'code'    => $status_code,
                'message' => $message,
                'data'    => $payload,
            ],
            $http_status
        );
    }

    private function error( $code, $message, $status ){
        return new \WP_REST_Response(
            [ 'status' => 'ERROR', 'code' => $code, 'message' => $message ],
            (int) $status
        );
    }

    private function site_payload( $blog_id, $domain ){
        return [
            'blog_id'   => (int) $blog_id,
            'url'       => get_site_url( $blog_id ),
            'admin_url' => get_admin_url( $blog_id ),
            'domain'    => $domain,
        ];
    }
}
