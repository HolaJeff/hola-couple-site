/**
 * Auth modal controller.
 *
 * The one authentication modal on the site — sign in, sign up and reset
 * password. Its markup is rendered by PHP into every page's footer
 * (template-parts/modals/login.php), and this class is the only supported way
 * to drive it, so anything on the page including the React booking form can
 * open it without knowing how it is built.
 *
 * Two things make the timing safe:
 *
 *   - this bundle is enqueued in <head> while the markup lands in the footer,
 *     so the element is resolved lazily rather than captured in the constructor;
 *   - open() called before the DOM is ready is queued and replayed on boot,
 *     which removes the race where an early click silently does nothing.
 *
 * Consumers react through native CustomEvents on `document`, listed in
 * AUTH_EVENTS. They are dispatched synchronously, so a listener can still
 * persist state before the page reloads or navigates away.
 *
 * The DOM ids below are deliberately still the "login" ones. They are
 * referenced by the theme (template-parts/offcanvas.php,
 * template-parts/header/content/user-login.php) and are baked into the
 * translated strings in languages/*.po, so renaming them would invalidate
 * existing translations for no user-visible gain.
 */

const MODAL_ID = 'loginModal';

/** Pane key → the collapse element that holds it. */
const PANES = {
	signin: 'ajax-login',
	signup: 'ajax-signup',
	reset:  'ajax-reset-password',
	verify: 'hw-auth-verify',
};

/** Accepted spellings that are not pane keys. */
const ALIASES = {
	login: 'signin',
};

export const AUTH_EVENTS = {
	/** The modal became visible. detail: { mode } */
	opened: 'hw:login:opened',
	/** The modal was dismissed. detail: {} */
	closed: 'hw:login:closed',
	/** Credentials accepted, fired BEFORE the page reloads. detail: {} */
	success: 'hw:login:success',
	/** A sign-in, sign-up or reset attempt failed. detail: { message, action } */
	error: 'hw:login:error',
	/** About to leave the page for an external provider. detail: { url, provider } */
	redirect: 'hw:login:redirect',
	/**
	 * An account was just created. detail: { userId, redirect }
	 *
	 * Cancelable: preventDefault() keeps the visitor where they are instead of
	 * sending them to the verification screen. The booking form uses this so a
	 * signup at the review step does not abandon the booking.
	 */
	registered: 'hw:auth:registered',
};

export default class HwAuthModal {

