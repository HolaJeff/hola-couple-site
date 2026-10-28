/******/ "use strict";
/******/ var __webpack_modules__ = ({

/***/ "./assets/src/js/app/auth-modal.js"
/*!*****************************************!*\
  !*** ./assets/src/js/app/auth-modal.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   AUTH_EVENTS: () => (/* binding */ AUTH_EVENTS),
/* harmony export */   "default": () => (/* binding */ HwAuthModal)
/* harmony export */ });
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

const AUTH_EVENTS = {
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

class HwAuthModal {

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


/***/ },

/***/ "./assets/src/js/app/verify-email.js"
/*!*******************************************!*\
  !*** ./assets/src/js/app/verify-email.js ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerVerifyEmail: () => (/* binding */ registerVerifyEmail)
/* harmony export */ });
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

function registerVerifyEmail() {

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


/***/ }

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	if (!(moduleId in __webpack_modules__)) {
/******/ 		delete __webpack_module_cache__[moduleId];
/******/ 		var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 		e.code = 'MODULE_NOT_FOUND';
/******/ 		throw e;
/******/ 	}
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/define property getters */
/******/ (() => {
/******/ 	// define getter functions for harmony exports
/******/ 	__webpack_require__.d = (exports, definition) => {
/******/ 		for(var key in definition) {
/******/ 			if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 				Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 			}
/******/ 		}
/******/ 	};
/******/ })();
/******/ 
/******/ /* webpack/runtime/hasOwnProperty shorthand */
/******/ (() => {
/******/ 	__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ })();
/******/ 
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!************************************!*\
  !*** ./assets/src/js/app/index.js ***!
  \************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _auth_modal__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./auth-modal */ "./assets/src/js/app/auth-modal.js");
/* harmony import */ var _verify_email__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./verify-email */ "./assets/src/js/app/verify-email.js");



// window.hw is the wp_localize_script payload for this handle, so extend it
// rather than replacing it. Assigned at module scope so anything loading after
// this bundle — the React booking form included — can call it right away;
// open() queues itself until the footer markup exists.
window.hw = window.hw || {};
window.hw.authModal = new _auth_modal__WEBPACK_IMPORTED_MODULE_0__["default"]();
window.hw.AUTH_EVENTS = _auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS;

// Legacy aliases. The modal grew a sign-up pane and was renamed at the JS layer,
// but window.hw.loginModal is what the React booking form already calls and what
// any third party would have found — so both names point at the one instance.
// AUTH_EVENTS is a superset of the old LOGIN_EVENTS keys, and the five original
// event *names* are unchanged, so existing listeners keep firing.
window.hw.loginModal = window.hw.authModal;
window.hw.LOGIN_EVENTS = _auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS;

// Alpine components owned by this bundle. Registered on alpine:init, which has
// not fired yet — this file runs in <head> and Alpine is deferred.
(0,_verify_email__WEBPACK_IMPORTED_MODULE_1__.registerVerifyEmail)();

jQuery(function($){

    $('[data-toggle="tooltip"]').tooltip();

    updateGalleryItemsHeight();

    $(window).on('resize', updateGalleryItemsHeight);
    
    $('.hotel-slider').slick({
        arrows: true,
        dots: false
    });

    $('.ajax-login-btn').on( 'click', Ajax_Login );

    $('#ajax-reset-password .reset-password-btn').on('click', ResetPassword)

    // Delegated: the pane is only rendered when sign-up is switched on.
    $(document).on('click', '.hw-signup-btn', SignUp);

    $('#help-bubble .bubble .trigger').on('click', function(){
        $(this).parent().toggleClass('open');
    })
    
});

function updateGalleryItemsHeight(){
    jQuery('.gallery .gallery-item').each(function(){
        let width = jQuery(this).outerWidth();
        console.log(width)
        jQuery(this).outerHeight(width);
    });
}

/**
 * Format price
 * @param {int} price 
 * @returns 
 */
