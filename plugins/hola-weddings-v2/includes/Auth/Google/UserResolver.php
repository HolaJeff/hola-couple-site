<?php

namespace HW\Auth\Google;

/**
 * Maps a verified Google identity onto a WordPress user.
 *
 * Order of resolution:
 *   1. A user already linked to this Google account (hw_google_id meta).
 *   2. A user with the same email address — linked on the spot. This is safe
 *      here, and only here, because Google asserted the email_verified claim
 *      and IdTokenVerifier rejects the token without it. An email typed into a
 *      booking form carries no such proof and never resolves an account.
 *   3. A brand new account, when the site allows Google sign-up.
 *
 * The caller is responsible for setting the auth cookie; this class only ever
 * returns a user ID.
 */
class UserResolver {

	const META_GOOGLE_ID = 'hw_google_id';
	const META_LINKED_AT = 'hw_google_linked_at';
	const META_PICTURE   = 'hw_google_picture';
	const META_SOURCE    = 'hw_signup_source';

	/**
	 * @return int|\WP_Error User ID, or an error explaining why login is refused.
	 */
	public function resolve( GoogleIdentity $identity ): int|\WP_Error {

		$user = $this->findByGoogleId( $identity->sub );

		if ( ! $user ) {

			$user = get_user_by( 'email', $identity->email );

			if ( $user ) {
				$this->link( $user->ID, $identity );
			}
		}

		if ( $user ) {

			$blocked = $this->assertUsable( $user );

			if ( is_wp_error( $blocked ) ) {
				return $blocked;
			}

			$this->refreshProfile( $user->ID, $identity );
			$this->ensureBlogMembership( $user->ID );

			/**
			 * Fires when an existing account signs in through Google.
			 *
			 * @param int            $user_id
			 * @param GoogleIdentity $identity
			 */
			do_action( 'hw_google_user_logged_in', $user->ID, $identity );

			return $user->ID;
		}

		if ( ! hw_google_signup_allowed() ) {
			return new \WP_Error(
				'hw_google_signup_disabled',
				__( 'There is no account for this Google address, and sign-up with Google is turned off for this site.', 'hola-weddings' )
			);
		}

		return $this->create( $identity );
	}

	// ── Lookup ────────────────────────────────────────────────────────────────

	/**
	 * User meta is global on multisite, so a couple who signed up on one wedding
	 * site stays linked on every other one.
	 */
	private function findByGoogleId( string $sub ): ?\WP_User {

		if ( $sub === '' ) {
			return null;
		}

		$users = get_users( [
			'meta_key'    => self::META_GOOGLE_ID,
			'meta_value'  => $sub,
			'number'      => 1,
			'blog_id'     => 0, // search the whole network, not just this site
			'count_total' => false,
		] );

		return ! empty( $users[0] ) ? $users[0] : null;
	}

	/**
	 * Refuse logins for accounts the network has spammed or deleted.
	 *
	 * @return true|\WP_Error
	 */
	private function assertUsable( \WP_User $user ): true|\WP_Error {

		if ( is_multisite() && ( ! empty( $user->spam ) || ! empty( $user->deleted ) ) ) {
			return new \WP_Error(
				'hw_google_account_disabled',
				__( 'This account is no longer active. Please contact us for help.', 'hola-weddings' )
			);
		}

		return true;
	}

	// ── Creation & linking ────────────────────────────────────────────────────

	private function create( GoogleIdentity $identity ): int|\WP_Error {

		$username = hw_get_unique_username( $identity->getUsernameSeed() );

		$user_id = wp_insert_user( [
			'user_login'    => $username,
			// Random and never disclosed: the account is Google-only until the
			// user asks for a password reset.
			'user_pass'     => wp_generate_password( 32, true, true ),
			'user_email'    => $identity->email,
			'first_name'    => $identity->first_name,
			'last_name'     => $identity->last_name,
			'display_name'  => $identity->getDisplayName(),
			'role'          => get_option( 'default_role', 'subscriber' ),
		] );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$this->link( $user_id, $identity );
		update_user_meta( $user_id, self::META_SOURCE, 'google' );

		$this->ensureBlogMembership( $user_id );

		/**
		 * Fires right after a new account is provisioned from a Google login.
		 *
		 * @param int            $user_id
		 * @param GoogleIdentity $identity
		 */
		do_action( 'hw_google_user_registered', $user_id, $identity );

		return $user_id;
	}

	private function link( int $user_id, GoogleIdentity $identity ): void {

		update_user_meta( $user_id, self::META_GOOGLE_ID, $identity->sub );
		update_user_meta( $user_id, self::META_LINKED_AT, current_time( 'mysql', true ) );

		/**
		 * Fires when a Google account is attached to an existing WP user.
		 *
		 * @param int            $user_id
		 * @param GoogleIdentity $identity
		 */
		do_action( 'hw_google_account_linked', $user_id, $identity );
	}

	/**
	 * Fill in names we did not have before. Existing values win — the user may
	 * have edited their profile, and Google is not the authority on it.
	 */
	private function refreshProfile( int $user_id, GoogleIdentity $identity ): void {

		$updates = [];

		if ( $identity->first_name !== '' && get_user_meta( $user_id, 'first_name', true ) === '' ) {
			$updates['first_name'] = $identity->first_name;
		}

		if ( $identity->last_name !== '' && get_user_meta( $user_id, 'last_name', true ) === '' ) {
			$updates['last_name'] = $identity->last_name;
		}

		if ( $updates ) {
			wp_update_user( [ 'ID' => $user_id ] + $updates );
		}

		if ( $identity->picture !== '' ) {
			update_user_meta( $user_id, self::META_PICTURE, esc_url_raw( $identity->picture ) );
		}
	}

	/**
	 * A network user who signs in on a wedding site they have never visited needs
	 * a role there, otherwise they land logged in with no capabilities.
	 *
	 * The logic moved to hw_add_user_to_current_blog() so the sign-up form shares
	 * it; this stays as the name the rest of the class already calls.
	 */
	private function ensureBlogMembership( int $user_id ): void {

		hw_add_user_to_current_blog( $user_id );
	}
}
