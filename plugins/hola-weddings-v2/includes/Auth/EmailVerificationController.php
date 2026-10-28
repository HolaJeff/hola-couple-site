<?php

namespace HW\Auth;

use HW\Routes;

/**
 * Wires the email verification feature into WordPress.
 *
 * Owns the verify endpoint, the resend request, and the few lifecycle hooks
 * that decide a new account's starting state. All decisions defer to
 * EmailVerificationService so the rules live in exactly one place.
 */
class EmailVerificationController {

	use \HW\Traits\Singleton;

	const RESEND_ACTION = 'hw_resend_verification';

	/**
	 * TEMPORARY (testing): treat "Skip Confirmation Email" as verifying the address.
	 *
	 * On, so accounts provisioned from wp-admin during testing are not held at
	 * the verification gate. Nobody proves anything on that branch, so set this
	 * back to false before production — or drop it and leave the checkbox
	 * unticked, which gets the invitee verified properly off one email.
	 *
	 * Overridable per site at runtime via the hw_trust_skipped_confirmation filter.
	 */
	const TRUST_SKIPPED_CONFIRMATION = true;

	private EmailVerificationService $service;

	public function __construct() {

		$this->service = new EmailVerificationService();

		// Stamp the rollout line before anything can register under the new rules.
		EmailVerificationService::bootstrapRollout();

		// New accounts: decide the starting state. Deliberately does not send the
		// email — only an explicit registration flow knows it should do that.
		add_action( 'user_register', [ $this, 'on_user_register' ], 5, 1 );

		// A Google identity carries a verified email claim, so those accounts are
		// verified on arrival and must never be mailed a link.
		add_action( 'hw_google_user_registered', [ $this, 'on_google_user' ], 10, 1 );
		add_action( 'hw_google_user_logged_in', [ $this, 'on_google_user' ], 10, 1 );

		// Multisite's own signup flow already proves the address when the invited
		// person clicks the activation link.
		add_action( 'wpmu_activate_user', [ $this, 'on_multisite_activation' ], 10, 1 );

		// Late, so anything that suppressed the notification earlier still wins.
		add_filter( 'wpmu_signup_user_notification', [ $this, 'send_activation_email' ], 99, 4 );

		// Verification is about the *current* address, so a change resets it.
		add_action( 'profile_update', [ $this, 'on_profile_update' ], 10, 2 );

		// Endpoint + resend handling.
		add_action( 'template_redirect', [ $this, 'handle_verification' ], 9 );
		add_action( 'init', [ $this, 'handle_resend' ] );

		// Same action name as the form, answered as JSON. Alpine posts here when
		// it is running; handle_resend() bows out during an AJAX request so the
		// two never both fire.
		add_action( 'wp_ajax_' . self::RESEND_ACTION, [ $this, 'handle_resend_ajax' ] );

		// Only signed-in users have anything to resend, so the page needs a session.
		add_action( 'template_redirect', [ $this, 'guard_verify_page' ], 10 );
	}

	public function getService(): EmailVerificationService {

		return $this->service;
	}

	// ── Lifecycle ─────────────────────────────────────────────────────────────

	/**
	 * Give every new account its starting verification state.
	 *
	 * Administrators are stamped immediately so they never see the gate and
	 * never get an email. Everyone else starts unverified; whichever flow
	 * created them is responsible for sending the link, which keeps accounts
	 * provisioned by Google (verified already) from being mailed one.
	 *
	 * @param int $user_id
	 */
	public function on_user_register( $user_id ): void {

		$user_id = (int) $user_id;

		if ( user_can( $user_id, 'manage_options' ) ) {

			update_user_meta( $user_id, EmailVerificationService::META_VERIFIED_AT, current_time( 'mysql', true ) );
		}
	}

	/**
	 * Google proved the address with a verified `email_verified` claim.
	 *
	 * @param int $user_id
	 */
	public function on_google_user( $user_id ): void {

		$user_id = (int) $user_id;

		if ( ! $this->service->isVerified( $user_id ) ) {

			$this->service->markVerified( $user_id );
		}
	}