function format_price( price ){
    
    return hw.currency_symbol + price + ' ' + hw.currency;
}

/**
 * Calculate age
 * @param {Date} dateString 
 * @param {Date} dateString 
 * @returns 
 */
function calc_age(birthDate, comparisonDate) {

    if(!birthDate instanceof Date){
        throw new TypeError("birthDate should be of type Date");
    }

    if(!comparisonDate instanceof Date){
        throw new TypeError("birthDate should be of type Date");
    }
    
    var age = comparisonDate.getFullYear() - birthDate.getFullYear();
    var m = comparisonDate.getMonth() - birthDate.getMonth();

    if (m < 0 || (m === 0 && comparisonDate.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
}

var is_doing_ajax_login = false;

/**
 * Perfom ajax login
 */
function Ajax_Login() {

    var container_el = jQuery('#ajax-login'),
        username_el = container_el.find('input[name="username"]'),
        pwd_el = container_el.find('input[name="password"]'),
        quote_form = jQuery('#quoteForm');

    // This is our request object. Each of these keys (action,
    // username & password) can be picked up in the PHP $_POST object.
    var request = {
       'action': 'ajax_login',
       'username': username_el.val(),
       'password': pwd_el.val()
    };

    // request already sent
    if(is_doing_ajax_login == true){

        return;
    }

    container_el.find('.errorMessage').remove();

    // check if values are set
    if (!request.username || !request.password) {
       
        if(!request.username.trim()){
            username_el.next().html(hw_messages.ajax_login_email_required);
            username_el.addClass('is-invalid');
        }
        
        if(!request.password.trim()){
            pwd_el.next().html(hw_messages.ajax_login_pwd_required);
            pwd_el.addClass('is-invalid');
        }
        
        return;
    }
        
    username_el.removeClass('is-invalid');
    pwd_el.removeClass('is-invalid');
    
    // console.log('Attempting to log in now.');
    container_el.addClass('working');
    container_el.find('input, button').prop('disabled', true);

    // Send our login request object to /wp-admin/admin-ajax.php
    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            is_doing_ajax_login = false;
            blockUI(container_el);
        }
    }).done(function(response) { // Success
        
        if (response.isLoggedIn) {

            // Let 3rd parties to prevent login i.e. on cart page
            const do_rediret = hw_applyFilters('ajax_login_do_redirect', true);

            // trigger the event
            jQuery(document).trigger('user_logged_in');

            // Native event for consumers that are not jQuery — the React booking
            // form persists its in-progress booking here. Dispatched
            // synchronously and before the reload, so listeners run first.
            window.hw.authModal.emit(_auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS.success);

            // Signed in, but the address has not been confirmed yet. Say so right
            // here rather than reloading into a page that will only bounce them to
            // /account/verify-email/. Closing the modal reloads, so the page still
            // catches up with the new session.
            const verification = response.verification || {};

            if (verification.required && window.hw.authModal.showVerification(verification)) {

                container_el.find('input, button').prop('disabled', false);
                container_el.removeClass('working');
                unblockUI(container_el);
                return;
            }

            // reload if allowed
            if(do_rediret){

                location.reload();
            }
            
        }else{

            jQuery(document).trigger('user_did_not_log_in');

            window.hw.authModal.emit(_auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS.error, {
                action: 'login',
                message: response.errorMessage,
            });

            container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(response.errorMessage));
        }

    }).fail(function() { // Failure
            
        container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(hw_messages.ajax_login_generic_error));

    }).always(function(response) { // Always
        
        if (!response.isLoggedIn) {

            container_el.find('input, button').prop('disabled', false);
            container_el.removeClass('working');
        }

        unblockUI(container_el);
    });
}

/**
 * Perfom ajax login
 */
