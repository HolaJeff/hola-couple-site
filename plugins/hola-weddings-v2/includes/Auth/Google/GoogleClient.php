<?php

namespace HW\Auth\Google;

/**
 * Google OAuth 2.0 authorization code flow.
 *
 * Setup:
 *   1. https://console.cloud.google.com/apis/credentials
 *   2. Create credentials → OAuth client ID → Web application
 *   3. Authorized redirect URI → the "OAuth Callback URL" shown in
 *      Network Settings → Google (one URL covers the whole network)
 *   4. Copy the Client ID / Client Secret into that same tab
 *      (or define HW_GOOGLE_CLIENT_ID / HW_GOOGLE_CLIENT_SECRET in wp-config.php)
 *
 * The client secret never leaves the server — the browser only ever carries the
 * opaque state token and the single-use authorization code.
 */
class GoogleClient {

	const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
	const TOKEN_URL = 'https://oauth2.googleapis.com/token';
	const SCOPES    = 'openid email profile';

	private string $client_id;
	private string $client_secret;

	public function __construct() {
		$this->client_id     = hw_get_google_client_id();
		$this->client_secret = hw_get_google_client_secret();
	}

	/** True when both halves of the credential pair are present. */
	public function is_configured(): bool {

		return $this->client_id !== '' && $this->client_secret !== '';
	}

	/**
	 * Build the Google consent screen URL.
	 *
	 * @param string $state Opaque single-use CSRF token from StateStore.
	 * @param string $nonce Value Google must echo back inside the ID token.
	 * @param string $login_hint Optional email to pre-select an account.
	 */
	public function get_authorization_url( string $state, string $nonce = '', string $login_hint = '' ): string {

		$params = [
			'client_id'     => $this->client_id,
			'redirect_uri'  => hw_get_google_callback_url(),
			'response_type' => 'code',
			'scope'         => self::SCOPES,
			'state'         => $state,
			// online: we never call Google APIs on the user's behalf after login,
			// so there is no refresh token to store.
			'access_type'   => 'online',
			'prompt'        => 'select_account',
		];

		if ( $nonce !== '' ) {
			$params['nonce'] = $nonce;
		}

		if ( $login_hint !== '' ) {
			$params['login_hint'] = $login_hint;
		}

		/**
		 * Filter the Google authorization request parameters.
		 *
		 * @param array $params
		 */
		$params = apply_filters( 'hw_google_authorization_params', $params );

		return self::AUTH_URL . '?' . http_build_query( $params );
	}

	/**
	 * Exchange an authorization code for the token set.
	 *
	 * @return array{id_token: string, access_token: string}|\WP_Error
	 */
	public function exchange_code( string $code ): array|\WP_Error {

		$response = wp_remote_post( self::TOKEN_URL, [
			'headers' => [
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_uri'  => hw_get_google_callback_url(),
			],
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

		if ( $status !== 200 ) {
			$message = $data['error_description'] ?? $data['error'] ?? "Token endpoint returned HTTP {$status}";
			return new \WP_Error( 'hw_google_token_error', $message );
		}

		if ( empty( $data['id_token'] ) ) {
			return new \WP_Error( 'hw_google_token_error', 'ID token missing from the Google token response.' );
		}

		return $data;
	}
}
