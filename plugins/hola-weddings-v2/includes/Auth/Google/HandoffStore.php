<?php

namespace HW\Auth\Google;

/**
 * Single-use ticket that carries an authenticated user from the network-wide
 * callback back to the site the login started on.
 *
 * One Google client means one registered redirect URI, so the callback always
 * runs on the main site. The auth cookie has to be set on the origin site
 * instead — this ticket is what lets that happen without ever putting a user ID
 * in a URL that could be forged.
 *
 * Tickets live for a minute, work once, and are bound to the browser that
 * started the flow.
 */
class HandoffStore {

	const PREFIX = 'hw_gauth_handoff_';
	const TTL    = 60;

	/**
	 * Mint a ticket for a resolved user.
	 *
	 * @param int    $user_id     User to log in on the target site.
	 * @param int    $blog_id     Site the ticket may be redeemed on.
	 * @param string $redirect_to Where to send the user afterwards.
	 */
	public static function issue( int $user_id, int $blog_id, string $redirect_to ): string {

		$ticket = wp_generate_password( 48, false );

		set_site_transient( self::key( $ticket ), [
			'user_id'     => $user_id,
			'blog_id'     => $blog_id,
			'redirect_to' => $redirect_to,
			'ua_hash'     => self::ua_hash(),
			'created'     => time(),
		], self::TTL );

		return $ticket;
	}

	/**
	 * Redeem a ticket for the current site.
	 *
	 * @return array|null Null when the ticket is unknown, expired, already used,
	 *                    issued for another site or presented by another browser.
	 */
	public static function consume( string $ticket, int $blog_id ): ?array {

		if ( $ticket === '' ) {
			return null;
		}

		$key     = self::key( $ticket );
		$payload = get_site_transient( $key );

		delete_site_transient( $key );

		if ( ! is_array( $payload ) || empty( $payload['user_id'] ) ) {
			return null;
		}

		if ( (int) ( $payload['blog_id'] ?? 0 ) !== $blog_id ) {
			return null;
		}

		if ( ! hash_equals( (string) ( $payload['ua_hash'] ?? '' ), self::ua_hash() ) ) {
			return null;
		}

		return $payload;
	}

	private static function key( string $ticket ): string {

		return self::PREFIX . hash( 'sha256', $ticket );
	}

	/**
	 * Weak browser binding. Not a security boundary on its own — the ticket is
	 * already short-lived and single-use — but it makes a leaked URL useless in
	 * a different client.
	 */
	private static function ua_hash(): string {

		return wp_hash( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
	}
}