function ResetPassword() {

    var container_el = jQuery(this).closest('#ajax-reset-password'),
        email_el = container_el.find('input[name="email"]');

    var request = {
       'action': 'hw_reset_password',
       'email': email_el.val(),
    };

    container_el.find('.notice').remove();

    // check if values are set
    if (!request.email) {
       
        if(!request.email.trim()){
            email_el.next().html(hw_messages.ajax_login_email_required);
            email_el.addClass('is-invalid');
        }
        
        return;
    }
        
    email_el.removeClass('is-invalid');
    
    container_el.find('input, button').prop('disabled', true);

    // Send our login request object to /wp-admin/admin-ajax.php
    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            blockUI(container_el);
        }
    }).done(function(response) { // Success
        
        let alert = jQuery('<p class="notice alert alert-success"></p>');

        if (response.success) {

            alert.addClass('alert-success').html(response.message);
        }else{

            alert.addClass('alert-danger').html(response.message);
        }

        container_el.prepend(alert);

    }).fail(function() { // Failure
            
        container_el.prepend(jQuery('<p class="notice alert alert-danger"></p>').html(hw_messages.ajax_login_generic_error));

    }).always(function(response) { // Always

        container_el.find('input, button').prop('disabled', false);
        unblockUI(container_el);
    });
}

/**
 * Create an account from the sign-up pane.
 *
 * Field errors go into each input's .next() div, matching how Ajax_Login and
 * ResetPassword write theirs; anything the server reports without a field lands
 * in a banner prepended to the pane.
 */
function SignUp() {

    var container_el = jQuery(this).closest('#ajax-signup');

    var fields = {
        first_name:       container_el.find('input[name="signup_first_name"]'),
        last_name:        container_el.find('input[name="signup_last_name"]'),
        email:            container_el.find('input[name="signup_email"]'),
        password:         container_el.find('input[name="signup_password"]'),
        password_confirm: container_el.find('input[name="signup_password_confirm"]'),
    };

    // Clear whatever the previous attempt left behind.
    container_el.find('.errorMessage').remove();
    container_el.find('.hw-lm__field-error').html('');
    container_el.find('input').removeClass('is-invalid');

    var invalid = function(key, message){
        fields[key].addClass('is-invalid').next().html(message);
    };

    // Client-side first, so an obvious mistake costs no round trip. The server
    // repeats every one of these — this is convenience, not the gate.
    var errors = 0;

    if(!fields.first_name.val().trim()){
        invalid('first_name', hw_messages.ajax_signup_first_name_required); errors++;
    }

    if(!fields.email.val().trim()){
        invalid('email', hw_messages.ajax_signup_email_required); errors++;
    }

    if(!fields.password.val()){
        invalid('password', hw_messages.ajax_signup_password_required); errors++;
    }else if(fields.password.val().length < 8){
        invalid('password', hw_messages.ajax_signup_password_short); errors++;
    }else if(fields.password.val() !== fields.password_confirm.val()){
        invalid('password_confirm', hw_messages.ajax_signup_password_mismatch); errors++;
    }

    if(errors){
        return;
    }

    var request = {
        'action': 'hw_signup',
        'nonce': container_el.find('input[name="hw_signup_nonce"]').val(),
        'signup_first_name': fields.first_name.val(),
        'signup_last_name': fields.last_name.val(),
        'signup_email': fields.email.val(),
        'signup_password': fields.password.val(),
        'signup_password_confirm': fields.password_confirm.val(),
    };

    container_el.find('input, button').prop('disabled', true);

    jQuery.ajax({
        url: ajax.ajax_url,
        data: request,
        method: 'POST',
        contentType: 'application/x-www-form-urlencoded',
        dataType: 'json',
        beforeSend: () => {
            blockUI(container_el);
        }
    }).done(function(response) {

        if(!response || !response.success){

            jQuery.each((response && response.errors) || {}, function(key, message){
                if(fields[key]){
                    invalid(key, message);
                }
            });

            if(response && response.message){
                container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(response.message));
            }

            window.hw.authModal.emit(_auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS.error, {
                action: 'signup',
                message: (response && response.message) || '',
            });

            return;
        }

        // Same contract the login flow uses — listeners persist state here,
        // synchronously, before we leave the page.
        window.hw.authModal.emit(_auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS.success);

        // Cancelable, and still fired so anything listening can react. The
        // redirect it guards is now only the fallback — see below.
        const go = window.hw.authModal.emit(_auth_modal__WEBPACK_IMPORTED_MODULE_0__.AUTH_EVENTS.registered, {
            userId: response.userId,
            redirect: response.redirect,
        });

        // A new account is always unconfirmed, so this is the normal path: swap
        // the modal to its verification pane instead of throwing the visitor onto
        // another page. Closing the modal reloads so the page catches up.
        const verification = response.verification || {};

        if (verification.required && window.hw.authModal.showVerification(verification)) {

            container_el.find('input, button').prop('disabled', false);
            unblockUI(container_el);
            return;
        }

        if(go && response.redirect){
            location.assign(response.redirect);
        }else{
            location.reload();
        }

    }).fail(function() {

        container_el.prepend(jQuery('<p class="errorMessage alert alert-danger"></p>').html(hw_messages.ajax_signup_generic_error));

    }).always(function() {

        container_el.find('input, button').prop('disabled', false);
        unblockUI(container_el);
    });
}

