/**
 * "Request new link" on the email verification screen.
 *
 * Progressive enhancement, not a dependency: the form is a plain POST that
 * EmailVerificationController::handle_resend() answers with a redirect, and this
 * only takes over when Alpine is actually running. With JavaScript off the page
 * behaves exactly as it did before.
 *
 * The form already carries its nonce and action as fields, so FormData is
 * precisely the body admin-ajax.php expects — nothing is rebuilt by hand and the
 * two paths cannot drift.
 */

export function registerVerifyEmail() {

	document.addEventListener( 'alpine:init', () => {

		window.Alpine.data( 'hwResendVerification', () => ( {

			busy: false,
			message: '',
			status: '',

			async send() {

				if ( this.busy ) {
					return;
				}

				this.busy = true;
				this.message = '';
				this.status = '';

				try {
					const response = await fetch( window.ajax.ajax_url, {
						method: 'POST',
						credentials: 'same-origin',
						body: new URLSearchParams( new FormData( this.$el ) ),
					} );

					const json = await response.json();

					this.status = json.success ? 'ok' : 'error';
					this.message = json.message || '';

				} catch ( e ) {

					this.status = 'error';
					this.message = window.hw_messages
						? window.hw_messages.ajax_login_generic_error
						: 'Something went wrong. Please try again.';

				} finally {

					this.busy = false;
				}
			},
		} ) );
	} );
}
