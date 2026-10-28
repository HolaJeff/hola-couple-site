<?php

namespace HW\Auth;

/**
 * AJAX endpoint for the sign-up pane.
 *
 * Kept out of HW\Ajax deliberately: that class predates any nonce discipline
 * (neither ajax_login nor hw_reset_password checks one), and a registration
 * endpoint should not inherit that. This follows the newer Auth\EmailVerification*
 * split instead — a controller for the wiring, a service for the rules.
 */
class RegistrationController {

	use \HW\Traits\Singleton;

	const ACTION = 'hw_signup';
	const NONCE  = 'hw_signup';

	/** Attempts per IP per window. The nonce is CSRF only — this is the real throttle. */
	const RATE_LIMIT  = 5;
	const RATE_WINDOW = 900;

	private RegistrationService $service;

	public function __construct() {

		$this->service = new RegistrationService();

		// Registered for both, not nopriv only: a logged-in caller hitting a
		// nopriv-only action gets a bare "0", which is impossible to act on.
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ $this, 'handle' ] );
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'handle' ] );
	}

	public function getService(): RegistrationService {

		return $this->service;
	}

	/**
	 * Create an account from the sign-up form.
	 *
	 * @return void Always ends in wp_send_json().
	 */
	public function handle(): void {

		// 1. CSRF.
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			$this->fail( __( 'Your session expired. Please refresh the page and try again.', 'hola-weddings' ), [], 403 );
		}

		// 2. Nothing to do for someone who is already signed in.
		if ( is_user_logged_in() ) {
			$this->fail( __( 'You are already signed in.', 'hola-weddings' ), [], 403 );
		}

		// 3. The switch closes registration, not just the form.
		if ( ! hw_is_signup_enabled() ) {
			$this->fail( __( 'Sign up is not available on this site.', 'hola-weddings' ), [], 403 );
		}

		// 4. Throttle before doing any work. A nonce is a shared per-day value for
		//    a logged-out visitor, so this is what actually blunts scripted signup.
		$limiter = new RateLimiter( 'signup', self::RATE_LIMIT, self::RATE_WINDOW );

		if ( ! $limiter->check() ) {
			$this->fail( __( 'Too many attempts. Please wait a few minutes and try again.', 'hola-weddings' ), [], 429 );
		}

		$limiter->record();

		$data = [
			'first_name'       => sanitize_text_field( wp_unslash( $_POST['signup_first_name'] ?? '' ) ),
			'last_name'        => sanitize_text_field( wp_unslash( $_POST['signup_last_name'] ?? '' ) ),
			'email'            => sanitize_email( wp_unslash( $_POST['signup_email'] ?? '' ) ),
			// Not sanitised: a password is a credential, not display text, and
			// sanitize_text_field() would silently alter it before hashing.
			'password'         => (string) wp_unslash( $_POST['signup_password'] ?? '' ),
			'password_confirm' => (string) wp_unslash( $_POST['signup_password_confirm'] ?? '' ),
		];

		// 5. Validation.
		$errors = $this->service->validate( $data );

		if ( ! empty( $errors ) ) {
			$this->fail( '', $errors );
		}

		$user_id = $this->service->register( $data );

		if ( is_wp_error( $user_id ) ) {

			// Core's message can leak internals, so log it and show a generic one.
			hw_write_log( $user_id );

			$this->fail( __( 'We could not create your account. Please try again or contact us.', 'hola-weddings' ) );
		}

		// Sign them straight in. Safe because the account area holds an unverified
		// user at /account/verify-email/, which is exactly the "check your inbox"
		// screen this flow wants — and the resend button there needs a session.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, false );

		$user = get_userdata( $user_id );

		/** Standard WP login hook, so anything listening for a login still fires. */
		do_action( 'wp_login', $user->user_login, $user );

		/**
		 * Filters where a new account lands.
		 *
		 * The frontend can still veto the redirect entirely by cancelling the
		 * hw:auth:registered event — that is how the booking form keeps someone
		 * on their booking instead of sending them to the verification screen.
		 *
		 * @param string $redirect
		 * @param int    $user_id
		 */
		$redirect = apply_filters( 'hw_signup_redirect', hw_get_verify_email_url(), $user_id );

		wp_send_json( [
			'success'      => true,
			'userId'       => $user_id,
			'redirect'     => $redirect,
			'message'      => '',
			// Always required for a brand new account, but built the same way as
			// the sign-in response so the modal has one shape to read.
			'verification' => hw_get_verification_payload( $user_id ),
		] );
	}

	/**
	 * Send a failure response and stop.
	 *
	 * @param string $message Shown as a banner above the form.
	 * @param array  $errors  Field key => message, keys without the signup_ prefix.
	 * @param int    $status  HTTP status.
	 * @return void
	 */
	private function fail( string $message = '', array $errors = [], int $status = 200 ): void {

		wp_send_json( [
			'success' => false,
			'message' => $message,
			'errors'  => $errors,
		], $status );
	}
}