/**
 * Block element
 *
 * @param {jQuery Element} el
 * @param {string} msg
 */
function blockUI(el, msg){
        
    el.block({
        centerY: false,
        centerX: true,
        message: msg,
        css:{
            width: '100%'
        },
        overlayCSS:  { 
            backgroundColor: 'rgba(255,255,255,0.9)',
        }
    }).addClass('loading');
}

/**
 * Unblock element
 * 
 * @param {jQuery element} el 
 */
function unblockUI(el){

    el.unblock().removeClass('loading');
}

/**
 *  Hook support
 */

/**
 * Contains the hooks that get registered with this EventManager. The array for storage utilizes a "flat"
 * object literal such that looking up the hook utilizes the native object literal hash.
 */
var HW_STORAGE = {
    actions: {},
    filters: {}
};
function hw_getStorage() {
    return HW_STORAGE;
}

/**
 * Adds an action to the event manager.
 *
 * @param action Must contain namespace.identifier
 * @param callback Must be a valid callback function before this action is added
 * @param [priority=10] Used to control when the function is executed in relation to other callbacks bound to the same hook
 * @param [context] Supply a value to be used for this
 */
function hw_addAction(action, callback, priority, context) {
    if (typeof action === 'string' && typeof callback === 'function') {
        priority = parseInt(priority || 10, 10);
        hw_addHook('actions', action, callback, priority, context);
    }
}

/**
 * Performs an action if it exists. You can pass as many arguments as you want to this function; the only rule is
 * that the first argument must always be the action.
 */
function hw_doAction( /* action, arg1, arg2, ... */) {
    var args = Array.prototype.slice.call(arguments);
    var action = args.shift();
    if (typeof action === 'string') {
        hw_runHook('actions', action, args);
    }
}

/**
 * Removes the specified action if it contains a namespace.identifier & exists.
 *
 * @param action The action to remove
 * @param [callback] Callback function to remove
 */
function hw_removeAction(action, callback) {
    if (typeof action === 'string') {
        hw_removeHook('actions', action, callback);
    }
}

/**
 * Adds a filter to the event manager.
 *
 * @param filter Must contain namespace.identifier
 * @param callback Must be a valid callback function before this action is added
 * @param [priority=10] Used to control when the function is executed in relation to other callbacks bound to the same hook
 * @param [context] Supply a value to be used for this
 */
function hw_addFilter(filter, callback, priority, context) {
    if (typeof filter === 'string' && typeof callback === 'function') {
    priority = parseInt(priority || 10, 10);
    hw_addHook('filters', filter, callback, priority, context);
    }
}

