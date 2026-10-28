<?php

namespace HW\Feedback;

use HW\Traits\Singleton;

/**
 * REST endpoint: POST /hw/v1/feedback-report
 *
 * Accepts multipart/form-data with:
 *   payload    — JSON string (see FeedbackValidator::sanitize for shape)
 *   screenshot — optional PNG/JPEG file
 *
 * Only logged-in users with the manage_options capability (admins) may submit.
 * The endpoint only accepts same-domain requests authenticated via WP REST nonce.
 */
class FeedbackEndpoint {

	use Singleton;

	const NAMESPACE         = 'hw/v1';
	const ROUTE             = '/feedback-report';
	const ROUTE_ISSUES      = '/feedback-issues';
	const ROUTE_REPORTERS   = '/feedback-reporters';
	const ROUTE_PROXY       = '/feedback-asset-proxy';
	const ROUTE_DELETE_ISSUE = '/feedback-issue/(?P<key>[A-Z0-9]+-\d+)';

	/** Maximum asset size the proxy will stream back (bytes). */
	const PROXY_MAX_BYTES   = 10 * 1024 * 1024; // 10 MB

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	// =========================================================================
	// Route registration
	// =========================================================================

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, self::ROUTE, [
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'handle_report' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::NAMESPACE, self::ROUTE_REPORTERS, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_get_reporters' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		register_rest_route( self::NAMESPACE, self::ROUTE_ISSUES, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_get_issues' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'page' => [
					'default'           => 1,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, self::ROUTE_DELETE_ISSUE, [
			'methods'             => \WP_REST_Server::DELETABLE,
			'callback'            => [ $this, 'handle_delete_issue' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'key' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, self::ROUTE_PROXY, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ $this, 'handle_asset_proxy' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'url' => [
					'required' => true,
				],
			],
		] );
	}

	// =========================================================================
	// Permission callback
	// =========================================================================

	/**
	 * Only administrators (manage_options) may submit feedback reports.
	 * Authentication is handled by the standard WP REST nonce (X-WP-Nonce header).
	 */
	public function check_permission(): bool|\WP_Error {

		if ( ! is_user_logged_in() ) {
			return new \WP_Error( 'rest_not_logged_in', 'Authentication required.', [ 'status' => 401 ] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'unauthorized', 'You do not have permission to submit reports.', [ 'status' => 403 ] );
		}

		return true;
	}

	// =========================================================================
	// Endpoint handler
	// =========================================================================

