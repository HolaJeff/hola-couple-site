<?php

namespace HW\Auth;

use HW\Email\EmailSender;
use HW\Email\Templates\VerifyEmail;

/**
 * Single source of truth for whether a WordPress user's email address has been
 * proven to belong to them.
 *
 * Verification state lives on the *user*, never on a reservation. A reservation
 * is contact information; an account is an identity. The two are deliberately
 * kept apart, and an email string submitted by an anonymous visitor is never
 * treated as proof of owning an account (see hw_claim_guest_quotes, which now
 * waits for verification before handing guest bookings over).
 *
 * Three groups are considered verified without ever touching a link:
 *
 *   1. Administrators — bypass, kept here so controllers never repeat the check.
 *   2. Legacy users — anyone who registered before this feature rolled out.
 *   3. Anyone whose email was proven by Google's verified `email_verified` claim.
 *
 * Tokens are deliberately independent of WordPress's `user_activation_key`, so
 * requesting a password reset can never invalidate a verification link and vice
 * versa.
 */
class EmailVerificationService {

	/** Timestamp (GMT, mysql) at which the user's *current* email was verified. */
	const META_VERIFIED_AT = 'hw_email_verified_at';

	/** HMAC of the outstanding verification token. The raw token is never stored. */
	const META_HASH = 'hw_email_verification_hash';

	/** Unix timestamp after which the outstanding token stops being accepted. */
	const META_EXPIRES_AT = 'hw_email_verification_expires_at';

	/**
	 * Base name of the per-site administrator override.
	 *
	 * Prefixed per blog at runtime (wp_2_hw_email_verification_override), the way
	 * core scopes wp_capabilities, because this one is deliberately NOT global.
	 *
	 * Proving you can read a mailbox is a fact about the person, so a genuine
	 * verification counts everywhere. An administrator marking someone verified
	 * is not proof — it is one site's staff vouching — so it must not grant or
	 * revoke trust on the other wedding sites in the network.
	 */
	const META_SITE_OVERRIDE = 'hw_email_verification_override';

	const OVERRIDE_GRANTED = 'granted';
	const OVERRIDE_REVOKED = 'revoked';

	/**
	 * Network-wide moment this feature went live.
	 *
	 * Everything registered before it is grandfathered. Stored once, network
	 * wide, because users are network wide — a per-site option would let a site
	 * activated later grandfather accounts that registered after rollout.
	 */
	const OPTION_ROLLOUT = 'hw_email_verification_rollout';

	/** How long a verification link stays good for. */
	const TOKEN_TTL = DAY_IN_SECONDS;

	/** Minimum gap between two verification emails for the same user. */
	const RESEND_COOLDOWN = 120;

	const TRANSIENT_COOLDOWN = 'hw_email_verify_sent_';