/**
 * Performs a filter if it exists. You should only ever pass 1 argument to be filtered. The only rule is that
 * the first argument must always be the filter.
 */
function hw_applyFilters( /* filter, filtered arg, arg2, ... */) {
    var args = Array.prototype.slice.call(arguments);
    var filter = args.shift();
    if (typeof filter === 'string') {
        return hw_runHook('filters', filter, args);
    }
}

/**
 * Removes the specified filter if it contains a namespace.identifier & exists.
 *
 * @param filter The action to remove
 * @param [callback] Callback function to remove
 */
function hw_removeFilter(filter, callback) {
    if (typeof filter === 'string') {
        hw_removeHook('filters', filter, callback);
    }
}

/**
 * Removes the specified hook by resetting the value of it.
 *
 * @param type Type of hook, either 'actions' or 'filters'
 * @param hook The hook (namespace.identifier) to remove
 * @private
 */
function hw_removeHook(type, hook, callback, context) {
    if (!HW_STORAGE[type][hook]) {
        return;
    }
    if (!callback) {
        HW_STORAGE[type][hook] = [];
    } else {
    var handlers = HW_STORAGE[type][hook];
    var i;
    if (!context) {
            for (i = handlers.length; i--;) {
                if (handlers[i].callback === callback) {
                    handlers.splice(i, 1);
                }
            }
        } else {
            for (i = handlers.length; i--;) {
                var handler = handlers[i];
                if (handler.callback === callback && handler.context === context) {
                    handlers.splice(i, 1);
                }
            }
        }
    }
}

/**
* Adds the hook to the appropriate storage container
*
* @param type 'actions' or 'filters'
* @param hook The hook (namespace.identifier) to add to our event manager
* @param callback The function that will be called when the hook is executed.
* @param priority The priority of this hook. Must be an integer.
* @param [context] A value to be used for this
* @private
*/
function hw_addHook(type, hook, callback, priority, context) {
    var hookObject = {
        callback: callback,
        priority: priority,
        context: context
    };

    // Utilize 'prop itself' : http://jsperf.com/hasownproperty-vs-in-vs-undefined/19
    var hooks = HW_STORAGE[type][hook];
    if (hooks) {
        hooks.push(hookObject);
        hooks = hw_hookInsertSort(hooks);
    } else {
        hooks = [hookObject];
    }
    
    HW_STORAGE[type][hook] = hooks;
}

/**
 * Use an insert sort for keeping our hooks organized based on priority. This function is ridiculously faster
 * than bubble sort, etc: http://jsperf.com/javascript-sort
 *
 * @param hooks The custom array containing all of the appropriate hooks to perform an insert sort on.
 * @private
 */
function hw_hookInsertSort(hooks) {
    var tmpHook, j, prevHook;
    for (var i = 1, len = hooks.length; i < len; i++) {
    tmpHook = hooks[i];
    j = i;
    while ((prevHook = hooks[j - 1]) && prevHook.priority > tmpHook.priority) {
        hooks[j] = hooks[j - 1];
        --j;
    }
    hooks[j] = tmpHook;
    }
    return hooks;
}

/**
 * Runs the specified hook. If it is an action, the value is not modified but if it is a filter, it is.
 *
 * @param type 'actions' or 'filters'
 * @param hook The hook ( namespace.identifier ) to be ran.
 * @param args Arguments to pass to the action/filter. If it's a filter, args is actually a single parameter.
 * @private
 */
function _runHook(type, hook, args) {
    var handlers = STORAGE[type][hook];
    if (!handlers) {
    return type === 'filters' ? args[0] : false;
    }
    var i = 0,
    len = handlers.length;
    if (type === 'filters') {
    for (; i < len; i++) {
        args[0] = handlers[i].callback.apply(handlers[i].context, args);
    }
    } else {
    for (; i < len; i++) {
        handlers[i].callback.apply(handlers[i].context, args);
    }
    }
    return type === 'filters' ? args[0] : true;
}

