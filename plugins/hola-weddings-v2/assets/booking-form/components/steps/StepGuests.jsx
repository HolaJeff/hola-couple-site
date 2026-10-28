import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { useBooking } from '../../hooks/useBooking';
import { ArrowLeft, ArrowRight, ChevronDown, ChevronUp } from 'lucide-react';
import { LOCALE } from '../../utils/locale';

// ── Constants ─────────────────────────────────────────────────────────────────

// Month names in the page's language, from the browser's own locale data.
const MONTH_NAMES = Array.from( { length: 12 }, ( _, i ) =>
	new Date( 2000, i, 1 ).toLocaleDateString( LOCALE, { month: 'long' } )
);

const TITLE_OPTIONS = [ 'mr', 'mrs', 'miss', 'mstr' ];

const TITLE_LABELS = {
	mr:   __( 'Mr.', 'hola-weddings' ),
	mrs:  __( 'Mrs.', 'hola-weddings' ),
	miss: __( 'Miss', 'hola-weddings' ),
	mstr: __( 'Mstr.', 'hola-weddings' ),
};

const TYPE_LABELS = {
	adult:  __( 'Adult', 'hola-weddings' ),
	teen:   __( 'Teen', 'hola-weddings' ),
	child:  __( 'Child', 'hola-weddings' ),
	infant: __( 'Infant', 'hola-weddings' ),
};

// ── Age helpers  (mirrors calc_age + isDateValid in app/index.js) ─────────────

function calcAge( day, month, year, comparisonDate ) {
	const birth = new Date( parseInt( year ), parseInt( month ) - 1, parseInt( day ) );
	let age = comparisonDate.getFullYear() - birth.getFullYear();
	const m = comparisonDate.getMonth() - birth.getMonth();
	if ( m < 0 || ( m === 0 && comparisonDate.getDate() < birth.getDate() ) ) {
		age--;
	}
	return age;
}

function isDateValid( day, month, year ) {
	if ( ! day || ! month || ! year ) return false;
	const d = new Date( parseInt( year ), parseInt( month ) - 1, parseInt( day ) );
	return (
		d.getDate()     === parseInt( day ) &&
		d.getMonth()    === parseInt( month ) - 1 &&
		d.getFullYear() === parseInt( year )
	);
}

function getAge( guest, comparisonDate ) {
	const { day, month, year } = guest;
	if ( ! isDateValid( day, month, year ) ) return null;
	return calcAge( day, month, year, comparisonDate );
}

// Age-only error — used for immediate feedback once all DOB fields are filled
function getAgeError( guest, minAge, maxAge, comparisonDate ) {
	if ( ! isDateValid( guest.day, guest.month, guest.year ) ) return null;
	const age = calcAge( guest.day, guest.month, guest.year, comparisonDate );
	if ( minAge !== null && age < minAge ) {
		return sprintf(
			/* translators: %d: minimum age in years */
			_n( 'Guest must be at least %d year old at time of travel', 'Guest must be at least %d years old at time of travel', minAge, 'hola-weddings' ),
			minAge
		);
	}
	if ( maxAge !== null && age > maxAge ) {
		return sprintf(
			/* translators: %d: maximum age in years */
			_n( 'Guest must be %d year old or younger at time of travel', 'Guest must be %d years old or younger at time of travel', maxAge, 'hola-weddings' ),
			maxAge
		);
	}
	return null;
}

// Mirrors GuestField.isValid() + form-guests.php age constraint logic
function validateGuest( guest, minAge, maxAge, comparisonDate ) {
	if ( ! guest.name?.trim()     ) return __( 'First name is required', 'hola-weddings' );
	if ( ! guest.lastname?.trim() ) return __( 'Last name is required', 'hola-weddings' );
	if ( ! isDateValid( guest.day, guest.month, guest.year ) ) {
		return __( 'Please enter a valid date of birth', 'hola-weddings' );
	}
	const age = calcAge( guest.day, guest.month, guest.year, comparisonDate );
	if ( minAge !== null && age < minAge ) {
		return sprintf(
			/* translators: %d: minimum age in years */
			_n( 'Guest must be at least %d year old at time of travel', 'Guest must be at least %d years old at time of travel', minAge, 'hola-weddings' ),
			minAge
		);
	}
	if ( maxAge !== null && age > maxAge ) {
		return sprintf(
			/* translators: %d: maximum age in years */
			_n( 'Guest must be %d year old or younger at time of travel', 'Guest must be %d years old or younger at time of travel', maxAge, 'hola-weddings' ),
			maxAge
		);
	}
	return null;
}

