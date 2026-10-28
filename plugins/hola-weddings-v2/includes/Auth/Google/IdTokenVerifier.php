<?php

namespace HW\Auth\Google;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;

/**
 * Verifies a Google ID token locally.
 *
 * Signature is checked against Google's published JWKS (cached in a site
 * transient), then every claim that matters is asserted: issuer, audience,
 * expiry, issued-at and email_verified. Nothing here trusts the token payload
 * before the signature passes.
 *
 * Local verification is used rather than the tokeninfo endpoint so login does
 * not depend on an extra Google round-trip per attempt.
 */
class IdTokenVerifier {

	const JWKS_URL       = 'https://www.googleapis.com/oauth2/v3/certs';
	const JWKS_TRANSIENT = 'hw_google_jwks';
	const JWKS_MIN_TTL   = HOUR_IN_SECONDS;
	const JWKS_MAX_TTL   = DAY_IN_SECONDS;

	/** Tolerance for clock skew between this server and Google, in seconds. */
	const LEEWAY = 60;

	const VALID_ISSUERS = [
		'accounts.google.com',
		'https://accounts.google.com',
	];

	/**
	 * Verify a raw ID token and return the identity it asserts.
	 *
	 * @param string $id_token The compact-serialized JWT from the token response.
	 * @param string $expected_nonce Optional nonce that must match the token claim.
	 * @return GoogleIdentity|\WP_Error
	 */
	public function verify( string $id_token, string $expected_nonce = '' ): GoogleIdentity|\WP_Error {

		$client_id = hw_get_google_client_id();

		if ( $client_id === '' ) {
			return new \WP_Error( 'hw_google_not_configured', 'No Google client ID configured.' );
		}

		try {
			$jws = ( new CompactSerializer() )->unserialize( $id_token );
		} catch ( \Throwable ) {
			return new \WP_Error( 'hw_google_malformed_token', 'The ID token could not be parsed.' );
		}

		$header = $jws->getSignature( 0 )->getProtectedHeader();

		// The algorithm manager below only knows RS256, so anything else would
		// fail anyway — rejecting it here just makes the reason legible.
		if ( ( $header['alg'] ?? '' ) !== 'RS256' ) {
			return new \WP_Error( 'hw_google_invalid_algorithm', 'The ID token is not signed with RS256.' );
		}

		$algorithm = new RS256();
		$kid       = (string) ( $header['kid'] ?? '' );
		$keys      = $this->get_key_set();

		if ( is_wp_error( $keys ) ) {
			return $keys;
		}

		// Refresh only when the cached set does not contain the key the token
		// names. Retrying on a failed signature instead would let anyone drive an
		// outbound request per forged token.
		if ( $kid !== '' && ! $keys->has( $kid ) ) {

			$keys = $this->get_key_set( true );

			if ( is_wp_error( $keys ) ) {
				return $keys;
			}

			if ( ! $keys->has( $kid ) ) {
				return new \WP_Error( 'hw_google_unknown_key', 'The ID token names a signing key Google does not publish.' );
			}
		}

		try {
			$verifier = new JWSVerifier( new AlgorithmManager( [ $algorithm ] ) );

			// With a kid we can pin the check to that one key; without one, any
			// current Google key is acceptable.
			$valid = $kid !== ''
				? $verifier->verifyWithKey( $jws, $keys->get( $kid ), 0 )
				: $verifier->verifyWithKeySet( $jws, $keys, 0 );

		} catch ( \Throwable ) {
			$valid = false;
		}

		if ( ! $valid ) {
			return new \WP_Error( 'hw_google_invalid_signature', 'The ID token signature could not be verified.' );
		}

		$claims = json_decode( (string) $jws->getPayload(), true );

		if ( ! is_array( $claims ) ) {
			return new \WP_Error( 'hw_google_malformed_token', 'The ID token payload is not valid JSON.' );
		}

		$error = $this->check_claims( $claims, $client_id, $expected_nonce );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return GoogleIdentity::fromClaims( $claims );
	}

	// ── Claim validation ──────────────────────────────────────────────────────