	/**
	 * Send the network invitation through the plugin's mailer instead of core's.
	 *
	 * wpmu_signup_user_notification() hardcodes `Content-Type: text/plain` into
	 * the headers it passes to wp_mail(), so filtering the body alone would just
	 * deliver HTML source as text. The only way to restyle it is to take the
	 * message over: send our own and return false to stop core sending its own.
	 *
	 * Runs at priority 99 so an earlier suppression still decides. wp-admin adds
	 * `__return_false` here at priority 10 when "Skip Confirmation Email" is
	 * ticked, and honouring that is what keeps this from mailing someone the
	 * administrator deliberately chose not to contact.
	 *
	 * @param string $user_login Falsy once something upstream has suppressed the mail.
	 * @param string $user_email
	 * @param string $key        Activation key.
	 * @param array  $meta       Signup meta: add_to_blog, new_role.
	 * @return string|false The login to let core proceed, false to stop it.
	 */
	public function send_activation_email( $user_login, $user_email = '', $key = '', $meta = [] ) {

		// Already suppressed upstream — leave it suppressed and send nothing.
		if ( ! $user_login ) {
			return $user_login;
		}

		$meta = is_array( $meta ) ? $meta : [];

		$blog_id    = isset( $meta['add_to_blog'] ) ? (int) $meta['add_to_blog'] : get_current_blog_id();
		$site_title = get_blog_option( $blog_id, 'blogname' );

		if ( ! $site_title ) {
			$site_title = wp_parse_url( network_home_url(), PHP_URL_HOST );
		}

		$role  = '';
		$roles = function_exists( 'get_editable_roles' ) ? get_editable_roles() : [];

		if ( ! empty( $meta['new_role'] ) && isset( $roles[ $meta['new_role'] ]['name'] ) ) {
			$role = translate_user_role( $roles[ $meta['new_role'] ]['name'] );
		}

		// Same URL core builds, so the key is redeemed by the standard endpoint.
		$activation_url = site_url( "wp-activate.php?key=$key" );

		$sent = ( new \HW\Email\EmailSender() )->send(
			new \HW\Email\Templates\UserActivation(
				(string) $user_login,
				(string) $user_email,
				$activation_url,
				wp_specialchars_decode( (string) $site_title, ENT_QUOTES ),
				$role
			)
		);

		// If our mailer failed, fall through to core's plain-text version rather
		// than leaving the invitee with nothing at all.
		if ( is_wp_error( $sent ) || false === $sent ) {
			return $user_login;
		}

		return false;
	}

	/**
	 * Accept multisite's activation link as proof of the address.
	 *
	 * Network "Add New User" leaves the account in wp_signups and emails an
	 * activation key; no WP user exists until someone opens that mailbox and
	 * clicks. That is the same thing our own link proves, so honouring it saves
	 * the invited customer a second, redundant confirmation email.
	 *
	 * "Skip Confirmation Email" proves nothing: that box makes wp-admin suppress
	 * the notification and call wpmu_activate_signup() inline, so this hook fires
	 * having sent nothing and having heard nothing back. It is detected the same
	 * way core decides it, by the request that is running, and is currently
	 * trusted anyway while testing — see self::TRUST_SKIPPED_CONFIRMATION.
	 *
	 * @param int $user_id
	 */
	public function on_multisite_activation( $user_id ): void {

		$user_id = (int) $user_id;

		$skipped = isset( $_POST['noconfirmation'] ) && current_user_can( 'manage_network_users' );

		if ( $skipped ) {

			/**
			 * Whether an administrator ticking "Skip Confirmation Email" should
			 * count as verifying the address on the new account's behalf.
			 *
			 * Nobody proved anything on that branch and the address may simply
			 * have been mistyped, so this is only meant to be on while staff are
			 * provisioning test accounts. See self::TRUST_SKIPPED_CONFIRMATION.
			 *
			 * @param bool $verified
			 * @param int  $user_id
			 */
			if ( ! apply_filters( 'hw_trust_skipped_confirmation', self::TRUST_SKIPPED_CONFIRMATION, $user_id ) ) {
				return;
			}
		}

		if ( ! $this->service->isVerified( $user_id ) ) {

			$this->service->markVerified( $user_id );
		}
	}

	/**
	 * Reset verification whenever the account's email address changes.
	 *
	 * WordPress only confirms an address change when the user makes it on their
	 * own profile page; an administrator editing someone else's account changes
	 * it outright. Rather than guess which path ran, every change resets and a
	 * fresh link goes to the new address. That is occasionally one redundant
	 * email, but it keeps the invariant absolute: hw_email_verified_at always
	 * refers to the address currently on the account.
	 *
	 * A grandfathered user who changes their address therefore joins the normal
	 * flow for the new one, which is the intended behaviour.
	 *
	 * @param int      $user_id
	 * @param \WP_User $old_user_data
	 */
	public function on_profile_update( $user_id, $old_user_data ): void {

		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user || ! $old_user_data instanceof \WP_User ) {
			return;
		}