/**
     * Runs the specified hook. If it is an action, the value is not modified but if it is a filter, it is.
     *
     * @param type 'actions' or 'filters'
     * @param hook The hook ( namespace.identifier ) to be ran.
     * @param args Arguments to pass to the action/filter. If it's a filter, args is actually a single parameter.
     * @private
     */
function hw_runHook(type, hook, args) {
    var handlers = HW_STORAGE[type][hook];
    if (!handlers) {
        return type === 'filters' ? args[0] : false;
    }
    var i = 0,
        len = handlers.length;
    if (type === 'filters') {
        for (; i < len; i++) {
            args[0] = handlers[i].callback.apply(handlers[i].context, args);
        }
    } else {
        for (; i < len; i++) {
            handlers[i].callback.apply(handlers[i].context, args);
        }
    }
    return type === 'filters' ? args[0] : true;
}

function getLang() {
    // Try WPML, Polylang, TranslatePress, fallback to <html lang="">
    return (
        (window.wpml_current_language && window.wpml_current_language()) ||
        (window.icl_lang) ||
        document.documentElement.getAttribute('lang')?.slice(0, 2) ||
        'en'
    );
}

/**
 * convets string date in format yyyy-mm-dd to date
 * @param {string} ymd 
 * @returns 
 */
function ymdToLocalDate(ymd) {

  const [y, m, d] = ymd.split('-').map(Number);

  // Use noon to dodge rare DST edge cases:
  return new Date(y, m - 1, d, 12, 0, 0, 0);
}

class GuestField{

    constructor(el, comparisonDate = null){

        this.el = jQuery(el);
        this.yearEl = this.el.find('.age-selector .year'),
        this.monthEl = this.el.find('.age-selector .month'),
        this.dayEl = this.el.find('.age-selector .day'),
        this.ageEl = this.el.find('.guest-age');

        this.messages = {
            'min' : this.el[0].hasAttribute('data-min-age-message') ? this.el.data('min-age-message') : messages.invalid_age,
            'max' : this.el[0].hasAttribute('data-max-age-message') ? this.el.data('max-age-message') : messages.invalid_age,
        }

        this.minAge = this.el[0].hasAttribute('data-min') ? parseInt(this.el.data('min')) : null;
        this.maxAge = this.el[0].hasAttribute('data-max') ? parseInt(this.el.data('max')) : null;

        // bind this instance to the dom element
        el.guestField = this;

        // if not comparison date is passed set it to today
        this.comparisonDate = comparisonDate ? comparisonDate : new Date();

        this.bindEvents();

        // run this the frist time, might be the fields are already filled
        this.updateAgeField();
    }
    
    bindEvents(){

        var _self = this;

        // day
        this.dayEl.on('keyup', function(){

            let val = jQuery(this).val();

            if(val.length >= 1){
                jQuery(this).addClass('is-valid')
                if(val.length == 2){
                    jQuery(this).parent().next().children('select').focus();
                }
                
                _self.updateAgeField();
            }else if(val.lenght < 1 || val === ''){
                jQuery(this).removeClass('is-valid').addClass('is-error');
            }
        });

        this.dayEl.on("change", function() {
            let v = parseInt(this.value);
            if (v < 1) this.value = 1;
            if (v > 31) this.value = 31;
        });

        // month
        this.monthEl.on('change', function(){

            jQuery(this).addClass('is-valid').parent().next().children('input').focus();
            
            _self.updateAgeField();
        });

        this.monthEl.on("change", function() {
            let v = parseInt(this.value);
            if (v < 1) this.value = 1;
            if (v > 12) this.value = 12;
        });

        // year
        this.yearEl.on("change", function() {
            let v = parseInt(this.value),
                max = jQuery(this).attr('max'),
                min = jQuery(this).attr('min');

            if (v < min) this.value = min;
            if (v > max) this.value = max;
        });

        this.yearEl.on('focusout', function(){

            let val = jQuery(this).val();

            if(val.length == 4){
                jQuery(this).addClass('is-valid');
                
                _self.updateAgeField();
            }else{
                jQuery(this).removeClass('is-valid').addClass('is-error');
            }
        });

        // validte the field only when all birth date fields are completed
        this.ageEl.on('change', function(){

            _self.validate();
        });
    }

