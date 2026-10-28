import { useEffect } from '@wordpress/element';
import { useBooking, saveBookingState } from '../store/bookingContext';

/**
 * Bridge to the plugin's login modal.
 *
 * The modal itself is server-rendered into every page footer and driven by
 * window.hw.loginModal (assets/src/js/app/login-modal.js) — there is no React
 * implementation any more, so the two cannot drift apart.
 *
 * Signing in reloads the page, which would throw away an in-progress booking.
 * The plugin fires `hw:login:success` synchronously just before that reload, so
 * persisting here is enough to survive it. Same story for a Google sign-in,
 * which leaves the page entirely.
 *
 * @returns {{ open: Function, close: Function }}
 */
export function useLoginModal() {

	const ctx = useBooking();

	useEffect( () => {

		const events = window.hw?.LOGIN_EVENTS;

		if ( ! events ) {
			return;
		}

		const persist = () => saveBookingState( ctx );

		// Signing up would otherwise send the visitor to /account/verify-email/,
		// abandoning the booking they are in the middle of. Cancelling the event
		// makes the plugin reload this page instead, which picks up the new
		// session and restores the state persisted just above. This hook only
		// mounts inside the booking form, so it is always the right call here.
		const stayPut = ( event ) => {
			saveBookingState( ctx );
			event.preventDefault();
		};

		document.addEventListener( events.success, persist );
		document.addEventListener( events.redirect, persist );
		document.addEventListener( events.registered, stayPut );

		return () => {
			document.removeEventListener( events.success, persist );
			document.removeEventListener( events.redirect, persist );
			document.removeEventListener( events.registered, stayPut );
		};
	}, [ ctx ] );

	return {
		// Safe to call before the footer markup exists — open() queues itself.
		open:  ( options ) => window.hw?.loginModal?.open( options ),
		close: () => window.hw?.loginModal?.close(),
	};
}
