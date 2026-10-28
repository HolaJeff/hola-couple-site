<?php

namespace HW\Feedback;

/**
 * Transient-based rate limiter for feedback submissions.
 * Keyed by logged-in user ID or a hash of the remote IP.
 */
class RateLimiter {

	private int $limit;
	private int $window; // seconds

	public function __construct() {
		$this->limit  = max( 1, (int) hw_get_option( 'hw_feedback_rate_limit',  5   ) );
		$this->window = max( 60, (int) hw_get_option( 'hw_feedback_rate_window', 600 ) );
	}

	/**
	 * Return true when the caller is within the allowed rate.
	 */
	public function check(): bool {
		return (int) get_transient( $this->key() ) < $this->limit;
	}

	/**
	 * Record one submission against the rate limit.
	 */
	public function record(): void {
		$key   = $this->key();
		$count = (int) get_transient( $key );

		if ( $count === 0 ) {
			set_transient( $key, 1, $this->window );
		} else {
			// Refresh TTL so the window slides from the most recent submission.
			set_transient( $key, $count + 1, $this->window );
		}
	}

	// -------------------------------------------------------------------------

	private function key(): string {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return "hw_fb_rate_u{$user_id}";
		}

		// Hash the IP so we do not persist raw addresses in the DB.
		return 'hw_fb_rate_ip_' . md5( $this->remote_ip() );
	}

	private function remote_ip(): string {
		// Trust proxy headers only for the known-safe header order.
		$candidates = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		];

		foreach ( $candidates as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			// X-Forwarded-For may be a comma-separated list; use the first entry.
			$ip = trim( explode( ',', $_SERVER[ $header ] )[0] );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		return '0.0.0.0';
	}
}
