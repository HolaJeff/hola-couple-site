import { __ } from '@wordpress/i18n';
import { useState }    from '@wordpress/element';
import { useBooking } from '../../hooks/useBooking';
import { StepCard }   from '../StepCard';
import { ArrowLeft, ArrowRight, Check, User } from 'lucide-react';
import COUNTRIES from '../../../../includes/data/countries.json';
import STATES    from '../../../../includes/data/states.json';

const US_STATES    = Object.entries( STATES.US ).map( ( [ code, name ] ) => ( { code, name } ) );
const HAS_CUSTOMER = Boolean( window.hwBooking?.hasCustomer );

const FIELDS = [
	{ name: 'firstName', label: __( 'First Name', 'hola-weddings' ), col: 'col-md-6' },
	{ name: 'lastName',  label: __( 'Last Name', 'hola-weddings' ),  col: 'col-md-6' },
	{ name: 'email',     label: __( 'Email', 'hola-weddings' ),      col: 'col-md-6', type: 'email' },
	{ name: 'phone',     label: __( 'Phone', 'hola-weddings' ),      col: 'col-md-6', type: 'tel' },
	{ name: 'address',   label: __( 'Address', 'hola-weddings' ),    col: 'col-md-9' },
	{ name: 'zip',       label: __( 'ZIP', 'hola-weddings' ),        col: 'col-md-3' },
	{ name: 'city',      label: __( 'City', 'hola-weddings' ),       col: 'col-md-5' },
];

function validate( billing, isUS ) {
	const errors = {};

	FIELDS.forEach( ( { name } ) => {
		if ( ! ( billing[ name ] ?? '' ).trim() ) errors[ name ] = __( 'This field is required.', 'hola-weddings' );
	} );

	if ( billing.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( billing.email ) ) {
		errors.email = __( 'Enter a valid email address.', 'hola-weddings' );
	}

	if ( ! ( billing.country ?? '' ) ) {
		errors.country = __( 'Please select a country.', 'hola-weddings' );
	}

	// State is always required — dropdown for US, free text for others
	if ( ! ( billing.state ?? '' ).trim() ) {
		errors.state = isUS ? __( 'Please select a state.', 'hola-weddings' ) : __( 'This field is required.', 'hola-weddings' );
	}

	return errors;
}

