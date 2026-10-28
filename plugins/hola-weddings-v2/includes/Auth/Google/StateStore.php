<?php

namespace HW\Auth\Google;

/**
 * Single-use CSRF state for the Google redirect flow.
 *
 * The browser only ever carries the opaque token; everything the callback needs
 * to finish the login (which site it started on, where to return, the OIDC
 * nonce) lives server-side in a network-wide transient. Consuming a token
 * deletes it, so a replayed ?state= is rejected.
 */
class StateStore {

	const PREFIX = 'hw_gauth_state_';
	const TTL    = 600; // 10 minutes — long enough to pick an account, short enough to be useless later.

	/**
	 * Store a login attempt and return the token to hand to Google.
	 *
	 * @param array $payload blog_id / redirect_to / nonce.
	 */
	public static function issue( array $payload ): string {

		$token = wp_generate_password( 48, false );

		$payload['created'] = time();

		set_site_transient( self::key( $token ), $payload, self::TTL );

		return $token;
	}

	/**
	 * Fetch and destroy a login attempt.
	 *
	 * @return array|null Null when the token is unknown, already used or expired.
	 */
	public static function consume( string $token ): ?array {

		if ( $token === '' ) {
			return null;
		}

		$key     = self::key( $token );
		$payload = get_site_transient( $key );

		delete_site_transient( $key );

		return is_array( $payload ) ? $payload : null;
	}

	/**
	 * Transient keys are derived from a hash so the raw token is never stored
	 * verbatim and the key length stays bounded.
	 */
	private static function key( string $token ): string {

		return self::PREFIX . hash( 'sha256', $token );
	}
}
