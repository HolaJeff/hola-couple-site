<?php

namespace HW\Feedback;

/**
 * Atlassian OAuth 2.0 (3LO) flow for Jira Cloud.
 *
 * Setup:
 *   1. Go to https://developer.atlassian.com/console/myapps/
 *   2. Create app → OAuth 2.0 (3LO)
 *   3. Add callback URL — shown in Network Settings → Jira as "OAuth Callback URL"
 *   4. Enable scopes: read:jira-user  write:jira-work  read:jira-work  offline_access
 *   5. Copy Client ID and Client Secret into the Jira settings tab
 *      (or define HW_JIRA_CLIENT_ID / HW_JIRA_CLIENT_SECRET in wp-config.php)
 */
class JiraOAuth {

	private const AUTH_URL      = 'https://auth.atlassian.com/authorize';
	private const TOKEN_URL     = 'https://auth.atlassian.com/oauth/token';
	private const RESOURCES_URL = 'https://api.atlassian.com/oauth/token/accessible-resources';
	private const SCOPES        = 'read:jira-user write:jira-work read:jira-work offline_access';
	private const STATE_OPTION  = 'hw_jira_oauth_state';

	private string $client_id;
	private string $client_secret;

	public function __construct() {
		$this->client_id     = $this->cfg( 'client_id' );
		$this->client_secret = $this->cfg( 'client_secret' );
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/** True when client ID and secret are present. */
	public function is_configured(): bool {
		return $this->client_id !== '' && $this->client_secret !== '';
	}

	/**
	 * Build the Atlassian authorization URL and persist a CSRF state token.
	 * Redirect the browser here to begin the OAuth flow.
	 */
	public function get_authorization_url(): string {

		$state = wp_generate_password( 40, false );
		update_network_option( null, self::STATE_OPTION, $state );

		return self::AUTH_URL . '?' . http_build_query( [
			'audience'      => 'api.atlassian.com',
			'client_id'     => $this->client_id,
			'scope'         => self::SCOPES,
			'redirect_uri'  => self::get_redirect_uri(),
			'state'         => $state,
			'response_type' => 'code',
			'prompt'        => 'consent',
		] );
	}

	/**
	 * Handle the callback from Atlassian: verify the CSRF state, exchange the
	 * authorization code for tokens, discover the cloud ID, and persist everything.
	 *
	 * @param string $code  The ?code= value from the callback URL.
	 * @param string $state The ?state= value for CSRF verification.
	 * @return true|\WP_Error
	 */
	public function handle_callback( string $code, string $state ): true|\WP_Error {

		$saved = (string) get_network_option( null, self::STATE_OPTION, '' );
		delete_network_option( null, self::STATE_OPTION );

		if ( $saved === '' || ! hash_equals( $saved, $state ) ) {
			return new \WP_Error( 'invalid_state', 'OAuth state mismatch — possible CSRF attempt.' );
		}

		$tokens = $this->exchange_code( $code );
		if ( is_wp_error( $tokens ) ) return $tokens;

		$resource = $this->fetch_primary_resource( $tokens['access_token'] );
		if ( is_wp_error( $resource ) ) return $resource;

		JiraTokenStore::save(
			$tokens,
			$resource['id'],
			$resource['name'] ?? '',
			$resource['url']  ?? ''
		);

		return true;
	}

	/**
	 * Exchange the stored refresh token for a fresh access token and persist it.
	 * Called automatically by JiraClient before any API request when the token is expired.
	 *
	 * @return true|\WP_Error
	 */
	public function refresh(): true|\WP_Error {

		$refresh_token = JiraTokenStore::get_refresh_token();

		if ( $refresh_token === '' ) {
			return new \WP_Error(
				'no_refresh_token',
				'No refresh token stored. Please reconnect Jira in Network Settings → Jira.'
			);
		}

		$tokens = $this->request_tokens( [
			'grant_type'    => 'refresh_token',
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'refresh_token' => $refresh_token,
		] );

		if ( is_wp_error( $tokens ) ) return $tokens;

		JiraTokenStore::save( $tokens, JiraTokenStore::get_cloud_id() );

		return true;
	}

	/**
	 * The redirect URI that must be registered in the Atlassian app console.
	 * Displayed in the settings tab so the admin can copy it.
	 *
	 * Override in wp-config.php for local development (Atlassian requires HTTPS):
	 *   define( 'HW_JIRA_OAUTH_REDIRECT_URI', 'https://your-tunnel.ngrok.io/wp-admin/admin-post.php?action=hw_jira_oauth_callback' );
	 */
	public static function get_redirect_uri(): string {
		if ( defined( 'HW_JIRA_OAUTH_REDIRECT_URI' ) && HW_JIRA_OAUTH_REDIRECT_URI ) {
			return HW_JIRA_OAUTH_REDIRECT_URI;
		}
		return admin_url( 'admin-post.php?action=hw_jira_oauth_callback' );
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private function exchange_code( string $code ): array|\WP_Error {
		return $this->request_tokens( [
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'code'          => $code,
			'redirect_uri'  => self::get_redirect_uri(),
		] );
	}

	private function request_tokens( array $params ): array|\WP_Error {

		$response = wp_remote_post( self::TOKEN_URL, [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode( $params ),
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) return $response;

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

		if ( $code !== 200 ) {
			$msg = $data['error_description'] ?? $data['error'] ?? "Token endpoint returned HTTP {$code}";
			return new \WP_Error( 'token_error', $msg );
		}

		if ( empty( $data['access_token'] ) ) {
			return new \WP_Error( 'token_missing', 'Access token missing from Atlassian response.' );
		}

		return $data;
	}

	/**
	 * Return the first accessible Jira Cloud resource for the authorized account.
	 *
	 * @return array{ id: string, name: string, url: string }|\WP_Error
	 */
	private function fetch_primary_resource( string $access_token ): array|\WP_Error {

		$response = wp_remote_get( self::RESOURCES_URL, [
			'headers' => [
				'Authorization' => "Bearer {$access_token}",
				'Accept'        => 'application/json',
			],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) return $response;

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

		if ( $code !== 200 ) {
			return new \WP_Error( 'resources_error', "Accessible-resources returned HTTP {$code}" );
		}

		if ( empty( $data ) || empty( $data[0]['id'] ) ) {
			return new \WP_Error( 'no_resources', 'No Jira Cloud sites found for this Atlassian account.' );
		}

		return $data[0];
	}

	private function cfg( string $key ): string {
		$constants = [
			'client_id'     => 'HW_JIRA_CLIENT_ID',
			'client_secret' => 'HW_JIRA_CLIENT_SECRET',
		];
		$constant = $constants[ $key ] ?? '';
		if ( $constant && defined( $constant ) ) return (string) constant( $constant );
		return (string) get_network_option( null, "hw_jira_{$key}", '' );
	}
}
