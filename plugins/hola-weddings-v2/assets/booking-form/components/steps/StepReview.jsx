import { __, sprintf } from '@wordpress/i18n';
import { useBooking }     from '../../hooks/useBooking';
import { useCart }        from '../../hooks/useCart';
import { useIsLoggedIn }  from '../../hooks/useIsLoggedIn';
import { submitRegistry } from '../../submitRegistry';
import { validatePayment, validateDisclaimer, validateAccount } from '../../utils/quoteValidation';
import { StepCard }       from '../StepCard';
import AccountCard        from './review/AccountCard';
import DepositCard        from './review/DepositCard';
import CardDetailsCard    from './review/CardDetailsCard';
import DisclaimerCard     from './review/DisclaimerCard';
import CancellationCard  from './review/CancellationCard';
import PaymentPolicyCard from './review/PaymentPolicyCard';
import { ArrowLeft, CalendarCheck, MessageSquare } from 'lucide-react';
import { LOCALE }         from '../../utils/locale';

const IS_PAYMENT_ENABLED = Boolean( window.hwBooking?.isPaymentEnabled );
const INSURANCE_PLANS    = window.hwBooking?.insuranceData?.plans ?? [];
const CURRENCY           = window.hwBooking?.currency ?? 'USD';
const STEP_BILLING       = 2;
const HIDE_CREATE_ACCOUNT = Boolean( window.hwBooking?.hideCreateAccount );
const ADDITIONAL_NOTES   = window.hwBooking?.additionalNotes ?? { enabled: false, label: __( 'Additional Notes', 'hola-weddings' ), required: false, description: '' };

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

export default function StepReview() {
	const booking    = useBooking();
	const isLoggedIn = useIsLoggedIn();
	const { grandTotal, minDepositAmount, deposit, isRoomAvailable } = useCart();

	const { billing, notes, payment, showErrors, accountEmail, password, confirmPassword, dispatch } = booking;

	const cardType      = payment.cardType    ?? 'visa';
	const isAmex        = cardType === 'amex';
	const cvvLength     = isAmex ? 4 : 3;
	const cardLength    = isAmex ? 15 : 16;
	const depositType   = payment.depositType ?? 'deposit';
	const payableAmount = depositType === 'deposit' ? deposit
		: depositType === 'full'   ? grandTotal
		: Number( payment.depositAmount ?? minDepositAmount );

	const needsPaymentValidation = IS_PAYMENT_ENABLED && isRoomAvailable;

	const payErrors  = showErrors && needsPaymentValidation
		? validatePayment( payment, { isAmex, cvvLength, cardLength, grandTotal, minDepositAmount } )
		: {};
	const discErrors = showErrors && IS_PAYMENT_ENABLED
		? validateDisclaimer( payment )
		: {};
	const notesError = showErrors && ADDITIONAL_NOTES.enabled && ADDITIONAL_NOTES.required && ! ( notes ?? '' ).trim()
		? sprintf( /* translators: %s: name of the required field */ __( '%s is required.', 'hola-weddings' ), ADDITIONAL_NOTES.label )
		: null;

	// Account creation is optional — validated only once the visitor starts
	// filling the card in. Same helper the submit gate uses.
	const accountErrors = showErrors
		? validateAccount( {
			accountEmail,
			password,
			confirmPassword,
			hidden: isLoggedIn || HIDE_CREATE_ACCOUNT,
		} )
		: {};

	const isUS             = billing?.country?.toUpperCase() === 'US';
	const insuranceSkipped = ! isUS || INSURANCE_PLANS.length === 0;

	function handleBack() {
		if ( insuranceSkipped ) {
			dispatch( { type: 'SET_STEP', payload: STEP_BILLING } );
		} else {
			dispatch( { type: 'PREV_STEP' } );
		}
	}

	function handleSubmit( e ) {
		e.preventDefault();
		submitRegistry.submit?.();
	}

	return (
		<div>
			<AccountCard
				confirmPassword={ confirmPassword }
				onConfirmChange={ ( v ) => dispatch( { type: 'SET_CONFIRM_PASSWORD', payload: v } ) }
				errors={ accountErrors }
			/>

			{ ADDITIONAL_NOTES.enabled && (
				<StepCard className="mb-3">
					<StepCard.Header
						icon={ <MessageSquare size={ 16 } /> }
						title={ ADDITIONAL_NOTES.required
							? sprintf( /* translators: %s: name of the notes field */ __( '%s (Required)', 'hola-weddings' ), ADDITIONAL_NOTES.label )
							: ADDITIONAL_NOTES.label }
					/>
					<div className="form-group mb-0">
						{ ADDITIONAL_NOTES.description && (
							<p className="text-muted small mb-2">{ ADDITIONAL_NOTES.description }</p>
						) }
						<textarea
							className={ `form-control${ notesError ? ' is-invalid' : '' }` }
							rows={ 4 }
							value={ notes ?? '' }
							onChange={ ( e ) => dispatch( { type: 'SET_NOTES', payload: e.target.value } ) }
						/>
						{ notesError && <div className="invalid-feedback">{ notesError }</div> }
					</div>
				</StepCard>
			) }

			{ IS_PAYMENT_ENABLED && (
				<>
					{ isRoomAvailable && (
						<>
							<DepositCard     errors={ payErrors } />
							<CardDetailsCard errors={ payErrors } />
						</>
					) }

					<CancellationCard />
					<PaymentPolicyCard />
					<DisclaimerCard
						errors={{ ...discErrors, acceptCharge: payErrors.acceptCharge }}
						showAuth={ isRoomAvailable }
						payableAmount={ formatPrice( payableAmount ) }
					/>
				</>
			) }

			<form id="hw-booking-form" onSubmit={ handleSubmit }>
				<div className="d-flex justify-content-between">
					<button type="button" className="btn btn-secondary" onClick={ handleBack }>
						<ArrowLeft size={ 18 } />
						{ __( 'Back', 'hola-weddings' ) }
					</button>
					<button type="submit" className="btn btn-success">
						{ __( 'Submit Booking', 'hola-weddings' ) } <CalendarCheck size={ 18 } />
					</button>
				</div>
			</form>
		</div>
	);
}
