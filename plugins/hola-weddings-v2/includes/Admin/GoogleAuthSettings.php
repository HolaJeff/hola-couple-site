<?php

namespace HW\Admin;

use HW\Traits\Singleton;

/**
 * Network-level Google OAuth credentials.
 *
 * The client ID / secret live network-wide so one Google Cloud client (and one
 * registered redirect URI) covers every wedding site. Whether the button is
 * actually shown is a per-site switch in Wedding Settings → Login/Sign Up.
 *
 * The secret is write-only in the UI and never leaves the server.
 */
class GoogleAuthSettings {

	use Singleton;

	public function __construct() {

		add_filter( 'hw_network_settings_tabs',    [ $this, 'add_network_tab' ] );
		add_action( 'hw_on_save_network_settings', [ $this, 'save_client_secret' ] );
	}

	public function add_network_tab( array $tabs ): array {

		$tabs['google'] = [
			'label'    => 'Google',
			'icon'     => '<i class="fab fa-google"></i>',
			'callback' => [ $this, 'render_network_tab' ],
		];

		return $tabs;
	}

	/** @param string $_tab Tab key (unused — required by the callback signature). */
	public function render_network_tab( string $_tab, $form = null ): void {

		hw_get_template( 'templates/admin/network-settings/google-tab', [ 'form' => $form ] );
	}

	/**
	 * Save the client secret after the generic data[] loop.
	 *
	 * Kept out of data[] so the field can render blank: a blank submission means
	 * "keep what is stored" rather than "erase it".
	 */
	public function save_client_secret(): void {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		$submitted = sanitize_text_field( stripslashes( $_POST['hw_google_client_secret_input'] ?? '' ) );

		if ( $submitted !== '' ) {
			update_network_option( null, 'hw_google_client_secret', $submitted );
		}
	}
}