const _isTeenEnabled = Boolean( window.hwBooking?.isTeenEnabled );

// Build the ordered flat guest list: adults → teens → children → infants
// Mirrors the PHP $j iterator in form-guests.php
function buildGuestList( search, guests ) {
	const groups = [
		{ type: 'adult',  count: parseInt( search.adults   ?? 0 ) },
		...( _isTeenEnabled ? [ { type: 'teen', count: parseInt( search.teens ?? 0 ) } ] : [] ),
		{ type: 'child',  count: parseInt( search.children ?? 0 ) },
		{ type: 'infant', count: parseInt( search.infants  ?? 0 ) },
	];
	const list = [];
	groups.forEach( ( { type, count } ) => {
		for ( let i = 0; i < count; i++ ) {
			const idx = list.length;
			list.push( {
				personType: type,
				title:      'mr',
				name:       '',
				lastname:   '',
				day:        '',
				month:      '1', // matches the first visible option so state is never out of sync
				year:       '',
				...( guests[ idx ] ?? {} ),
				personType: type, // always enforce correct type after spread
			} );
		}
	} );
	return list;
}

// ── GuestCard ─────────────────────────────────────────────────────────────────

function GuestCard( { guest, globalIdx, typeIdx, minAge, maxAge, showError, comparisonDate, onChange } ) {
	const { personType, title, name, lastname, day, month, year } = guest;
	const [ open, setOpen ] = useState( true );

	const age          = getAge( guest, comparisonDate );
	const ageOk        = age !== null && ( minAge === null || age >= minAge ) && ( maxAge === null || age <= maxAge );
	const ageWarn      = age !== null && ! ageOk;
	const error        = showError ? validateGuest( guest, minAge, maxAge, comparisonDate ) : null;
	const allDobFilled = Boolean( day && year ); // month select always has a value
	const liveAgeError = allDobFilled ? getAgeError( guest, minAge, maxAge, comparisonDate ) : null;

	// Year selector range based on age constraints
	const compYear = comparisonDate.getFullYear();
	const maxYear  = minAge !== null ? compYear - minAge       : compYear;
	const minYear  = maxAge !== null ? compYear - maxAge - 1   : compYear - 120;
	const years    = Array.from( { length: maxYear - minYear + 1 }, ( _, i ) => maxYear - i );

	// Age range label shown next to guest header
	const ageRangeLabel = minAge !== null && maxAge !== null
		? sprintf( /* translators: 1: minimum age. 2: maximum age */ __( '%1$d–%2$d yrs', 'hola-weddings' ), minAge, maxAge )
		: minAge !== null
		? sprintf( /* translators: %d: minimum age */ __( 'min. %d yrs', 'hola-weddings' ), minAge )
		: maxAge !== null
		? sprintf( /* translators: %d: maximum age */ __( 'max. %d yrs', 'hola-weddings' ), maxAge )
		: null;

	// Live heading: "Adult #1" → "Adult: John" as soon as first name is typed
	const headingName = name.trim()
		? sprintf( /* translators: 1: guest type, e.g. Adult. 2: guest's full name */ __( '%1$s: %2$s', 'hola-weddings' ), TYPE_LABELS[ personType ], `${ name.trim() }${ lastname.trim() ? ' ' + lastname.trim() : '' }` )
		: sprintf( /* translators: 1: guest type, e.g. Adult. 2: guest number within that type */ __( '%1$s #%2$d', 'hola-weddings' ), TYPE_LABELS[ personType ], typeIdx );

	function f( field, value ) {
		onChange( { ...guest, [ field ]: value } );
	}

	const dobInvalid = liveAgeError || ( showError && ageWarn );
	const dateClass  = ( extra = '' ) => [
		'form-control',
		dobInvalid ? 'is-invalid' : '',
		age !== null && ageOk ? 'is-valid' : '',
		extra,
	].filter( Boolean ).join( ' ' );

	return (
		<div className={ `guest-card mb-3${ error ? ' guest-card--invalid' : '' }${ ! open ? ' guest-card--collapsed' : '' }` }>

			{ /* ── Header ── */ }
			<div
				className="guest-card__header guest-card__header--toggle"
				onClick={ () => setOpen( ( v ) => ! v ) }
				role="button"
				aria-expanded={ open }
			>
				<div className="guest-card__heading">
					<span className="guest-card__num">{ globalIdx + 1 }</span>
					{ headingName }
					{ ageRangeLabel && ! name.trim() && (
						<small className="guest-card__age-range">{ ageRangeLabel }</small>
					) }
					{ age !== null && (
						<span className={ `guest-card__age-pill ml-3${ ageOk ? ' guest-card__age-pill--ok' : ' guest-card__age-pill--warn' }` }>
							{ sprintf(
								/* translators: %d: guest's age in years */
								_n( '%d yr', '%d yrs', age, 'hola-weddings' ),
								age
							) }
						</span>
					) }
				</div>
				<div className="guest-card__header-right">
					{ open
						? <ChevronUp   size={ 14 } className="guest-card__chevron" />
						: <ChevronDown size={ 14 } className="guest-card__chevron" />
					}
				</div>
			</div>

			{ error && <div className="guest-card__error">{ error }</div> }

			{ open && (
				<>


			{ /* ── Title ── */ }
			<div className="guest-card__body">
				<small className="guest-card__label">{ __( 'Title', 'hola-weddings' ) }</small>
				<div className="guest-title-group">
					{ TITLE_OPTIONS.map( ( t ) => (
						<label key={ t } className={ `guest-title-btn${ title === t ? ' guest-title-btn--active' : '' }` }>
							<input
								type="radio"
								name={ `guest-${ globalIdx }-title` }
								value={ t }
								checked={ title === t }
								onChange={ () => f( 'title', t ) }
								className="sr-only"
							/>
							{ TITLE_LABELS[ t ] }
						</label>
					) ) }
				</div>
			</div>

			{ /* ── Name ── */ }
			<div className="guest-card__body">
				<div className="form-row">
					<div className="form-group col-md-6 mb-1">
						<small className="guest-card__label">{ __( 'First Name', 'hola-weddings' ) }</small>
						<input
							type="text"
							className={ `form-control${ showError && ! name.trim() ? ' is-invalid' : '' }` }
							placeholder={ __( 'First Name', 'hola-weddings' ) }
							value={ name }
							onChange={ ( e ) => f( 'name', e.target.value ) }
						/>
					</div>
					<div className="form-group col-md-6 mb-1">
						<small className="guest-card__label">{ __( 'Last Name', 'hola-weddings' ) }</small>
						<input
							type="text"
							className={ `form-control${ showError && ! lastname.trim() ? ' is-invalid' : '' }` }
							placeholder={ __( 'Last Name', 'hola-weddings' ) }
							value={ lastname }
							onChange={ ( e ) => f( 'lastname', e.target.value ) }
						/>
					</div>
				</div>
				<small className="text-muted guest-card__hint">
					{ __( 'Please enter your name as it appears on the passport.', 'hola-weddings' ) }
				</small>
			</div>

			{ /* ── Date of birth + live age ── */ }
			<div className="guest-card__body">
				<small className="guest-card__label">{ __( 'Date of Birth', 'hola-weddings' ) }</small>
				<div className="guest-dob-row">
					<div className="guest-dob-field">
						<input
							type="number"
							className={ `form-control dob-number${ dobInvalid ? ' is-invalid' : '' }${ age !== null && ageOk ? ' is-valid' : '' }` }
							placeholder={ __( 'DD', 'hola-weddings' ) }
							min="1"
							max="31"
							value={ day }
							onChange={ ( e ) => f( 'day', e.target.value ) }
						/>
					</div>
					<div className="guest-dob-field guest-dob-field--month">
						<select className={ dateClass() } value={ month } onChange={ ( e ) => f( 'month', e.target.value ) }>
							{ MONTH_NAMES.map( ( m, i ) => (
								<option key={ i + 1 } value={ i + 1 }>{ m }</option>
							) ) }
						</select>
					</div>
					<div className="guest-dob-field guest-dob-field--year">
						<input
							type="number"
							className={ `form-control dob-number${ dobInvalid ? ' is-invalid' : '' }${ age !== null && ageOk ? ' is-valid' : '' }` }
							placeholder={ __( 'YYYY', 'hola-weddings' ) }
							min={ minYear }
							max={ maxYear }
							value={ year }
							onChange={ ( e ) => f( 'year', e.target.value ) }
						/>
					</div>
				</div>
				{ liveAgeError && (
					<div className="invalid-feedback d-block mt-1">{ liveAgeError }</div>
				) }
			</div>
			</>
			) }
		</div>
	);
}

