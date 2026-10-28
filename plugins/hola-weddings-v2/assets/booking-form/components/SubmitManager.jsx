import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { useBooking }     from '../hooks/useBooking';
import { useCart }        from '../hooks/useCart';
import { useIsLoggedIn }  from '../hooks/useIsLoggedIn';
import { devStore }       from '../devStore';
import { submitRegistry } from '../submitRegistry';
import { validatePayment, validateDisclaimer, validateAccount, wantsAccount } from '../utils/quoteValidation';
import { submitQuote }    from '../api/booking';
import Dialog             from './Dialog';
import { LoaderCircle }  from 'lucide-react';

const IS_PAYMENT_ENABLED = Boolean( window.hwBooking?.isPaymentEnabled );
const INSURANCE_PLANS    = window.hwBooking?.insuranceData?.plans ?? [];
const ADDITIONAL_NOTES   = window.hwBooking?.additionalNotes ?? { enabled: false, label: __( 'Additional Notes', 'hola-weddings' ), required: false, description: '' };
const STEP_BILLING       = 2;
const HIDE_CREATE_ACCOUNT = Boolean( window.hwBooking?.hideCreateAccount );

// Every element a validation error can mark. Submission is gated far below the
// card that failed on mobile, so the error has to be brought into view — without
// this the button just looks dead.
const ERROR_SELECTOR = '.is-invalid, .pay-auth--error, .step-card__signature--error';

function scrollToFirstError() {
	const el = document.querySelector( ERROR_SELECTOR );
	if ( el ) el.scrollIntoView( { behavior: 'smooth', block: 'center' } );
}