	/**
	 * Stamp the rollout moment the first time the plugin runs with this feature.
	 *
	 * Called during boot so the value exists before any registration can happen
	 * in the same request. add_site_option() only writes when the option is
	 * absent, so concurrent requests cannot move the line.
	 */
	public static function bootstrapRollout(): void {

		if ( false === get_site_option( self::OPTION_ROLLOUT, false ) ) {

			add_site_option( self::OPTION_ROLLOUT, gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * The rollout cut-off as a GMT mysql datetime.
	 *
	 * Falls back to "now" only if boot never ran, which would grandfather
	 * nobody rather than grandfathering everybody.
	 */
	public static function rolloutDate(): string {

		$value = get_site_option( self::OPTION_ROLLOUT, false );

		return is_string( $value ) && $value !== '' ? $value : gmdate( 'Y-m-d H:i:s' );
	}

	// ── State ─────────────────────────────────────────────────────────────────

	/**
	 * The one question every caller should ask.
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public function isVerified( int $user_id ): bool {

		if ( $user_id <= 0 ) {
			return false;
		}

		// First, and unconditionally: an administrator is never gated, so one
		// site's staff cannot lock another administrator out by revoking them.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$override = $this->getSiteOverride( $user_id );

		// An explicit revoke beats both grandfathering and a previous email
		// proof — that is the point of it, and without this the action would
		// silently do nothing for the legacy users who make up most of the list.
		if ( self::OVERRIDE_REVOKED === $override ) {
			return false;
		}

		if ( self::OVERRIDE_GRANTED === $override ) {
			return true;
		}

		if ( $this->isLegacyUser( $user_id ) ) {
			return true;
		}

		return '' !== (string) get_user_meta( $user_id, self::META_VERIFIED_AT, true );
	}

	// ── Per-site administrator override ───────────────────────────────────────

	/**
	 * Meta key holding this site's override for a user.
	 *
	 * @return string
	 */
	public static function siteOverrideKey(): string {

		global $wpdb;

		return $wpdb->get_blog_prefix() . self::META_SITE_OVERRIDE;
	}

	/**
	 * This site's override for a user: 'granted', 'revoked', or '' for none.
	 */
	public function getSiteOverride( int $user_id ): string {

		$stored = get_user_meta( $user_id, self::siteOverrideKey(), true );

		$status = is_array( $stored ) ? ( $stored['status'] ?? '' ) : '';

		return in_array( $status, [ self::OVERRIDE_GRANTED, self::OVERRIDE_REVOKED ], true ) ? $status : '';
	}

	/**
	 * Record the full override, including who set it and when.
	 *
	 * @return array|null
	 */
	public function getSiteOverrideRecord( int $user_id ): ?array {

		$stored = get_user_meta( $user_id, self::siteOverrideKey(), true );

		return is_array( $stored ) && ! empty( $stored['status'] ) ? $stored : null;
	}

	/**
	 * Set this site's override.
	 *
	 * @param int    $user_id
	 * @param string $status  self::OVERRIDE_GRANTED | self::OVERRIDE_REVOKED
	 * @param int    $by      Administrator who did it.
	 * @return void
	 */
	public function setSiteOverride( int $user_id, string $status, int $by = 0 ): void {

		if ( ! in_array( $status, [ self::OVERRIDE_GRANTED, self::OVERRIDE_REVOKED ], true ) ) {
			return;
		}

		update_user_meta( $user_id, self::siteOverrideKey(), [
			'status'  => $status,
			'at'      => current_time( 'mysql', true ),
			'by'      => $by ?: get_current_user_id(),
			'blog_id' => get_current_blog_id(),
		] );

		if ( self::OVERRIDE_GRANTED === $status ) {

			// Vouching for someone leaves no outstanding link to click.
			$this->invalidateToken( $user_id );
		}

		/**
		 * Fires when a site's administrator overrides a user's verification.
		 *
		 * @param int    $user_id
		 * @param string $status
		 */
		do_action( 'hw_email_verification_override_set', $user_id, $status );

		if ( self::OVERRIDE_GRANTED === $status ) {

			// Same downstream effect as a real verification on this site.
			do_action( 'hw_email_verified', $user_id );
		}
	}

	/**
	 * Drop this site's override, falling back to the global state.
	 */
	public function clearSiteOverride( int $user_id ): void {

		delete_user_meta( $user_id, self::siteOverrideKey() );
	}

	/**
	 * True when the account is outside the verification gate altogether.
	 *
	 * Administrators bypass by capability rather than by role name, so a custom
	 * role with the same reach is treated the same way.
	 */
	public function isExempt( int $user_id ): bool {

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		return $this->isLegacyUser( $user_id );
	}

	/**
	 * True for accounts that already existed when this feature shipped.
	 *
	 * The migration in HW\Database stamps these users explicitly; this check is
	 * the deterministic backstop so a migration that has not finished — or that
	 * failed — can never lock a historical customer out of their account.
	 */
	public function isLegacyUser( int $user_id ): bool {

		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_registered ) ) {
			return false;
		}

		// user_registered is stored in GMT, same basis as the rollout stamp.
		return strtotime( $user->user_registered . ' UTC' ) < strtotime( self::rolloutDate() . ' UTC' );
	}

	/**
	 * True when this user still has to prove their address.
	 */
	public function requiresVerification( int $user_id ): bool {

		return $user_id > 0 && ! $this->isVerified( $user_id );
	}

	/**
	 * Mark the user's current email address as proven.
	 *
	 * Always clears the outstanding token: verification is single use.
	 */
	public function markVerified( int $user_id ): void {

		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::META_VERIFIED_AT, current_time( 'mysql', true ) );

		// Proving the address again is exactly what an administrator asking for
		// re-verification wanted, so lift this site's revoke rather than leaving
		// the user stuck behind it however many times they click the link.
		if ( self::OVERRIDE_REVOKED === $this->getSiteOverride( $user_id ) ) {

			$this->clearSiteOverride( $user_id );
		}

		$this->invalidateToken( $user_id );

		/**
		 * Fires once a user's email address has been proven to be theirs.
		 *
		 * This — not registration — is the moment it becomes safe to act on the
		 * address, which is why guest bookings are handed over from here.
		 *
		 * @param int $user_id
		 */
		do_action( 'hw_email_verified', $user_id );
	}

	// ── Tokens ────────────────────────────────────────────────────────────────

	/**
	 * Issue a fresh token, invalidating any previous one.
	 *
	 * @param int $user_id
	 * @return string The raw token — only ever leaves here inside the email link.
	 */
	public function generateToken( int $user_id ): string {

		$token = bin2hex( random_bytes( 32 ) );

		update_user_meta( $user_id, self::META_HASH, $this->hashToken( $token ) );
		update_user_meta( $user_id, self::META_EXPIRES_AT, time() + self::TOKEN_TTL );

		return $token;
	}