// ── StepGuests ────────────────────────────────────────────────────────────────

export default function StepGuests() {
	const { search, guests, dispatch, nextStep, prevStep } = useBooking();
	const [ showErrors, setShowErrors ] = useState( false );

	// Age constraints from PHP via hwBooking.personTypes
	const personTypes = window.hwBooking?.personTypes ?? {};

	function getConstraints( type, globalIdx ) {
		const cfg    = personTypes[ type ] ?? {};
		const minAge = parseInt( cfg.min ?? 0 ) || 0;
		const maxAge = cfg.max != null ? parseInt( cfg.max ) : null;
		// First guest rule: mirrors form-guests.php `if($j == 1){ $min_age = 18; }`
		return {
			minAge: globalIdx === 0 ? Math.max( minAge, 18 ) : minAge,
			maxAge,
		};
	}

	// Comparison date = checkout date (mirrors GuestField constructor in cart.js)
	const comparisonDate = useMemo( () => {
		if ( search.checkout ) {
			const [ y, m, d ] = search.checkout.split( '-' ).map( Number );
			return new Date( y, m - 1, d );
		}
		return new Date();
	}, [ search.checkout ] );

	const guestList = useMemo(
		() => buildGuestList( search, guests ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ search.adults, search.teens, search.children, search.infants, guests ],
	);

	function updateGuest( idx, updated ) {
		const next = guestList.map( ( g, i ) => i === idx ? updated : g );
		dispatch( { type: 'SET_GUESTS', payload: next } );
	}

	// Track type index per guest (Adult #1, Adult #2, Teen #1, …)
	const typeCounters = {};
	const guestsMeta = guestList.map( ( guest, idx ) => {
		const t = guest.personType;
		typeCounters[ t ] = ( typeCounters[ t ] ?? 0 ) + 1;
		return { globalIdx: idx, typeIdx: typeCounters[ t ], ...getConstraints( t, idx ) };
	} );

	function handleNext() {
		const allValid = guestsMeta.every( ( { globalIdx, minAge, maxAge } ) =>
			validateGuest( guestList[ globalIdx ], minAge, maxAge, comparisonDate ) === null,
		);
		if ( ! allValid ) {
			setShowErrors( true );
			return;
		}
		nextStep();
	}

	return (
		<div>
			{ guestList.map( ( guest, idx ) => {
				const { globalIdx, typeIdx, minAge, maxAge } = guestsMeta[ idx ];
				return (
					<GuestCard
						key={ globalIdx }
						guest={ guest }
						globalIdx={ globalIdx }
						typeIdx={ typeIdx }
						minAge={ minAge }
						maxAge={ maxAge }
						showError={ showErrors }
						comparisonDate={ comparisonDate }
						onChange={ ( updated ) => updateGuest( globalIdx, updated ) }
					/>
				);
			} ) }

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
