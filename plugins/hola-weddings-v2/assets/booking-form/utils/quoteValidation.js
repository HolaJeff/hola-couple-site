import { __, sprintf } from '@wordpress/i18n';
import { LOCALE }      from './locale';

const CURRENCY = window.hwBooking?.currency ?? 'USD';

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

export function validatePayment( payment, { isAmex, cvvLength, cardLength, grandTotal, minDepositAmount } ) {
	const errors = {};
	const cardOwner     = ( payment.cardOwner  ?? '' ).trim();
	const cardNumber    = ( payment.cardNumber  ?? '' ).replace( /\D/g, '' );
	const cardMonth     = String( payment.cardMonth ?? '' ).trim();
	const cardYear      = String( payment.cardYear  ?? '' ).trim();
	const cvv           = String( payment.cvv       ?? '' ).trim();
	const depositType   = payment.depositType ?? 'deposit';
	const depositAmount = Number( payment.depositAmount ?? minDepositAmount );

	const cvvName       = isAmex ? 'CID' : 'CVV';

	if ( ! cardOwner )                           errors.cardOwner  = __( 'Card owner is required.', 'hola-weddings' );
	if ( ! cardNumber )                          errors.cardNumber = __( 'Card number is required.', 'hola-weddings' );
	else if ( cardNumber.length !== cardLength ) {
		/* translators: %d: number of digits on the card */
		errors.cardNumber = sprintf( __( 'Card number must be %d digits.', 'hola-weddings' ), cardLength );
	}

	if ( ! cardMonth || ! cardYear )             errors.expiry = __( 'Expiration date is required.', 'hola-weddings' );
	else if ( !/^\d{1,2}$/.test( cardMonth ) || +cardMonth < 1 || +cardMonth > 12 )
	                                             errors.expiry = __( 'Enter a valid month (1–12).', 'hola-weddings' );
	else if ( !/^\d{4}$/.test( cardYear ) || +cardYear < new Date().getFullYear() )
	                                             errors.expiry = __( 'Enter a valid 4-digit year.', 'hola-weddings' );

	if ( ! cvv ) {
		/* translators: %s: card security code name, CID or CVV */
		errors.cvv = sprintf( __( '%s is required.', 'hola-weddings' ), cvvName );
	} else if ( cvv.length !== cvvLength ) {
		/* translators: 1: card security code name, CID or CVV. 2: number of digits */
		errors.cvv = sprintf( __( '%1$s must be %2$d digits.', 'hola-weddings' ), cvvName, cvvLength );
	}

	if ( depositType === 'custom' && depositAmount < minDepositAmount ) {
		/* translators: %s: formatted minimum amount, e.g. $500.00 */
		errors.depositAmount = sprintf( __( 'Minimum amount is %s.', 'hola-weddings' ), formatPrice( minDepositAmount ) );
	}

	if ( ! payment.acceptCharge )                errors.acceptCharge = __( 'You must authorize the charge to continue.', 'hola-weddings' );

	return errors;
}

export function validateDisclaimer( payment ) {
	const errors = {};
	if ( ! payment.termsAccepted ) errors.termsAccepted = __( 'Please read and accept the Terms & Conditions.', 'hola-weddings' );
	if ( ! payment.signature     ) errors.signature     = __( 'Please add your electronic signature.', 'hola-weddings' );
	return errors;
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Account creation is optional: nothing is required until the visitor starts
 * filling the card in, at which point the whole set has to be coherent — the
 * server can only create the user with a valid email and an 8+ char password.
 *
 * `hidden` covers the cases where there is no card to fill: already logged in,
 * or the site hides it via hw_booking_form_hide_create_account.
 */
export function validateAccount( { accountEmail = '', password = '', confirmPassword = '', hidden = false } ) {
	const errors = {};
	if ( hidden ) return errors;

	const email = ( accountEmail ?? '' ).trim();
	const pwd   = password ?? '';
	const conf  = confirmPassword ?? '';

	if ( ! email && ! pwd && ! conf ) return errors;

	if ( ! email )                  errors.accountEmail    = __( 'Email is required to create an account.', 'hola-weddings' );
	else if ( ! EMAIL_RE.test( email ) )
	                                errors.accountEmail    = __( 'Enter a valid email address.', 'hola-weddings' );

	if ( ! pwd )                    errors.password        = __( 'Password is required to create an account.', 'hola-weddings' );
	else if ( pwd.length < 8 )      errors.password        = __( 'Password must be at least 8 characters.', 'hola-weddings' );

	if ( conf !== pwd )             errors.confirmPassword = __( 'Passwords do not match.', 'hola-weddings' );

	return errors;
}

/** True when the visitor has asked for an account by filling the card in. */
export function wantsAccount( { accountEmail = '', password = '', hidden = false } ) {
	if ( hidden ) return false;
	return Boolean( ( accountEmail ?? '' ).trim() ) || Boolean( password );
}
