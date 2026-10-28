<?php

namespace HW\Auth\Google;

/**
 * Immutable value object holding the verified claims of a Google ID token.
 *
 * Only build this through IdTokenVerifier — an instance is a promise that the
 * signature, issuer, audience, expiry and email_verified claims all checked out.
 */
class GoogleIdentity {

	public function __construct(
		public readonly string $sub,
		public readonly string $email,
		public readonly string $first_name = '',
		public readonly string $last_name  = '',
		public readonly string $full_name  = '',
		public readonly string $picture    = '',
		public readonly string $locale     = '',
		public readonly string $hd         = ''
	) {}

	/**
	 * Build from a verified ID token claim set.
	 *
	 * @param array $claims Decoded ID token payload.
	 */
	public static function fromClaims( array $claims ): self {

		return new self(
			(string) ( $claims['sub']         ?? '' ),
			(string) ( $claims['email']       ?? '' ),
			(string) ( $claims['given_name']  ?? '' ),
			(string) ( $claims['family_name'] ?? '' ),
			(string) ( $claims['name']        ?? '' ),
			(string) ( $claims['picture']     ?? '' ),
			(string) ( $claims['locale']      ?? '' ),
			(string) ( $claims['hd']          ?? '' )
		);
	}

	/**
	 * Seed for hw_get_unique_username() — first initial + last name, falling back
	 * to the local part of the email address.
	 */
	public function getUsernameSeed(): string {

		$seed = '';

		if ( $this->first_name !== '' && $this->last_name !== '' ) {
			$seed = mb_substr( $this->first_name, 0, 1 ) . $this->last_name;
		} elseif ( $this->full_name !== '' ) {
			$seed = str_replace( ' ', '', $this->full_name );
		}

		if ( $seed === '' ) {
			$seed = strstr( $this->email, '@', true ) ?: $this->email;
		}

		$seed = sanitize_user( $seed, true );

		return $seed !== '' ? $seed : 'user';
	}

	/**
	 * Display name to store on the user record.
	 */
	public function getDisplayName(): string {

		if ( $this->full_name !== '' ) {
			return $this->full_name;
		}

		$name = trim( $this->first_name . ' ' . $this->last_name );

		return $name !== '' ? $name : $this->email;
	}
}
