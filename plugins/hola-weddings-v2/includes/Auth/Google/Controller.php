<?php

namespace HW\Auth\Google;

use HW\Auth\RateLimiter;
use HW\Traits\Singleton;

/**
 * Front controller for "Sign in with Google" (OAuth 2.0 authorization code flow).
 *
 * Three steps, all reachable while logged out, dispatched from ?hw_auth= on the
 * site's own front end:
 *
 *   ?hw_auth=google           (any site)  → build state, redirect to Google
 *   ?hw_auth=google-callback  (main site) → verify state, exchange code, verify
 *                                           ID token, resolve user, mint a ticket
 *   ?hw_auth=google-handoff   (origin)    → redeem the ticket, set the auth cookie
 *
 * A query parameter on home_url() rather than a rewrite or admin-post.php: it
 * resolves whatever the permalink settings are, so there are no rewrite rules to
 * flush and nothing breaks on a fresh site, and it stays clear of /wp-admin/ —
 * which fires admin_init and is routinely blocked for logged-out visitors by
 * security plugins and host rules. The callback is always a logged-out request,
 * so that mattered most there.
 *
 * The callback always lands on the main site because one Google client means one
 * registered redirect URI. The handoff hop is what puts the auth cookie on the
 * wedding site the visitor actually started from.
 *
 * Errors never render: every failure redirects back with ?hw_auth_error=<code>,
 * which the frontend can turn into copy via hw_get_google_auth_error_message().
 */
class Controller {

	use Singleton;

	/** Query parameter naming the step to run. */
	const QUERY_VAR = 'hw_auth';

	public function __construct() {

		// Early, so the request is answered before themes and other plugins do
		// work that a redirect would throw away.
		add_action( 'init', [ $this, 'dispatch' ], 5 );
	}

	/**
	 * Route ?hw_auth= to the right step.
	 */
	public function dispatch(): void {

		// init also fires for admin, cron and REST requests, where these URLs
		// mean nothing. is_admin() covers admin-ajax.php too.
		if ( is_admin() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$step = isset( $_GET[ self::QUERY_VAR ] )
			? sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) )
			: '';

		if ( $step === '' ) {
			return;
		}

		// These URLs carry single-use codes and tickets — no proxy or page cache
		// should ever hold on to one.
		nocache_headers();