		if ( strtolower( $user->user_email ) === strtolower( $old_user_data->user_email ) ) {
			return;
		}

		// Administrators bypass the gate before and after a change.
		if ( user_can( $user_id, 'manage_options' ) ) {

			update_user_meta( $user_id, EmailVerificationService::META_VERIFIED_AT, current_time( 'mysql', true ) );
			$this->service->invalidateToken( $user_id );

			return;
		}

		delete_user_meta( $user_id, EmailVerificationService::META_VERIFIED_AT );
		$this->service->invalidateToken( $user_id );

		$this->service->sendVerificationEmail( $user_id );
	}

	// ── Endpoint ──────────────────────────────────────────────────────────────

	/**
	 * Consume a verification link.
	 *
	 * Runs before the account area's login guard so the link works whether or
	 * not the visitor happens to be signed in — clicking it from a webmail
	 * client on another device is the normal case.
	 */
	public function handle_verification(): void {

		if ( Routes::get_view() !== 'verify-email' ) {
			return;
		}

		$uid   = isset( $_GET['uid'] ) ? absint( $_GET['uid'] ) : 0;
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( $uid === 0 || $token === '' ) {
			return; // Nothing to consume — the view renders the restricted-state screen.
		}

		$result = $this->service->verifyToken( $uid, $token );

		// Only failures leave a notice. On success the card the visitor lands on
		// already says the address is confirmed, so a banner above it saying the
		// same thing is just noise — whereas an invalid or expired link needs the
		// explanation, because the card falls back to its unconfirmed state and
		// would otherwise look like nothing happened.
		if ( is_wp_error( $result ) ) {

			hw_add_notice( $result->get_error_message(), 'error' );
		}

		// Drop the token from the address bar so it is not left in history or
		// leaked through a Referer header.
		wp_safe_redirect( home_url( '/account/verify-email/' ) );
		exit;
	}

	/**
	 * Handle the "send it again" button on the restricted-state screen.
	 */
	public function handle_resend(): void {

		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== self::RESEND_ACTION ) {
			return;
		}

		// admin-ajax.php fires init too. Leave that request to handle_resend_ajax(),
		// or this would redirect in the middle of the JSON response.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], self::RESEND_ACTION . '_' . $user_id ) ) {

			hw_add_notice( __( 'Your session expired. Please refresh the page and try again.', 'hola-weddings' ), 'error' );

			wp_safe_redirect( home_url( '/account/verify-email/' ) );
			exit;
		}

		$result = $this->service->resend( $user_id );

		if ( is_wp_error( $result ) ) {

			hw_add_notice( $result->get_error_message(), 'error' );
		} else {

			hw_add_notice( __( 'Verification email sent. Please check your inbox.', 'hola-weddings' ), 'success' );
		}

		wp_safe_redirect( home_url( '/account/verify-email/' ) );
		exit;
	}

	/**
	 * Resend, answered as JSON for the Alpine-enhanced form.
	 *
	 * Deliberately thin: every rule lives in the service, so the no-JS POST and
	 * this path can never drift on who is allowed to ask or how often.
	 *
	 * @return void
	 */
	public function handle_resend_ajax(): void {

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json( [
				'success' => false,
				'message' => __( 'Please sign in again to request a new link.', 'hola-weddings' ),
			], 403 );
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], self::RESEND_ACTION . '_' . $user_id ) ) {
			wp_send_json( [
				'success' => false,
				'message' => __( 'Your session expired. Please refresh the page and try again.', 'hola-weddings' ),
			], 403 );
		}

		$result = $this->service->resend( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json( [
				'success' => false,
				'message' => $result->get_error_message(),
			] );
		}

		wp_send_json( [
			'success' => true,
			'message' => __( 'Verification email sent. Please check your inbox.', 'hola-weddings' ),
		] );
	}

	/**
	 * The verification screen is for signed-in users.
	 *
	 * Runs at priority 10, after handle_verification() at 9, so a link clicked
	 * from a webmail client on another device still gets consumed and the address
	 * still gets marked verified before anyone is sent to log in.
	 *
	 * @return void
	 */
	public function guard_verify_page(): void {

		if ( Routes::get_template() !== 'verify-email' ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return;
		}

		wp_safe_redirect( wp_login_url( hw_get_verify_email_url() ) );
		exit;
	}
}