export default function SubmitManager() {
	const booking    = useBooking();
	const isLoggedIn = useIsLoggedIn();
	const { grandTotal, minDepositAmount, deposit, isRoomAvailable } = useCart();

	const [ loading, setLoading ] = useState( false );
	const [ dialog,  setDialog  ] = useState( null );

	const {
		payment,
		notes,
		billing,
		updateCustomer,
		accountEmail,
		password,
		confirmPassword,
		guests,
		search,
		selectedRoom,
		selectedPackage,
		insurance,
		dispatch,
	} = booking;

	const cardType   = payment.cardType ?? 'visa';
	const isAmex     = cardType === 'amex';
	const cvvLength  = isAmex ? 4 : 3;
	const cardLength = isAmex ? 15 : 16;

	const needsPaymentValidation = IS_PAYMENT_ENABLED && isRoomAvailable;

	// No card to fill in → nothing to validate and no account to create.
	const accountHidden   = isLoggedIn || HIDE_CREATE_ACCOUNT;
	const creatingAccount = wantsAccount( { accountEmail, password, hidden: accountHidden } );

	async function doSubmit() {
		dispatch( { type: 'SET_SHOW_ERRORS',    payload: true } );
		dispatch( { type: 'SET_SERVER_ERRORS',  payload: {} } );

		if ( ! devStore.get().skipValidation ) {
			const pe = needsPaymentValidation
				? validatePayment( payment, { isAmex, cvvLength, cardLength, grandTotal, minDepositAmount } )
				: {};
			const de = IS_PAYMENT_ENABLED ? validateDisclaimer( payment ) : {};
			const ne = ADDITIONAL_NOTES.enabled && ADDITIONAL_NOTES.required && ! ( notes ?? '' ).trim();
			// Account creation is optional — this only fires on a half-filled card.
			const acc = validateAccount( { accountEmail, password, confirmPassword, hidden: accountHidden } );

			if ( Object.keys( pe ).length > 0 || Object.keys( de ).length > 0 || ne || Object.keys( acc ).length > 0 ) {

				// ── TEMPORARY DIAGNOSTIC ──────────────────────────────────────────
				// Silent-submit investigation: report which gate blocked the submit and
				// whether the UI that should show the error is actually rendered.
				// Remove once the failing configuration has been identified.
				const accountCardRendered = ! accountHidden;
				const blockedBy = [
					Object.keys( pe ).length > 0 && `payment (${ Object.keys( pe ).join( ', ' ) })`,
					Object.keys( de ).length > 0 && `disclaimer (${ Object.keys( de ).join( ', ' ) })`,
					ne && 'additionalNotes',
					Object.keys( acc ).length > 0 && `account (${ Object.keys( acc ).join( ', ' ) })${ accountCardRendered ? '' : ' — NO UI RENDERED' }`,
				].filter( Boolean );

				console.warn( '[HW] Submit blocked by:', blockedBy.join( ' | ' ) );
				console.warn( '[HW] Submit diagnostics', {
					paymentErrors:     pe,
					disclaimerErrors:  de,
					notesBlocked:      ne,
					accountErrors:     acc,
					account: {
						isLoggedIn,
						accountCardRendered,
						accountEmail:   accountEmail ?? '',
						creatingAccount,
						passwordLength: ( password ?? '' ).length,
					},
					disclaimer: {
						termsAccepted: Boolean( payment.termsAccepted ),
						hasSignature:  Boolean( payment.signature ),
						acceptCharge:  Boolean( payment.acceptCharge ),
					},
					config: {
						isPaymentEnabled:        IS_PAYMENT_ENABLED,
						isRoomAvailable,
						needsPaymentValidation,
						hideLoginCta:            Boolean( window.hwBooking?.hideLoginCta ),
						hideCreateAccount:       HIDE_CREATE_ACCOUNT,
						notesEnabled:            ADDITIONAL_NOTES.enabled,
						notesRequired:           ADDITIONAL_NOTES.required,
						viewport:                `${ window.innerWidth }x${ window.innerHeight }`,
					},
				} );
				// ── END TEMPORARY DIAGNOSTIC ──────────────────────────────────────

				// Deferred so the SHOW_ERRORS re-render has painted the markers
				setTimeout( scrollToFirstError, 0 );
				return;
			}
		}

		setLoading( true );
		try {
			const response = await submitQuote( {
				checkin:         search.checkin,
				checkout:        search.checkout,
				adults:          search.adults,
				teens:           search.teens,
				children:        search.children,
				infants:         search.infants,
				roomBlockId:     selectedRoom?.id ?? null,
				packageId:       selectedPackage?.id ?? null,
				insurancePlanId: insurance?.planId ?? 0,
				notes:           notes,
				customer: {
					firstName: billing.firstName,
					lastName:  billing.lastName,
					email:     billing.email,
					phone:     billing.phone,
					address:   billing.address,
					city:      billing.city,
					state:     billing.state,
					country:   billing.country,
					zip:       billing.zip,
				},
				guests: guests.map( g => ( {
					title:      g.title,
					name:       g.name,
					lastname:   g.lastname,
					personType: g.personType,
					dob:        { day: g.day, month: g.month, year: g.year },
				} ) ),
				payment: {
					cardType:      payment.cardType,
					cardOwner:     payment.cardOwner,
					cardNumber:    payment.cardNumber,
					cardMonth:     payment.cardMonth,
					cardYear:      payment.cardYear,
					cvv:           payment.cvv,
					depositType:   payment.depositType,
					depositAmount: payment.depositType === 'custom' ? payment.depositAmount : null,
					acceptCharge:  payment.acceptCharge,
				},
				termsAccepted:      Boolean( payment.termsAccepted ),
				signature:          payment.signature ?? null,
				// Omitted unless the visitor actually asked for an account — the
				// server treats their absence as "book without creating a user".
				accountEmail:       creatingAccount ? accountEmail.trim() : undefined,
				password:           creatingAccount ? password : undefined,
				updateCustomerData: isLoggedIn ? Boolean( updateCustomer ) : undefined,
			} );

			if ( response.confirmation_url && ! response.test ) {
				setDialog( {
					type:        'success',
					title:       __( 'Booking Submitted!', 'hola-weddings' ),
					message:     __( "Your reservation has been received. You'll be redirected to your confirmation page in a moment.", 'hola-weddings' ),
					redirectUrl: response.confirmation_url,
				} );
			} else {
				// Test mode — show result in dialog instead of redirecting
				setDialog( {
					type:    'success',
					title:   __( 'Booking Request Submitted!', 'hola-weddings' ),
					message: __( 'Your request has been received. Our team will be in touch shortly to confirm your reservation.', 'hola-weddings' ),
				} );
			}
		} catch ( err ) {
			if ( err?.data?.errors ) {
				dispatch( { type: 'SET_SERVER_ERRORS', payload: err.data.errors } );
			}
			setDialog( {
				type:    'error',
				title:   __( 'Submission Failed', 'hola-weddings' ),
				message: err?.message ?? __( 'Something went wrong. Please try again.', 'hola-weddings' ),
				errors:  err?.data?.errors ?? null,
				btnText: __( 'Close', 'hola-weddings' )
			} );
		} finally {
			setLoading( false );
		}
	}

	const doSubmitRef = useRef( doSubmit );
	doSubmitRef.current = doSubmit;

	useEffect( () => {
		submitRegistry.submit = () => doSubmitRef.current();
		return () => { submitRegistry.submit = null; };
	}, [] );

	const REDIRECT_DELAY = 3000;

	useEffect( () => {
		if ( ! dialog?.redirectUrl ) return;
		const timer = setTimeout( () => {
			window.location.href = dialog.redirectUrl;
		}, REDIRECT_DELAY );
		return () => clearTimeout( timer );
	}, [ dialog?.redirectUrl ] );

	return (
		<>
			{ loading && (
				<div className="hw-submit-overlay">
					<div className="hw-submit-overlay__inner">
						<LoaderCircle size={ 48 } className="hw-spin" />
						<p className="hw-submit-overlay__text">{ __( 'Submitting your booking…', 'hola-weddings' ) }</p>
						<p className="hw-submit-overlay__sub">{ __( 'Please wait, do not close this page.', 'hola-weddings' ) }</p>
					</div>
				</div>
			) }
			<Dialog
				open={ dialog !== null }
				type={ dialog?.type }
				title={ dialog?.title }
				message={ dialog?.message }
				errors={ dialog?.errors ?? null }
				btnText={ dialog?.btnText }
				redirectUrl={ dialog?.redirectUrl }
				redirectDelay={ REDIRECT_DELAY }
				onClose={ dialog?.redirectUrl ? undefined : () => setDialog( null ) }
			/>
		</>
	);
}