export default function StepBilling() {
	const { billing, guests, updateCustomer, dispatch, nextStep, prevStep } = useBooking();

	const [ showErrors,   setShowErrors   ] = useState( false );
	const [ sameAsGuest,  setSameAsGuest  ] = useState( false );

	const isUS   = billing.country === 'US';
	const errors = showErrors ? validate( billing, isUS ) : {};

	const firstGuest = guests?.[0];

	function handleSameAsGuest( checked ) {
		setSameAsGuest( checked );
		dispatch( { type: 'SET_BILLING', payload: {
			firstName: checked ? ( firstGuest?.name     ?? '' ) : '',
			lastName:  checked ? ( firstGuest?.lastname ?? '' ) : '',
		} } );
	}

	function set( field ) {
		return ( e ) => dispatch( { type: 'SET_BILLING', payload: { [ field ]: e.target.value } } );
	}

	function handleCountryChange( e ) {
		const country = e.target.value;
		const state   = country === 'US' ? US_STATES[ 0 ].code : '';
		dispatch( { type: 'SET_BILLING', payload: { country, state } } );
	}

	function handleNext() {
		setShowErrors( true );
		if ( Object.keys( validate( billing, isUS ) ).length === 0 ) nextStep();
	}

	function fc( key ) {
		return `form-control${ errors[ key ] ? ' is-invalid' : '' }`;
	}

	return (
		<div>
		<StepCard className="mb-3">
			<StepCard.Header icon={ <User size={ 16 } /> } title={ __( 'Main Contact Information', 'hola-weddings' ) } />

			{ firstGuest?.name?.trim() && (
				<label
					htmlFor="sameAsGuest"
					className={ `border pay-auth mb-3${ sameAsGuest ? ' pay-auth--checked' : '' }` }
				>
					<input
						type="checkbox"
						id="sameAsGuest"
						className="sr-only"
						checked={ sameAsGuest }
						onChange={ ( e ) => handleSameAsGuest( e.target.checked ) }
					/>
					<span className={ `pay-auth__box${ ! sameAsGuest ? ' pay-auth__box' : '' }` }>
						{ sameAsGuest && <Check size={ 14 } className="pay-auth__check" /> }
					</span>
					<span className="pay-auth__text">
						{ __( 'The main contact is the primary traveler.', 'hola-weddings' ) }
					</span>
				</label>
			) }

			<div className="form-row">
				{ FIELDS.map( ( { name, label, col, type = 'text' } ) => (
					<div key={ name } className={ `form-group ${ col }` }>
						<label>{ label }</label>
						<input
							type={ type }
							className={ fc( name ) }
							value={ billing[ name ] ?? '' }
							onChange={ set( name ) }
						/>
						{ errors[ name ] && (
							<div className="invalid-feedback">{ errors[ name ] }</div>
						) }
					</div>
				) ) }

				{ /* State — dropdown for US, free text for all other countries */ }
				<div className="form-group col-md-3">
					<label>{ __( 'State', 'hola-weddings' ) }</label>
					{ isUS ? (
						<>
							<select
								className={ fc( 'state' ) }
								value={ billing.state ?? '' }
								onChange={ set( 'state' ) }
							>
								{ US_STATES.map( ( { code, name } ) => (
									<option key={ code } value={ code }>{ name }</option>
								) ) }
							</select>
							{ errors.state && <div className="invalid-feedback">{ errors.state }</div> }
						</>
					) : (
						<>
							<input
								type="text"
								className={ fc( 'state' ) }
								value={ billing.state ?? '' }
								onChange={ set( 'state' ) }
							/>
							{ errors.state && <div className="invalid-feedback">{ errors.state }</div> }
						</>
					) }
				</div>

				<div className="form-group col-md-4">
					<label>{ __( 'Country', 'hola-weddings' ) }</label>
					<select
						className={ fc( 'country' ) }
						value={ billing.country ?? '' }
						onChange={ handleCountryChange }
					>
						<optgroup label={ __( 'Most Used', 'hola-weddings' ) }>
							<option value="US">{ __( 'United States', 'hola-weddings' ) }</option>
						</optgroup>
						<optgroup label={ __( 'All Countries', 'hola-weddings' ) }>
							{ COUNTRIES.filter( ( { code } ) => code !== 'US' ).map( ( { name, code } ) => (
								<option key={ code } value={ code }>{ name }</option>
							) ) }
						</optgroup>
					</select>
					{ errors.country && <div className="invalid-feedback">{ errors.country }</div> }
				</div>
			</div>

			{ HAS_CUSTOMER && (
				<label
					htmlFor="updateCustomer"
					className={ `border pay-auth mt-3${ updateCustomer ? ' pay-auth--checked' : '' }` }
				>
					<input
						type="checkbox"
						id="updateCustomer"
						className="sr-only"
						checked={ updateCustomer }
						onChange={ ( e ) => dispatch( { type: 'SET_UPDATE_CUSTOMER', payload: e.target.checked } ) }
					/>
					<span className="pay-auth__box">
						{ updateCustomer && <Check size={ 14 } className="pay-auth__check" /> }
					</span>
					<span className="pay-auth__text">
						{ __( 'Update my information for future bookings.', 'hola-weddings' ) }
					</span>
				</label>
			) }

		</StepCard>

		<div className="mt-3 d-flex justify-content-between">
			<button type="button" className="btn btn-secondary" onClick={ prevStep }>
				<ArrowLeft size={ 18 } />
				{ __( 'Back', 'hola-weddings' ) }
			</button>
			<button type="button" className="btn btn-primary" onClick={ handleNext }>
				{ __( 'Next', 'hola-weddings' ) }
				<ArrowRight size={ 18 } />
			</button>
		</div>
		</div>
	);
}