		match ( $step ) {
			'google'          => $this->handle_start(),
			'google-callback' => $this->handle_callback(),
			'google-handoff'  => $this->handle_handoff(),
			default           => null,
		};
	}

	// ── Step 1: start ─────────────────────────────────────────────────────────

	/**
	 * Kick off the flow: remember where we came from, then hand the visitor to
	 * Google's consent screen.
	 */
	public function handle_start(): void {

		$redirect_to = $this->resolve_start_redirect();

		if ( is_user_logged_in() ) {
			$this->redirect( $redirect_to );
		}

		if ( ! hw_is_google_login_configured() ) {
			$this->fail( 'google_not_available', $redirect_to, 'Google login requested while disabled or unconfigured.' );
		}

		$limiter = new RateLimiter( 'google_start' );

		if ( ! $limiter->check() ) {
			$this->fail( 'google_rate_limited', $redirect_to );
		}

		$limiter->record();

		$nonce = wp_generate_password( 32, false );

		$state = StateStore::issue( [
			'blog_id'     => get_current_blog_id(),
			'redirect_to' => $redirect_to,
			'nonce'       => $nonce,
		] );

		// External host, so wp_redirect rather than wp_safe_redirect.
		wp_redirect( ( new GoogleClient() )->get_authorization_url( $state, $nonce ) );
		exit;
	}

	// ── Step 2: callback ──────────────────────────────────────────────────────

	/**
	 * Google sends the visitor back here with ?code= and ?state=, or ?error=.
	 */
	public function handle_callback(): void {

		$code  = sanitize_text_field( wp_unslash( $_GET['code']  ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );

		// State carries the origin site and return URL, so consume it before
		// anything else — including before reporting Google's own error.
		$payload = StateStore::consume( $state );

		if ( ! is_array( $payload ) ) {
			$this->fail( 'google_state', '', 'Google callback with missing, expired or replayed state.' );
		}

		$blog_id     = (int) ( $payload['blog_id'] ?? get_current_blog_id() );
		$switched    = false;

		if ( is_multisite() && $blog_id && $blog_id !== get_current_blog_id() ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		// From here on every redirect target, option read and role assignment is
		// in the origin site's context.
		$redirect_to = wp_validate_redirect( (string) ( $payload['redirect_to'] ?? '' ), home_url( '/' ) );

		if ( $error !== '' ) {
			$this->fail(
				$error === 'access_denied' ? 'google_denied' : 'google_identity',
				$redirect_to,
				"Google returned error: {$error}",
				$switched
			);
		}

		if ( $code === '' ) {
			$this->fail( 'google_token', $redirect_to, 'Google callback carried no authorization code.', $switched );
		}

		if ( ! hw_is_google_login_configured() ) {
			$this->fail( 'google_not_available', $redirect_to, 'Google callback hit while disabled or unconfigured.', $switched );
		}

		$limiter = new RateLimiter( 'google_callback' );

		if ( ! $limiter->check() ) {
			$this->fail( 'google_rate_limited', $redirect_to, null, $switched );
		}

		$limiter->record();

		$tokens = ( new GoogleClient() )->exchange_code( $code );

		if ( is_wp_error( $tokens ) ) {
			$this->fail( 'google_token', $redirect_to, 'Token exchange failed: ' . $tokens->get_error_message(), $switched );
		}

		$identity = ( new IdTokenVerifier() )->verify( (string) $tokens['id_token'], (string) ( $payload['nonce'] ?? '' ) );

		if ( is_wp_error( $identity ) ) {
			$this->fail( 'google_identity', $redirect_to, 'ID token rejected: ' . $identity->get_error_message(), $switched );
		}

		$user_id = ( new UserResolver() )->resolve( $identity );

		if ( is_wp_error( $user_id ) ) {
			$this->fail(
				$this->map_resolver_error( $user_id->get_error_code() ),
				$redirect_to,
				'User resolution failed: ' . $user_id->get_error_message(),
				$switched
			);
		}

		// A completed login should not count against either bucket.
		$limiter->reset();
		( new RateLimiter( 'google_start' ) )->reset();

		$ticket      = HandoffStore::issue( (int) $user_id, $blog_id, $redirect_to );
		$handoff_url = hw_get_google_handoff_url( $blog_id, $ticket );

		if ( $switched ) {
			restore_current_blog();
		}

		// Self-built URL on a network site, which wp_safe_redirect would reject
		// under domain mapping.
		wp_redirect( $handoff_url );
		exit;
	}

	// ── Step 3: handoff ───────────────────────────────────────────────────────

	/**
	 * Redeem the single-use ticket and set the auth cookie on this site.
	 */
	public function handle_handoff(): void {

		$ticket  = sanitize_text_field( wp_unslash( $_GET['ticket'] ?? '' ) );
		$payload = HandoffStore::consume( $ticket, get_current_blog_id() );

		if ( ! is_array( $payload ) ) {
			$this->fail( 'google_handoff', '', 'Handoff ticket missing, expired, replayed or presented on the wrong site.' );
		}

		$user_id     = (int) $payload['user_id'];
		$redirect_to = wp_validate_redirect( (string) ( $payload['redirect_to'] ?? '' ), home_url( '/' ) );
		$user        = get_userdata( $user_id );

		if ( ! $user ) {
			$this->fail( 'google_handoff', $redirect_to, "Handoff ticket referenced missing user {$user_id}." );
		}

		// The auth cookie is shared network-wide on a single-domain install, so
		// the visitor may already be signed in by the time they get here.
		if ( get_current_user_id() !== $user_id ) {

			wp_set_auth_cookie( $user_id, false );
			wp_set_current_user( $user_id );

			/** Standard WP login hook, so anything listening for a login still fires. */
			do_action( 'wp_login', $user->user_login, $user );
		}

		$this->redirect( $redirect_to );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Where to land after login when the flow starts.
	 *
	 * An explicit ?redirect_to wins, then the site's configured preference, then
	 * the page the button was clicked on.
	 */
	private function resolve_start_redirect(): string {

		$requested = (string) wp_unslash( $_GET['redirect_to'] ?? '' );

		if ( $requested !== '' ) {
			return wp_validate_redirect( $requested, home_url( '/' ) );
		}

		if ( hw_get_google_login_redirect_to() === 'account' && function_exists( 'hw_get_account_url' ) ) {
			return hw_get_account_url();
		}

		$referer = wp_get_referer();

		return $referer ? wp_validate_redirect( $referer, home_url( '/' ) ) : home_url( '/' );
	}

	/**
	 * Translate a UserResolver error code into a frontend error code.
	 */
	private function map_resolver_error( string $code ): string {

		return match ( $code ) {
			'hw_google_signup_disabled'  => 'google_signup_disabled',
			'hw_google_account_disabled' => 'google_account_disabled',
			default                      => 'google_user_failed',
		};
	}

	/**
	 * Abort the flow, telling the frontend why without leaking detail.
	 *
	 * @param string      $code        Error code appended as ?hw_auth_error=.
	 * @param string      $redirect_to Where to send the visitor; home when empty.
	 * @param string|null $log         Operator-facing detail for the site log.
	 * @param bool        $switched    Whether a switch_to_blog() is outstanding.
	 */
	private function fail( string $code, string $redirect_to = '', ?string $log = null, bool $switched = false ): void {

		if ( $log !== null ) {
			$this->log( $log );
		}

		if ( $switched ) {
			restore_current_blog();
		}

		$target = $redirect_to !== '' ? $redirect_to : home_url( '/' );

		$this->redirect( add_query_arg( 'hw_auth_error', $code, $target ) );
	}

	/**
	 * Every URL reaching this point has already been through
	 * wp_validate_redirect() in the context of the site it belongs to, which is
	 * what wp_safe_redirect() would do — except it would reject a sibling
	 * network site under domain mapping.
	 */
	private function redirect( string $url ): void {

		wp_redirect( $url );
		exit;
	}

	/**
	 * Log an auth failure. Never receives tokens or codes — only our own summary.
	 */
	private function log( string $message ): void {

		try {

			if ( function_exists( 'hw' ) && hw()->getLogger() ) {

				hw()->getLogger()->error( $message, 'google-auth' );
			}
		} catch ( \Throwable ) {
			// Logging must never be the reason a login fails.
		}
	}
}
