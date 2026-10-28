<?php

namespace HW\Admin;

use HW\Traits\Singleton;
use HW\Feedback\JiraOAuth;
use HW\Feedback\JiraTokenStore;

/**
 * Jira OAuth admin integration.
 *
 * Adds a "Jira" tab to the network settings page and handles the full
 * OAuth 2.0 (3LO) connect / callback / disconnect flow.
 *
 * The React frontend never receives Jira tokens — all API calls happen
 * server-side inside JiraClient.
 */
class FeedbackSettings {

	use Singleton;

	public function __construct() {
		add_filter( 'hw_network_settings_tabs',    [ $this, 'add_network_tab' ] );
		add_action( 'hw_on_save_network_settings', [ $this, 'save_client_secret' ] );
		add_action( 'hw_on_save_network_settings', [ $this, 'save_allowed_issue_types' ] );

		add_action( 'admin_post_hw_jira_connect',        [ $this, 'handle_connect' ] );
		add_action( 'admin_post_hw_jira_oauth_callback', [ $this, 'handle_oauth_callback' ] );
		add_action( 'admin_post_hw_jira_disconnect',     [ $this, 'handle_disconnect' ] );

		add_action( 'wp_ajax_hw_jira_get_issue_types', [ $this, 'ajax_get_issue_types' ] );
	}

	// ── Network settings tab ──────────────────────────────────────────────────

	public function add_network_tab( array $tabs ): array {
		$tabs['jira'] = [
			'label'    => 'Jira',
			'icon'     => '<i class="fas fa-bug"></i>',
			'callback' => [ $this, 'render_network_tab' ],
		];
		return $tabs;
	}

	/** @param string $_tab  Tab key (unused — required by the callback signature). */
	public function render_network_tab( string $_tab, $form = null ): void {
		hw_get_template( 'templates/admin/network-settings/jira-tab', [ 'form' => $form ] );
	}

	// ── Credential save (client_secret masking) ───────────────────────────────

	/**
	 * Save the OAuth client secret after the generic data[] loop.
	 * Only writes when a non-empty value was submitted (blank → keep existing).
	 */
	public function save_client_secret(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
		$submitted = sanitize_text_field( stripslashes( $_POST['hw_jira_client_secret_input'] ?? '' ) );
		if ( $submitted !== '' ) {
			update_network_option( null, 'hw_jira_client_secret', $submitted );
		}
	}

	/**
	 * Save the allowed issue types multi-select.
	 * Always writes (including empty array) so deselecting all clears the option.
	 * Uses a dedicated POST key outside data[] to avoid the missing-key problem
	 * with multi-selects when nothing is selected.
	 */
	public function save_allowed_issue_types(): void {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) return;
		$raw  = isset( $_POST['hw_jira_allowed_issue_types'] ) ? (array) $_POST['hw_jira_allowed_issue_types'] : [];
		$safe = array_map( 'sanitize_text_field', wp_unslash( $raw ) );
		update_network_option( null, 'hw_jira_allowed_issue_types', $safe );
	}

	// ── OAuth action handlers ─────────────────────────────────────────────────

	/** Step 1 — admin clicks "Connect to Jira"; redirect to Atlassian. */
	public function handle_connect(): void {
		check_admin_referer( 'hw_jira_connect' );
		if ( ! current_user_can( 'manage_network_options' ) ) wp_die( 'Unauthorized', 403 );

		$oauth = new JiraOAuth();

		if ( ! $oauth->is_configured() ) {
			wp_safe_redirect( $this->settings_url( [ 'jira_error' => 'app_not_configured' ] ) );
			exit;
		}

		wp_redirect( $oauth->get_authorization_url() );
		exit;
	}

	/** Step 2 — Atlassian redirects back with ?code= and ?state=. */
	public function handle_oauth_callback(): void {
		$code  = sanitize_text_field( wp_unslash( $_GET['code']  ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );

		if ( $error !== '' ) {
			wp_safe_redirect( $this->settings_url( [ 'jira_error' => urlencode( $error ) ] ) );
			exit;
		}

		if ( ! current_user_can( 'manage_network_options' ) ) wp_die( 'Unauthorized', 403 );

		$result = ( new JiraOAuth() )->handle_callback( $code, $state );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $this->settings_url( [ 'jira_error' => urlencode( $result->get_error_message() ) ] ) );
			exit;
		}

		wp_safe_redirect( $this->settings_url( [ 'jira_connected' => '1' ] ) );
		exit;
	}

	/** Disconnect — clears all stored tokens. */
	public function handle_disconnect(): void {
		check_admin_referer( 'hw_jira_disconnect' );
		if ( ! current_user_can( 'manage_network_options' ) ) wp_die( 'Unauthorized', 403 );

		JiraTokenStore::clear();

		wp_safe_redirect( $this->settings_url( [ 'jira_disconnected' => '1' ] ) );
		exit;
	}

	// ── AJAX ─────────────────────────────────────────────────────────────────

	/** Return issue types for a project key as a JSON object `{ "Bug": "Bug", … }`. */
	public function ajax_get_issue_types(): void {
		check_ajax_referer( 'hw_jira_ajax' );
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$project_key = sanitize_text_field( wp_unslash( $_GET['project_key'] ?? '' ) );
		$types       = ( new \HW\Feedback\JiraClient() )->get_issue_types( $project_key );

		if ( is_wp_error( $types ) ) {
			wp_send_json_error( $types->get_error_message() );
			return;
		}

		wp_send_json_success( $types );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function settings_url( array $extra = [] ): string {
		return add_query_arg(
			array_merge( [ 'page' => 'hw-network-settings', 'tab' => 'jira' ], $extra ),
			network_admin_url( 'admin.php' )
		);
	}
}