	/**
	 * @return true|\WP_Error
	 */
	private function check_claims( array $claims, string $client_id, string $expected_nonce ): true|\WP_Error {

		$now = time();

		if ( ! in_array( (string) ( $claims['iss'] ?? '' ), self::VALID_ISSUERS, true ) ) {
			return new \WP_Error( 'hw_google_invalid_issuer', 'Unexpected ID token issuer.' );
		}

		// aud is a single string for web clients, but compare defensively.
		$audiences = (array) ( $claims['aud'] ?? [] );

		if ( ! in_array( $client_id, array_map( 'strval', $audiences ), true ) ) {
			return new \WP_Error( 'hw_google_invalid_audience', 'The ID token was issued for a different client ID.' );
		}

		if ( empty( $claims['exp'] ) || ( (int) $claims['exp'] + self::LEEWAY ) < $now ) {
			return new \WP_Error( 'hw_google_token_expired', 'The ID token has expired.' );
		}

		if ( ! empty( $claims['iat'] ) && ( (int) $claims['iat'] - self::LEEWAY ) > $now ) {
			return new \WP_Error( 'hw_google_token_not_yet_valid', 'The ID token was issued in the future.' );
		}

		if ( empty( $claims['sub'] ) ) {
			return new \WP_Error( 'hw_google_no_subject', 'The ID token carries no subject identifier.' );
		}

		if ( empty( $claims['email'] ) || ! is_email( (string) $claims['email'] ) ) {
			return new \WP_Error( 'hw_google_no_email', 'The ID token carries no usable email address.' );
		}

		// Account linking and provisioning both key off the email address, so an
		// unverified one is never good enough.
		if ( empty( $claims['email_verified'] ) || ! filter_var( $claims['email_verified'], FILTER_VALIDATE_BOOLEAN ) ) {
			return new \WP_Error( 'hw_google_email_unverified', 'The Google account email address is not verified.' );
		}

		if ( $expected_nonce !== '' && ! hash_equals( $expected_nonce, (string) ( $claims['nonce'] ?? '' ) ) ) {
			return new \WP_Error( 'hw_google_nonce_mismatch', 'The ID token nonce does not match this login attempt.' );
		}

		return true;
	}

	// ── JWKS handling ─────────────────────────────────────────────────────────

	/**
	 * Google's current signing keys, cached network-wide.
	 *
	 * @param bool $force Bypass and refresh the cache.
	 * @return JWKSet|\WP_Error
	 */
	private function get_key_set( bool $force = false ): JWKSet|\WP_Error {

		if ( ! $force ) {

			$cached = get_site_transient( self::JWKS_TRANSIENT );

			if ( is_array( $cached ) && ! empty( $cached['keys'] ) ) {

				try {
					return JWKSet::createFromKeyData( $cached );
				} catch ( \Throwable ) {
					// Cached payload is unusable — fall through and refetch.
					delete_site_transient( self::JWKS_TRANSIENT );
				}
			}
		}

		$response = wp_remote_get( self::JWKS_URL, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ],
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'hw_google_jwks_error', 'Could not reach Google to fetch signing keys: ' . $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( $status !== 200 ) {
			return new \WP_Error( 'hw_google_jwks_error', "Google signing key endpoint returned HTTP {$status}." );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['keys'] ) ) {
			return new \WP_Error( 'hw_google_jwks_error', 'Google returned no signing keys.' );
		}

		try {
			$key_set = JWKSet::createFromKeyData( $data );
		} catch ( \Throwable ) {
			return new \WP_Error( 'hw_google_jwks_error', 'Google signing keys could not be parsed.' );
		}

		set_site_transient( self::JWKS_TRANSIENT, $data, $this->get_cache_ttl( $response ) );

		return $key_set;
	}

	/**
	 * Honour Google's Cache-Control max-age, clamped to a sane range.
	 *
	 * @param array|\WP_Error $response
	 */
	private function get_cache_ttl( $response ): int {

		$header  = (string) wp_remote_retrieve_header( $response, 'cache-control' );
		$max_age = 0;

		if ( preg_match( '/max-age\s*=\s*(\d+)/i', $header, $matches ) ) {
			$max_age = (int) $matches[1];
		}

		return min( self::JWKS_MAX_TTL, max( self::JWKS_MIN_TTL, $max_age ) );
	}
}
