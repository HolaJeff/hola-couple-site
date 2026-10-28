<?php

namespace HW\Auth;

/**
 * Creates accounts from the sign-up form.
 *
 * The only email/password registration path in the plugin. Google sign-up lives
 * in Auth\Google\UserResolver and the booking form's optional "Create an
 * account" lives in Booking\QuoteController; all three end up in the same shape
 * — a subscriber on the current blog, unverified unless the identity provider
 * already proved the address.
 *
 * Sending the verification email is deliberately this class's job rather than a
 * user_register hook. EmailVerificationController::on_user_register() only
 * stamps state, precisely so that accounts provisioned from a verified Google
 * identity are never mailed a link they do not need.
 */
class RegistrationService {

	const META_SOURCE = 'hw_signup_source';

	/** Shortest password accepted. Matches the booking form's account fields. */
	const MIN_PASSWORD_LENGTH = 8;

	/**
	 * Validate a submission.
	 *
	 * Collects every problem rather than stopping at the first, so the form can
	 * mark all the offending fields in one round trip.
	 *
	 * @param array $data Raw (already unslashed) field values.
	 * @return array Field key => message. Empty when the submission is good.
	 */
	public function validate( array $data ): array {

		$errors = [];

		$first    = trim( (string) ( $data['first_name'] ?? '' ) );
		$email    = trim( (string) ( $data['email'] ?? '' ) );
		$password = (string) ( $data['password'] ?? '' );
		$confirm  = (string) ( $data['password_confirm'] ?? '' );

		if ( $first === '' ) {
			$errors['first_name'] = __( 'Please enter your first name.', 'hola-weddings' );
		}

		if ( $email === '' ) {

			$errors['email'] = __( 'Please enter your email address.', 'hola-weddings' );

		} elseif ( ! is_email( $email ) ) {

			$errors['email'] = __( 'Please enter a valid email address.', 'hola-weddings' );

		} elseif ( email_exists( $email ) ) {

			// Deliberately explicit. It confirms the address is registered, but
			// wp_authenticate's distinct failure messages and the password reset
			// form already reveal that, so this adds no new exposure — and the
			// alternative silently strands someone who simply forgot they had an
			// account.
			$errors['email'] = __( 'An account already uses this email address. Please sign in instead.', 'hola-weddings' );
		}

		if ( $password === '' ) {

			$errors['password'] = __( 'Please choose a password.', 'hola-weddings' );

		} elseif ( mb_strlen( $password ) < self::MIN_PASSWORD_LENGTH ) {

			$errors['password'] = sprintf(
				/* translators: %d: minimum number of characters. */
				__( 'Password must be at least %d characters.', 'hola-weddings' ),
				self::MIN_PASSWORD_LENGTH
			);

		} elseif ( ! hash_equals( $password, $confirm ) ) {

			$errors['password_confirm'] = __( 'The passwords do not match.', 'hola-weddings' );
		}

		return $errors;
	}

	/**
	 * Create the account.
	 *
	 * Assumes validate() has already passed.
	 *
	 * @param array $data first_name, last_name, email, password.
	 * @return int|\WP_Error New user id.
	 */
	public function register( array $data ): int|\WP_Error {

		$first    = sanitize_text_field( trim( (string) ( $data['first_name'] ?? '' ) ) );
		$last     = sanitize_text_field( trim( (string) ( $data['last_name'] ?? '' ) ) );
		$email    = sanitize_email( trim( (string) ( $data['email'] ?? '' ) ) );
		$password = (string) ( $data['password'] ?? '' );

		$user_id = wp_insert_user( [
			'user_login'   => hw_get_unique_username( $this->usernameSeed( $email ) ),
			// Raw on purpose — wp_insert_user() hashes it.
			'user_pass'    => $password,
			'user_email'   => $email,
			'first_name'   => $first,
			'last_name'    => $last,
			'display_name' => trim( "{$first} {$last}" ) ?: $first,
			'role'         => get_option( 'default_role', 'subscriber' ),
		] );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		hw_add_user_to_current_blog( $user_id );

		update_user_meta( $user_id, self::META_SOURCE, 'form' );

		// The account exists but is not trusted yet: nothing of theirs shows up
		// until they prove the address.
		hw_email_verification()->sendVerificationEmail( $user_id );

		/**
		 * Fires after an account is created from the sign-up form.
		 *
		 * @param int    $user_id
		 * @param string $source
		 */
		do_action( 'hw_user_registered', $user_id, 'form' );

		return $user_id;
	}

	/**
	 * Seed hw_get_unique_username() from the email's local part.
	 *
	 * Preferred over the booking form's first-initial + surname, which can
	 * produce two-character logins. Falls back to a generic seed when the local
	 * part carries nothing usable.
	 */
	private function usernameSeed( string $email ): string {

		$local = strstr( $email, '@', true ) ?: $email;
		$seed  = preg_replace( '/[^a-z0-9]/', '', strtolower( $local ) );

		return strlen( (string) $seed ) >= 4 ? (string) $seed : 'user';
	}
}
