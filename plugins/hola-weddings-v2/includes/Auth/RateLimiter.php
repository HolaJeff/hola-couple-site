<?php

namespace HW\Auth;

/**
 * Transient-based rate limiter for authentication attempts.
 *
 * Keyed by remote IP and stored network-wide, so a flow that starts on one site
 * and finishes on another shares one counter. Provider agnostic: callers name
 * their own bucket, e.g. 'google_start' for OAuth starts, and could just as well
 * throttle password logins.
 *
 * This blunts scripted probing — forged codes, replayed state, guessed
 * credentials — it is not a substitute for verifying anything.
 */
class RateLimiter {

	const PREFIX = 'hw_auth_rl_';

	private string $bucket;
	private int    $limit;
	private int    $window;

	public function __construct( string $bucket = 'google', int $limit = 15, int $window = 600 ) {

		$this->bucket = $bucket;
		$this->limit  = max( 1, (int) apply_filters( 'hw_auth_rate_limit', $limit, $bucket ) );
		$this->window = max( 60, (int) apply_filters( 'hw_auth_rate_window', $window, $bucket ) );
	}

	/** True while the caller is still within the allowed rate. */
	public function check(): bool {

		return (int) get_site_transient( $this->key() ) < $this->limit;
	}

	/** Record one attempt against the window. */
	public function record(): void {

		$key   = $this->key();
		$count = (int) get_site_transient( $key );

		// TTL is refreshed on every hit so the window slides from the last attempt.
		set_site_transient( $key, $count + 1, $this->window );
	}

	/** Clear the counter — called after a successful login. */
	public function reset(): void {

		delete_site_transient( $this->key() );
	}

	private function key(): string {

		$ip = hw_get_the_user_ip();

		return self::PREFIX . $this->bucket . '_' . hash( 'sha256', (string) $ip );
	}
}