    /**
     * Update age for a given field
     */
    updateAgeField(){
            
        let year = this.yearEl.val(),
            month = this.monthEl.val(),
            day = this.dayEl.val();

        if(year !== '' && month !== '' && day !== ''){

            let guest_age = new Date(year, (month - 1), day);

            let age = calc_age(guest_age, this.comparisonDate);
            console.log(guest_age, this.comparisonDate, age)
            this.ageEl.val(age).trigger('change');
        }
    }

    /**
     * Check whether the date is valid
     * @returns boolean
     */
    isValidDate(){

        let year = this.yearEl.val(),
            month = this.monthEl.val(),
            day = this.dayEl.val();

        if(year !== '' && month !== '' && day !== ''){

            return isDateValid(year, month, day);
        }

        return false;
    }

    validate(){

        this.removeError();

        // check Age
        let validation = this.isValid()
        if(validation instanceof Error){

            this.displayError(validation);

            this.el.find('.age-selector :input')
                .removeClass('is-valid')
                .addClass('is-invalid');

            return false;
        }

        // remove invalid class
        this.el.find('.age-selector :input')
            .removeClass('is-invalid')
            .addClass('is-valid');

        return true;
    }

    isValid(){

        let age = this.ageEl.val();

        if(age === NaN || age === '' || age === undefined){

            return new Error(messages.invalid_age);
        }

        if(!this.isValidDate()){

            return new Error(messages.invalid_dob);
        }

        if(this.minAge || this.maxAge){

            if(this.minAge){

                if(parseInt(age) < this.minAge){
    
                    return new Error(this.messages.min);
                }
            }
    
            if(this.maxAge){
    
                if(parseInt(age) > this.maxAge){
    
                    return new Error(this.messages.max);
                }
            }        
        }

        return true;
    }

    displayError(error){

        let errorEl = jQuery(`<div class="invalid-feedback age"></div>`);

        let errorMessage = error instanceof Error ? error.message : message;

        this.el.append(errorEl);
        errorEl.text(errorMessage).show();
    }

    removeError(){

        this.el.find('.invalid-feedback.age').remove();
    }
}
/* ─────────────────────────────────────────────────────────────────────────────
 * Public surface
 *
 * This file is the app bundle's entry. The moment it gained an `import` it
 * became an ES module, and webpack wraps entry modules in their own IIFE to
 * isolate them — `output.iife: false` in gulpfile.babel.js only controls the
 * outer bundle wrapper, not per-module scope. Everything declared above is
 * therefore closure-scoped, where it used to leak to `window` by accident.
 *
 * These are the ones other scripts actually call, so they are published
 * deliberately instead. Do not remove without checking the caller listed
 * beside each — they load separately and would fail silently at runtime.
 * ────────────────────────────────────────────────────────────────────────── */
Object.assign(window, {
    blockUI,          // cart.js, utils.js, admin-settings.js, POS bundle
    unblockUI,        // cart.js, utils.js, admin-settings.js, POS bundle
    format_price,     // cart.js
    ymdToLocalDate,   // cart.js
    getLang,          // cart.js, template-parts/home/hero/content/countdown.php
    calc_age,         // assets/js/admin/quote.js
    GuestField,       // cart.js — `new GuestField(...)`, a class rather than a function

    // Hook API — a documented extension point, so the whole surface ships even
    // though only hw_addFilter has a caller inside this repo today.
    hw_addAction,
    hw_doAction,
    hw_removeAction,
    hw_addFilter,
    hw_applyFilters,
    hw_removeFilter,
});

})();


//# sourceMappingURL=app.js.map