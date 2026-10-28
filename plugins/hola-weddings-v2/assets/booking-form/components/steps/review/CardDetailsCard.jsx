import { __ } from '@wordpress/i18n';
import { useBooking } from '../../../hooks/useBooking';
import { StepCard }   from '../../StepCard';
import { CalendarDays, CreditCard, Lock } from 'lucide-react';

const ASSETS_URL = window.hwBooking?.assetsUrl ?? '';

const CARD_TYPES = [
	{ value: 'visa', label: 'Visa',            img: 'visa.png' },
	{ value: 'mc',   label: 'Mastercard',       img: 'mc.png'   },
	{ value: 'amex', label: 'American Express', img: 'amex.png' },
];

function detectCardType( digits ) {
	if ( /^4/.test( digits ) )                                          return 'visa';
	if ( /^3[47]/.test( digits ) )                                      return 'amex';
	if ( /^5[1-5]/.test( digits ) || /^2[2-7]/.test( digits ) )        return 'mc';
	return null;
}

function formatCardNumber( digits, isAmex ) {
	if ( ! digits ) return '';
	if ( isAmex ) {
		return [ digits.slice( 0, 4 ), digits.slice( 4, 10 ), digits.slice( 10, 15 ) ]
			.filter( Boolean ).join( ' ' );
	}
	return digits.match( /.{1,4}/g )?.join( ' ' ) ?? '';
}

function expiryDisplay( month, year ) {
	if ( ! month ) return '';
	return year ? `${ month } / ${ year }` : month;
}

export default function CardDetailsCard( { errors = {} } ) {
	const { payment, dispatch } = useBooking();

	const cardType  = payment.cardType ?? 'visa';
	const isAmex    = cardType === 'amex';
	const cvvLength = isAmex ? 4 : 3;

	function set( field ) {
		return ( e ) => dispatch( { type: 'SET_PAYMENT', payload: { [ field ]: e.target.value } } );
	}
	function setDirect( field, value ) {
		dispatch( { type: 'SET_PAYMENT', payload: { [ field ]: value } } );
	}

	function handleCardNumberChange( e ) {
		const raw      = e.target.value.replace( /\D/g, '' );
		const detected = detectCardType( raw );
		const newType  = detected ?? cardType;
		const digits   = raw.slice( 0, newType === 'amex' ? 15 : 16 );
		if ( detected && detected !== cardType ) {
			dispatch( { type: 'SET_PAYMENT', payload: { cardType: detected, cvv: '' } } );
		}
		setDirect( 'cardNumber', digits );
	}

	function handleExpiryChange( e ) {
		const digits = e.target.value.replace( /\D/g, '' ).slice( 0, 6 );
		dispatch( { type: 'SET_PAYMENT', payload: {
			cardMonth: digits.slice( 0, 2 ),
			cardYear:  digits.slice( 2, 6 ),
		} } );
	}

	function fc( key ) {
		return `form-control${ errors[ key ] ? ' is-invalid' : '' }`;
	}

	return (
		<StepCard className="mb-3">
			<StepCard.Header icon={ <CreditCard size={ 16 } /> } title={ __( 'Card Details', 'hola-weddings' ) } />

			{ /* Card type logos */ }
			<div className="form-group">
				<div className="d-flex gap-2">
					{ CARD_TYPES.map( ( { value, label, img } ) => {
						const active = cardType === value;
						return (
							<label
								key={ value }
								className={ `pay-card-type${ active ? ' pay-card-type--active' : '' }` }
								title={ label }
							>
								<input
									type="radio"
									name="cardType"
									value={ value }
									checked={ active }
									onChange={ () => { setDirect( 'cardType', value ); setDirect( 'cvv', '' ); } }
									className="sr-only"
								/>
								<img src={ `${ ASSETS_URL }/img/${ img }` } alt={ label } className="pay-card-img" />
							</label>
						);
					} ) }
				</div>
			</div>

			{ /* Card number + expiry + CVV */ }
			<div className="form-row">
				<div className="form-group col-md-6">
					<label>{ __( 'Card Number', 'hola-weddings' ) }</label>
					<div className="pay-input-wrap">
						<CreditCard size={ 14 } className="pay-input-icon" />
						<input
							type="text"
							inputMode="numeric"
							className={ `${ fc( 'cardNumber' ) } pay-input--icon` }
							value={ formatCardNumber( payment.cardNumber ?? '', isAmex ) }
							onChange={ handleCardNumberChange }
							placeholder={ isAmex ? '•••• •••••• •••••' : '•••• •••• •••• ••••' }
							autoComplete="cc-number"
						/>
					</div>
					{ errors.cardNumber && <div className="invalid-feedback d-block">{ errors.cardNumber }</div> }
				</div>

				<div className="form-group col-md-3">
					<label>{ __( 'Expiration Date', 'hola-weddings' ) }</label>
					<div className="pay-input-wrap">
						<CalendarDays size={ 14 } className="pay-input-icon" />
						<input
							type="text"
							inputMode="numeric"
							className={ `form-control pay-input--icon${ errors.expiry ? ' is-invalid' : '' }` }
							value={ expiryDisplay( payment.cardMonth ?? '', payment.cardYear ?? '' ) }
							onChange={ handleExpiryChange }
							placeholder={ __( 'MM / YYYY', 'hola-weddings' ) }
							autoComplete="cc-exp"
						/>
					</div>
					{ errors.expiry && <div className="invalid-feedback d-block">{ errors.expiry }</div> }
				</div>

				<div className="form-group col-md-3">
					<label>{ isAmex ? 'CID' : 'CVV' }</label>
					<div className="pay-input-wrap">
						<Lock size={ 14 } className="pay-input-icon" />
						<input
							type="text"
							inputMode="numeric"
							className={ `${ fc( 'cvv' ) } pay-input--icon` }
							placeholder={ isAmex ? '••••' : '•••' }
							value={ payment.cvv ?? '' }
							onChange={ ( e ) => {
								const val = e.target.value.replace( /\D/g, '' );
								if ( val.length <= cvvLength ) setDirect( 'cvv', val );
							} }
							autoComplete="cc-csc"
						/>
					</div>
					{ errors.cvv && <div className="invalid-feedback d-block">{ errors.cvv }</div> }
				</div>
			</div>

			{ /* Card owner — full width, last */ }
			<div className="form-group">
				<label>{ __( 'Card Owner', 'hola-weddings' ) }</label>
				<input
					type="text"
					className={ fc( 'cardOwner' ) }
					value={ payment.cardOwner ?? '' }
					onChange={ set( 'cardOwner' ) }
					autoComplete="cc-name"
				/>
				{ errors.cardOwner && <div className="invalid-feedback">{ errors.cardOwner }</div> }
			</div>

		</StepCard>
	);
}
