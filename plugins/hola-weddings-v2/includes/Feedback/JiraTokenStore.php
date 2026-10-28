<?php

namespace HW\Feedback;

/**
 * Encrypted storage for Jira OAuth 2.0 tokens in WordPress network options.
 *
 * Tokens are AES-256-CBC encrypted at rest using AUTH_KEY as key material.
 * If AUTH_KEY is not defined the data is base64-encoded (plain) — still
 * inaccessible via the browser, but admins should always define AUTH_KEY.
 */
class JiraTokenStore {

	private const KEY_ACCESS_TOKEN  = 'hw_jira_access_token';
	private const KEY_REFRESH_TOKEN = 'hw_jira_refresh_token';
	private const KEY_EXPIRES_AT    = 'hw_jira_token_expires_at';
	private const KEY_CLOUD_ID      = 'hw_jira_cloud_id';
	private const KEY_SITE_NAME     = 'hw_jira_site_name';
	private const KEY_SITE_URL      = 'hw_jira_site_url';

	// ── Write ─────────────────────────────────────────────────────────────────

	/**
	 * Persist a token response alongside the cloud instance metadata.
	 *
	 * @param array  $tokens     Atlassian token response (access_token, refresh_token, expires_in).
	 * @param string $cloud_id   Jira Cloud instance ID.
	 * @param string $site_name  Human-readable site name (e.g. "Acme Corp").
	 * @param string $site_url   Base URL of the Jira site (e.g. "https://acme.atlassian.net").
	 */
	public static function save(
		array $tokens,
		string $cloud_id,
		string $site_name = '',
		string $site_url  = ''
	): void {
		update_network_option( null, self::KEY_ACCESS_TOKEN,
			self::encrypt( $tokens['access_token'] ) );

		// Subtract 60 s from expires_in so we never use a token that is about to expire.
		$expires_at = time() + max( 0, (int) ( $tokens['expires_in'] ?? 3600 ) ) - 60;
		update_network_option( null, self::KEY_EXPIRES_AT, $expires_at );

		// Atlassian may not return a new refresh token on every token-refresh call;
		// keep the stored one when the response omits it.
		if ( ! empty( $tokens['refresh_token'] ) ) {
			update_network_option( null, self::KEY_REFRESH_TOKEN,
				self::encrypt( $tokens['refresh_token'] ) );
		}

		update_network_option( null, self::KEY_CLOUD_ID, $cloud_id );

		if ( $site_name !== '' ) update_network_option( null, self::KEY_SITE_NAME, $site_name );
		if ( $site_url  !== '' ) update_network_option( null, self::KEY_SITE_URL,  $site_url  );
	}

	/** Remove all stored tokens and connection metadata. */
	public static function clear(): void {
		foreach ( [
			self::KEY_ACCESS_TOKEN,
			self::KEY_REFRESH_TOKEN,
			self::KEY_EXPIRES_AT,
			self::KEY_CLOUD_ID,
			self::KEY_SITE_NAME,
			self::KEY_SITE_URL,
		] as $key ) {
			delete_network_option( null, $key );
		}
	}

	// ── Read ──────────────────────────────────────────────────────────────────

	public static function get_access_token(): string {
		$enc = (string) get_network_option( null, self::KEY_ACCESS_TOKEN, '' );
		return $enc !== '' ? self::decrypt( $enc ) : '';
	}

	public static function get_refresh_token(): string {
		$enc = (string) get_network_option( null, self::KEY_REFRESH_TOKEN, '' );
		return $enc !== '' ? self::decrypt( $enc ) : '';
	}

	public static function get_cloud_id(): string {
		return (string) get_network_option( null, self::KEY_CLOUD_ID, '' );
	}

	public static function get_site_name(): string {
		return (string) get_network_option( null, self::KEY_SITE_NAME, '' );
	}

	public static function get_site_url(): string {
		return (string) get_network_option( null, self::KEY_SITE_URL, '' );
	}

	/** True when an access token and cloud ID are present (regardless of expiry). */
	public static function is_connected(): bool {
		return self::get_access_token() !== '' && self::get_cloud_id() !== '';
	}

	/** True when the stored access token has expired or will within the next 60 s. */
	public static function is_expired(): bool {
		$exp = (int) get_network_option( null, self::KEY_EXPIRES_AT, 0 );
		return time() >= $exp;
	}

	// ── Encryption ────────────────────────────────────────────────────────────

	private static function encrypt( string $plaintext ): string {
		if ( ! self::has_key() ) {
			return base64_encode( $plaintext );
		}

		$key        = self::derive_key();
		$iv         = random_bytes( 16 );
		$ciphertext = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( $ciphertext === false ) {
			return base64_encode( $plaintext );
		}

		return base64_encode( $iv . $ciphertext );
	}

	private static function decrypt( string $encoded ): string {
		if ( ! self::has_key() ) {
			$decoded = base64_decode( $encoded, true );
			return $decoded !== false ? $decoded : '';
		}

		$key  = self::derive_key();
		$data = base64_decode( $encoded, true );

		if ( $data === false || strlen( $data ) < 17 ) return '';

		$iv         = substr( $data, 0, 16 );
		$ciphertext = substr( $data, 16 );
		$result     = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		return $result !== false ? $result : '';
	}

	private static function has_key(): bool {
		return defined( 'AUTH_KEY' ) && strlen( AUTH_KEY ) >= 8;
	}

	private static function derive_key(): string {
		// Raw binary SHA-256 of AUTH_KEY, truncated to 32 bytes (AES-256).
		return substr( hash( 'sha256', AUTH_KEY, true ), 0, 32 );
	}
}
