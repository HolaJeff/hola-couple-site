<?php

namespace HW\Admin;

use HW\Auth\EmailVerificationService;

/**
 * Email verification controls on the Users screen.
 *
 * Lets a site administrator vouch for someone without them clicking a link, or
 * withdraw that trust and ask them to prove the address again — handy when a
 * customer cannot receive the email, and it saves creating a fresh account every
 * time the flow needs testing.
 *
 * Everything here writes the PER-SITE override, never the global verified stamp.
 * Reading a mailbox is a fact about the person and counts network wide; an
 * administrator vouching is one site's opinion and stops at that site's edge.
 * So marking someone verified on this wedding site leaves them exactly as they
 * were on every other one.
 */
class UserVerification {

	use \HW\Traits\Singleton;

	const ACTION_VERIFY   = 'hw_verify_user';
	const ACTION_UNVERIFY = 'hw_unverify_user';
	const ACTION_RESEND   = 'hw_resend_user_verification';

	/** Site administrators. Deliberately not edit_users, which is super-admin only on multisite. */
	const CAPABILITY = 'manage_options';

	private EmailVerificationService $service;

	public function __construct() {

		$this->service = new EmailVerificationService();

		add_filter( 'manage_users_columns', [ $this, 'add_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'render_column' ], 10, 3 );
		add_filter( 'user_row_actions', [ $this, 'add_row_actions' ], 10, 2 );

		add_action( 'load-users.php', [ $this, 'handle_action' ] );
		add_action( 'admin_notices', [ $this, 'render_notice' ] );
	}

	// ── Column ────────────────────────────────────────────────────────────────

	public function add_column( $columns ) {

		$columns['hw_email_verified'] = __( 'Email Verified', 'hola-weddings' );

		return $columns;
	}

	public function render_column( $output, $column, $user_id ) {

		if ( 'hw_email_verified' !== $column ) {
			return $output;
		}

		$user_id = (int) $user_id;

		if ( user_can( $user_id, self::CAPABILITY ) ) {
			return '<span title="' . esc_attr__( 'Administrators are never gated.', 'hola-weddings' ) . '">&mdash;</span>';
		}

		$override = $this->service->getSiteOverrideRecord( $user_id );
		$verified = $this->service->isVerified( $user_id );

		$label = $verified
			? '<span style="color:#0f5132;">&#10003; ' . esc_html__( 'Verified', 'hola-weddings' ) . '</span>'
			: '<span style="color:#842029;">&#10007; ' . esc_html__( 'Not verified', 'hola-weddings' ) . '</span>';

		// Say where the answer came from, so "verified" that is really an
		// administrator's word is not mistaken for the user having proved it.
		if ( $override ) {

			$by   = get_userdata( (int) ( $override['by'] ?? 0 ) );
			$note = $override['status'] === EmailVerificationService::OVERRIDE_GRANTED
				? __( 'set by %s on this site', 'hola-weddings' )
				: __( 'revoked by %s on this site', 'hola-weddings' );

			$label .= '<br><small>' . sprintf( esc_html( $note ), esc_html( $by ? $by->display_name : __( 'an administrator', 'hola-weddings' ) ) ) . '</small>';

		} elseif ( $verified && $this->service->isLegacyUser( $user_id ) ) {

			$label .= '<br><small>' . esc_html__( 'predates verification', 'hola-weddings' ) . '</small>';

		} elseif ( $verified ) {

			$label .= '<br><small>' . esc_html__( 'confirmed by email', 'hola-weddings' ) . '</small>';
		}

		return $label;
	}

	// ── Row actions ───────────────────────────────────────────────────────────

	public function add_row_actions( $actions, $user ) {

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $actions;
		}

		// Nothing to toggle: they bypass the gate whatever this says.
		if ( user_can( $user->ID, self::CAPABILITY ) ) {
			return $actions;
		}

		if ( $this->service->isVerified( $user->ID ) ) {

			$actions['hw_unverify'] = $this->link(
				self::ACTION_UNVERIFY,
				$user->ID,
				__( 'Mark as unverified', 'hola-weddings' ),
				__( 'Remove verification on this site and ask them to confirm their email again.', 'hola-weddings' )
			);

		} else {

			$actions['hw_verify'] = $this->link(
				self::ACTION_VERIFY,
				$user->ID,
				__( 'Mark as verified', 'hola-weddings' ),
				__( 'Vouch for this address on this site, without them clicking a link.', 'hola-weddings' )
			);

			$actions['hw_resend'] = $this->link(
				self::ACTION_RESEND,
				$user->ID,
				__( 'Request new email verification', 'hola-weddings' ),
				__( 'Send them a fresh verification link.', 'hola-weddings' )
			);
		}

		return $actions;
	}

	private function link( string $action, int $user_id, string $label, string $title ): string {

		$url = wp_nonce_url(
			add_query_arg( [ 'hw_action' => $action, 'user' => $user_id ], admin_url( 'users.php' ) ),
			$action . '_' . $user_id
		);

		return sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr( $title ),
			esc_html( $label )
		);
	}

	// ── Handling ──────────────────────────────────────────────────────────────

	public function handle_action(): void {

		$action = isset( $_GET['hw_action'] ) ? sanitize_key( wp_unslash( $_GET['hw_action'] ) ) : '';

		if ( ! in_array( $action, [ self::ACTION_VERIFY, self::ACTION_UNVERIFY, self::ACTION_RESEND ], true ) ) {
			return;
		}

		$user_id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;

		if ( ! $user_id || ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Not authorized.', 'hola-weddings' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( $action . '_' . $user_id );

		// The Users screen only lists this site's members, but the id arrives in
		// the URL — so check rather than trust it.
		if ( is_multisite() && ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
			wp_die( esc_html__( 'That user is not a member of this site.', 'hola-weddings' ), '', [ 'response' => 403 ] );
		}

		$result = 'done';

		switch ( $action ) {

			case self::ACTION_VERIFY:
				$this->service->setSiteOverride( $user_id, EmailVerificationService::OVERRIDE_GRANTED );
				$result = 'verified';
				break;

			case self::ACTION_UNVERIFY:
				$this->service->setSiteOverride( $user_id, EmailVerificationService::OVERRIDE_REVOKED );
				$result = 'unverified';
				break;

			case self::ACTION_RESEND:
				$sent   = $this->service->sendVerificationEmail( $user_id );
				$result = is_wp_error( $sent ) || false === $sent ? 'resend_failed' : 'resent';
				break;
		}

		wp_safe_redirect( add_query_arg( 'hw_result', $result, admin_url( 'users.php' ) ) );
		exit;
	}

	public function render_notice(): void {

		$screen = get_current_screen();

		if ( ! $screen || 'users' !== $screen->id ) {
			return;
		}

		$result = isset( $_GET['hw_result'] ) ? sanitize_key( wp_unslash( $_GET['hw_result'] ) ) : '';

		$messages = [
			'verified'      => [ 'success', __( 'Marked as verified on this site. Their status on other sites is unchanged.', 'hola-weddings' ) ],
			'unverified'    => [ 'warning', __( 'Verification removed on this site. Use “Request new email verification” to send them a fresh link.', 'hola-weddings' ) ],
			'resent'        => [ 'success', __( 'Verification email sent.', 'hola-weddings' ) ],
			'resend_failed' => [ 'error',   __( 'Could not send the verification email. They may already be verified, or one was sent moments ago.', 'hola-weddings' ) ],
		];

		if ( ! isset( $messages[ $result ] ) ) {
			return;
		}

		[ $type, $message ] = $messages[ $result ];

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}