	/**
	 * POST /hw/v1/feedback-report
	 */
	public function handle_report( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {

		// --- Decode payload ------------------------------------------------------
		$raw = $request->get_param( 'payload' );
		if ( empty( $raw ) ) {
			return new \WP_Error( 'missing_payload', 'Missing payload.', [ 'status' => 400 ] );
		}

		$payload = json_decode( wp_unslash( (string) $raw ), true );
		if ( ! is_array( $payload ) ) {
			return new \WP_Error( 'invalid_payload', 'Payload must be a valid JSON object.', [ 'status' => 400 ] );
		}

		// --- Validate + sanitize -------------------------------------------------
		$validator = new FeedbackValidator();
		$valid     = $validator->validate( $payload );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$data = $validator->sanitize( $payload );

		// --- Screenshots (always optional, supports multiple) --------------------
		$screenshots = [];
		$files       = $request->get_file_params();

		if ( ! empty( $files['screenshot'] ) ) {
			foreach ( $this->normalize_file_array( $files['screenshot'] ) as $file ) {
				$file_valid = $validator->validate_screenshot( $file );
				if ( is_wp_error( $file_valid ) ) {
					return $file_valid;
				}
				$screenshots[] = $file;
			}
		}

		// --- Create Jira issue ---------------------------------------------------
		$jira   = new JiraClient();
		$result = $jira->create_issue( $data, $screenshots );

		if ( is_wp_error( $result ) ) {
			$this->log_error( $result->get_error_message() );
			return new \WP_Error(
				'jira_error',
				'Could not create the issue. Please try again.',
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response( [
			'success'  => true,
			'jira_key' => $result['key'],
			'jira_url' => $result['url'],
			'message'  => 'Issue reported successfully.',
		] );
	}

	/**
	 * GET /hw/v1/feedback-reporters
	 * Busts the transient cache and returns a fresh project member list.
	 */
	public function handle_get_reporters(): \WP_REST_Response|\WP_Error {

		$project_key = (string) get_network_option( null, 'hw_jira_project_key', '' );

		$cache_key = 'hw_jira_members_roles2_' . md5( $project_key );
		delete_transient( $cache_key );

		if ( ! JiraTokenStore::is_connected() || $project_key === '' ) {
			return rest_ensure_response( [] );
		}

		$result = ( new JiraClient() )->get_project_members( $project_key );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return rest_ensure_response( $result );
	}

	/**
	 * GET /hw/v1/feedback-issues
	 */
	public function handle_get_issues( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {

		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$result = ( new JiraClient() )->get_site_issues( $page );

		if ( is_wp_error( $result ) ) {
			$jira_code = (int) ( $result->get_error_data()['http_code'] ?? 500 );
			$status    = ( $jira_code >= 400 && $jira_code < 600 ) ? $jira_code : 500;
			return new \WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				[ 'status' => $status ]
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * DELETE /hw/v1/feedback-issue/{key}
	 */
	public function handle_delete_issue( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {

		$key = strtoupper( trim( (string) $request->get_param( 'key' ) ) );
		if ( ! preg_match( '/^[A-Z0-9]+-\d+$/', $key ) ) {
			return new \WP_Error( 'invalid_key', 'Invalid issue key.', [ 'status' => 400 ] );
		}

		$result = ( new JiraClient() )->delete_issue( $key );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error(
				'jira_error',
				$result->get_error_message(),
				[ 'status' => 500 ]
			);
		}

		return rest_ensure_response( [ 'success' => true, 'key' => $key ] );
	}

	/**
	 * GET /hw/v1/feedback-asset-proxy?url=…
	 *
	 * Fetches an image server-side and streams it back from the WordPress origin
	 * with a permissive CORS header.  This lets html2canvas inline cross-origin
	 * (e.g. multisite-subdomain or CDN) background-images and <img> assets as
	 * same-origin data-URLs so the capture canvas is never tainted.
	 *
	 * Admin-only (manage_options, via check_permission). Streams raw bytes then exits.
	 */
	public function handle_asset_proxy( \WP_REST_Request $request ): \WP_Error {

		$url = trim( (string) $request->get_param( 'url' ) );

		// Only well-formed http(s) URLs.
		if ( $url === '' || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'invalid_url', 'Invalid asset URL.', [ 'status' => 400 ] );
		}

		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return new \WP_Error( 'invalid_scheme', 'Only http(s) URLs are allowed.', [ 'status' => 400 ] );
		}

		// Block the cloud metadata endpoint (defence-in-depth against SSRF).
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( $host === '169.254.169.254' || $host === 'metadata.google.internal' ) {
			return new \WP_Error( 'forbidden_host', 'Host not allowed.', [ 'status' => 403 ] );
		}

		$response = wp_remote_get( $url, [
			'timeout'     => 10,
			'redirection' => 3,
			'sslverify'   => false, // local *.test dev certs are typically self-signed
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'fetch_failed', 'Could not fetch asset.', [ 'status' => 502 ] );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new \WP_Error( 'fetch_failed', 'Asset returned HTTP ' . $code, [ 'status' => 502 ] );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$content_type = is_array( $content_type ) ? ( $content_type[0] ?? '' ) : (string) $content_type;
		$content_type = trim( strtok( $content_type, ';' ) ); // drop charset/params

		if ( strpos( $content_type, 'image/' ) !== 0 ) {
			return new \WP_Error( 'not_image', 'Asset is not an image.', [ 'status' => 415 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( $body === '' || strlen( $body ) > self::PROXY_MAX_BYTES ) {
			return new \WP_Error( 'bad_asset', 'Asset is empty or too large.', [ 'status' => 502 ] );
		}

		// Stream the raw bytes from this origin so the canvas stays untainted.
		header_remove();
		status_header( 200 );
		header( 'Content-Type: ' . $content_type );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Cache-Control: private, max-age=300' );
		header( 'Content-Length: ' . strlen( $body ) );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw binary image.
		exit;
	}

	// =========================================================================
	// Internal helpers
	// =========================================================================

	/**
	 * Normalize a PHP $_FILES entry to an indexed array of individual file entries.
	 * Handles both single-file (scalar tmp_name) and multi-file (array tmp_name) uploads.
	 *
	 * @param array $file A single entry from get_file_params(), e.g. $files['screenshot'].
	 * @return array[]
	 */
	private function normalize_file_array( array $file ): array {
		if ( is_array( $file['tmp_name'] ) ) {
			$entries = [];
			$count   = count( $file['tmp_name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				$entries[] = [
					'name'     => $file['name'][$i]     ?? 'screenshot.png',
					'type'     => $file['type'][$i]     ?? 'image/png',
					'tmp_name' => $file['tmp_name'][$i] ?? '',
					'error'    => $file['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
					'size'     => $file['size'][$i]     ?? 0,
				];
			}
			return $entries;
		}

		return [ $file ];
	}

	private function log_error( string $message ): void {
		$errors = get_option( 'hw_feedback_errors', [] );
		array_unshift( $errors, [
			'message' => $message,
			'time'    => current_time( 'mysql' ),
		] );
		update_option( 'hw_feedback_errors', array_slice( $errors, 0, 20 ), false );
	}
}