	/**
	 * Drop the outstanding token, if any.
	 */
	public function invalidateToken( int $user_id ): void {

		delete_user_meta( $user_id, self::META_HASH );
		delete_user_meta( $user_id, self::META_EXPIRES_AT );
	}

	/**
	 * Validate a token and, on success, mark the address verified.
	 *
	 * Every failure returns the same generic error message so the endpoint
	 * cannot be used to tell a real account from a made-up one.
	 *
	 * @return true|\WP_Error
	 */
	public function verifyToken( int $user_id, string $token ): true|\WP_Error {

		$generic = new \WP_Error(
			'hw_verification_invalid',
			__( 'This verification link is no longer valid. Request a new one below.', 'hola-weddings' )
		);

		if ( $user_id <= 0 || $token === '' || ! get_userdata( $user_id ) ) {
			return $generic;
		}

		// Already done — treat as success so a double-clicked link is not an error.
		//
		// Asks isVerified() rather than reading the stamp directly, because the
		// stamp alone is not the whole answer any more. Someone who confirmed
		// their address and was later un-verified by an administrator still
		// carries it, so reading it raw short-circuited here and never reached
		// markVerified() — leaving them permanently unable to clear the revoke no
		// matter how many fresh links they clicked.
		if ( $this->isVerified( $user_id ) ) {
			return true;
		}

		$stored  = (string) get_user_meta( $user_id, self::META_HASH, true );
		$expires = (int) get_user_meta( $user_id, self::META_EXPIRES_AT, true );

		if ( $stored === '' ) {
			return $generic;
		}

		if ( $expires > 0 && time() > $expires ) {

			$this->invalidateToken( $user_id );

			return new \WP_Error(
				'hw_verification_expired',
				__( 'This verification link has expired. Request a new one below.', 'hola-weddings' )
			);
		}

		if ( ! hash_equals( $stored, $this->hashToken( $token ) ) ) {
			return $generic;
		}

		$this->markVerified( $user_id );

		return true;
	}

	/**
	 * Never store the raw token — only a keyed hash of it.
	 */
	private function hashToken( string $token ): string {

		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	// ── Delivery ──────────────────────────────────────────────────────────────

	/**
	 * The link that lands on the verification endpoint.
	 */
	public function getVerificationUrl( int $user_id, string $token ): string {

		return add_query_arg(
			[
				'uid'   => $user_id,
				'token' => $token,
			],
			home_url( '/account/verify-email/' )
		);
	}

	/**
	 * Issue a token and email it.
	 *
	 * Silently does nothing for accounts that are exempt or already verified —
	 * administrators and grandfathered users must never receive one of these.
	 *
	 * @return bool|\WP_Error
	 */
	public function sendVerificationEmail( int $user_id ): bool|\WP_Error {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'hw_verification_no_user', __( 'Unknown account.', 'hola-weddings' ) );
		}

		if ( $this->isVerified( $user_id ) ) {
			return true;
		}

		$token = $this->generateToken( $user_id );

		set_site_transient( self::TRANSIENT_COOLDOWN . $user_id, time(), self::RESEND_COOLDOWN );

		$sent = ( new EmailSender() )->send(
			new VerifyEmail( $user, $this->getVerificationUrl( $user_id, $token ) )
		);

		/**
		 * Fires after a verification email has been dispatched.
		 *
		 * @param int              $user_id
		 * @param bool|\WP_Error   $sent
		 */
		do_action( 'hw_email_verification_sent', $user_id, $sent );

		return $sent;
	}

	/**
	 * User-triggered resend, with the guard rails the endpoint should not repeat.
	 *
	 * @return true|\WP_Error
	 */
	public function resend( int $user_id ): true|\WP_Error {

		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return new \WP_Error( 'hw_verification_no_user', __( 'Unknown account.', 'hola-weddings' ) );
		}

		if ( $this->isVerified( $user_id ) ) {
			return new \WP_Error(
				'hw_verification_not_required',
				__( 'This account does not need email verification.', 'hola-weddings' )
			);
		}

		if ( false !== get_site_transient( self::TRANSIENT_COOLDOWN . $user_id ) ) {
			return new \WP_Error(
				'hw_verification_cooldown',
				__( 'A verification email was just sent. Please check your inbox before requesting another.', 'hola-weddings' )
			);
		}

		// Shared IP bucket on top of the per-user cooldown, so one machine
		// cannot cycle through accounts.
		$limiter = new RateLimiter( 'email_verification', 10, 600 );

		if ( ! $limiter->check() ) {
			return new \WP_Error(
				'hw_verification_rate_limited',
				__( 'Too many attempts. Please wait a few minutes and try again.', 'hola-weddings' )
			);
		}

		$limiter->record();

		$sent = $this->sendVerificationEmail( $user_id );

		if ( is_wp_error( $sent ) ) {
			return $sent;
		}

		return true;
	}
}