	constructor() {
		this._ready = false;
		this._queued = null;
		this._mode = 'signin';
		this._authenticated = false;
		this._verification = { email: '', nonce: '' };

		// Alpine boots after this bundle (deferred vs. in <head>), so registering
		// the store here is early enough. It seeds from whatever mode has been
		// asked for in the meantime, so an open({mode}) that landed first is not
		// overwritten back to sign-in.
		document.addEventListener( 'alpine:init', () => {

			window.Alpine.store( 'hwAuth', {
				mode: this._mode,

				// Filled in from the sign-in / sign-up response: this markup is
				// rendered while logged out, so neither is known until then.
				verification: this._verification,

				/** Bound from the template: $store.hwAuth.set('signup'). */
				set( next ) {
					this.mode = next;
					// Keep the controller authoritative so mode() agrees.
					if ( window.hw && window.hw.authModal ) {
						window.hw.authModal._mode = next;
					}
				},
			} );
		} );

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', () => this._boot() );
		} else {
			this._boot();
		}
	}

	// ── Lifecycle ─────────────────────────────────────────────────────────────

	_boot() {
		this._ready = true;

		const el = this.element();

		// Bootstrap owns visibility; mirror its events onto the public contract
		// so consumers never have to know that.
		if ( el && window.jQuery ) {
			window.jQuery( el )
				.on( 'shown.bs.modal', () => this.emit( AUTH_EVENTS.opened, { mode: this.mode() } ) )
				.on( 'hidden.bs.modal', () => {

					this.emit( AUTH_EVENTS.closed );

					// Someone signed in or signed up and then dismissed the modal.
					// Nothing on the page knows about the new session yet — the
					// header still offers "Log In", the booking form still shows
					// its create-account fields — so pick it up now. Deferred to
					// the next tick so listeners see the closed event first.
					if ( this._authenticated ) {

						this._authenticated = false;
						setTimeout( () => window.location.reload(), 0 );
					}
				} );
		}

		// Signing in with Google leaves the page, so announce it first. The
		// listener runs synchronously during the click, before navigation, which
		// is what lets the booking form persist an in-progress booking. Delegated
		// from the modal root so it covers the button in every pane.
		if ( el ) {
			el.addEventListener( 'click', ( event ) => {

				const link = event.target.closest( '.hw-google-btn' );

				if ( ! link ) {
					return;
				}

				const proceed = this.emit( AUTH_EVENTS.redirect, {
					url: link.getAttribute( 'href' ),
					provider: 'google',
				} );

				// The event is cancelable, so a listener can veto the redirect.
				if ( ! proceed ) {
					event.preventDefault();
				}
			} );
		}

		if ( this._queued ) {
			const queued = this._queued;
			this._queued = null;
			this.open( queued );
		}
	}

	/** The modal root, or null on a page that did not render it. */
	element() {
		return document.getElementById( MODAL_ID );
	}

	/** Normalise a caller-supplied mode to a pane key. */
	_resolveMode( mode ) {
		const key = ALIASES[ mode ] || mode;

		return PANES[ key ] ? key : 'signin';
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Show the modal.
	 *
	 * @param {Object} [options]
	 * @param {'signin'|'signup'|'reset'|'login'} [options.mode] Which pane to land on.
	 * @returns {HwAuthModal}
	 */
	open( options = {} ) {

		if ( ! this._ready ) {
			this._queued = options;
			return this;
		}

		const el = this.element();

		if ( ! el || ! window.jQuery ) {
			return this;
		}

		this.setMode( options.mode );
		window.jQuery( el ).modal( 'show' );

		return this;
	}

	/** Hide the modal. */
	close() {

		const el = this.element();

		if ( el && window.jQuery ) {
			window.jQuery( el ).modal( 'hide' );
		}

		return this;
	}

	/** Whether the modal is currently visible. */
	isOpen() {

		const el = this.element();

		return !! el && el.classList.contains( 'show' );
	}

	/** Which pane is showing: 'signin', 'signup' or 'reset'. */
	mode() {

		return this._mode;
	}

	/**
	 * Switch panes without opening or closing the modal.
	 *
	 * The class holds the mode rather than reading it back off the DOM, because
	 * Alpine may not have booted yet — this bundle runs in <head> and Alpine is
	 * deferred. The store, once it exists, is kept in step; until then the value
	 * waits here and the store picks it up on alpine:init.
	 *
	 * Asking for a pane that is not rendered — signup while sign-up is switched
	 * off — falls back to sign-in rather than leaving the modal blank.
	 */
	setMode( mode ) {

		let target = this._resolveMode( mode );

		if ( ! document.getElementById( PANES[ target ] ) ) {
			target = 'signin';
		}

		this._mode = target;

		const store = window.Alpine && window.Alpine.store( 'hwAuth' );

		if ( store ) {
			store.mode = target;
		}

		return this;
	}

	/**
	 * Swap to the "confirm your email" pane after a successful sign-in or sign-up.
	 *
	 * Keeps the visitor in the modal instead of sending them to
	 * /account/verify-email/, which still exists and still handles the emailed
	 * link, direct visits and anyone without JavaScript.
	 *
	 * @param {Object} verification From the auth response: { email, nonce }.
	 * @returns {boolean} False when the pane is not on the page, so the caller
	 *                    can fall back to its redirect.
	 */
	showVerification( verification = {} ) {

		if ( ! document.getElementById( PANES.verify ) ) {
			return false;
		}

		// Mutate rather than replace: Alpine's reactivity is bound to this object.
		this._verification.email = verification.email || '';
		this._verification.nonce = verification.nonce || '';

		const store = window.Alpine && window.Alpine.store( 'hwAuth' );

		if ( store ) {
			store.verification.email = this._verification.email;
			store.verification.nonce = this._verification.nonce;
		}

		this._authenticated = true;
		this.setMode( 'verify' );
		this.open( { mode: 'verify' } );

		return true;
	}

	/**
	 * Note that the visitor is now signed in, so closing the modal reloads.
	 */
	markAuthenticated() {

		this._authenticated = true;

		return this;
	}

	// ── Events ────────────────────────────────────────────────────────────────

	/**
	 * Dispatch one of AUTH_EVENTS on `document`.
	 *
	 * Synchronous on purpose: `success` fires immediately before the reload, so
	 * a listener gets to persist whatever it needs first.
	 *
	 * @returns {boolean} False when a cancelable event was prevented.
	 */
	emit( name, detail = {} ) {

		return document.dispatchEvent( new CustomEvent( name, {
			detail,
			bubbles: true,
			cancelable: true,
		} ) );
	}

	/**
	 * Subscribe to one of AUTH_EVENTS.
	 *
	 * @returns {Function} Unsubscribe.
	 */
	on( name, handler ) {

		document.addEventListener( name, handler );

		return () => document.removeEventListener( name, handler );
	}

	off( name, handler ) {

		document.removeEventListener( name, handler );

		return this;
	}
}
