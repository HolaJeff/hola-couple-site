import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { useBooking }                  from '../hooks/useBooking';
import { useBodyScrollLock }           from '../hooks/useBodyScrollLock';
import HotelModal                      from './steps/HotelModal';
import { CalendarDays, Heart, Info, MapPin, Search, UsersRound, X } from 'lucide-react';
import { LOCALE }                      from '../utils/locale';

// ── Locale objects (mirrors cart.js datepickerEnLocale / datepickerEsLocale) ──
const EN_LOCALE = {
	days:        [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ],
	daysShort:   [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ],
	daysMin:     [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ],
	months:      [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ],
	monthsShort: [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ],
	today:       'Today',
	clear:       'Clear',
	dateFormat:  'MM/dd/yyyy',
	firstDay:    0,
};

const ES_LOCALE = {
	days:        [ 'Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado' ],
	daysShort:   [ 'Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab' ],
	daysMin:     [ 'Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa' ],
	months:      [ 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' ],
	monthsShort: [ 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic' ],
	today:       'Hoy',
	clear:       'Limpiar',
	dateFormat:  'dd/MM/yyyy',
	firstDay:    1,
};

// ── Helpers (mirrors ymdToLocalDate / addDays / removeDays in cart.js) ────────
function ymdToLocalDate( ymd ) {
	const [ y, m, d ] = ymd.split( '-' );
	return new Date( Number( y ), Number( m ) - 1, Number( d ) );
}

function addDays( date, days ) {
	const d = new Date( date );
	d.setDate( d.getDate() + days );
	return d;
}

// ── Guest helpers ─────────────────────────────────────────────────────────────
const _isTeenEnabled  = Boolean( window.hwBooking?.isTeenEnabled );
const _isAdultsOnly   = Boolean( window.hwBooking?.isAdultsOnly );

// Age constraints from PHP via hwBooking.personTypes (mirrors StepGuests.jsx)
const _personTypes = window.hwBooking?.personTypes ?? {};

// Human-readable age range for a person type, built from the hotel's configured
// brackets: "18+ years" (no max), "2–12 years" (min + max), "Under 2" (min 0).
function ageDesc( type, fallback ) {
	const cfg = _personTypes[ type ];
	if ( ! cfg ) return fallback;

	const min = parseInt( cfg.min ?? 0 ) || 0;
	const max = cfg.max != null && cfg.max !== '' ? parseInt( cfg.max ) : NaN;

	/* translators: %d: minimum age */
	if ( Number.isNaN( max ) ) return sprintf( __( '%d+ years', 'hola-weddings' ), min );
	/* translators: %d: age the guest must be under */
	if ( min === 0 )           return sprintf( __( 'Under %d', 'hola-weddings' ), max + 1 );
	/* translators: 1: minimum age. 2: maximum age */
	return sprintf( __( '%1$d–%2$d years', 'hola-weddings' ), min, max );
}

const GUEST_TYPES = _isAdultsOnly
	? [ { field: 'adults', label: __( 'Adults', 'hola-weddings' ), desc: ageDesc( 'adult', __( '18+ years', 'hola-weddings' ) ), min: 1 } ]
	: [
		{ field: 'adults',   label: __( 'Adults', 'hola-weddings' ),   desc: ageDesc( 'adult',  __( '18+ years', 'hola-weddings' )   ), min: 1 },
		...( _isTeenEnabled ? [ { field: 'teens', label: __( 'Teens', 'hola-weddings' ), desc: ageDesc( 'teen', __( '13–17 years', 'hola-weddings' ) ), min: 0 } ] : [] ),
		{ field: 'children', label: __( 'Children', 'hola-weddings' ), desc: ageDesc( 'child',  __( '2–12 years', 'hola-weddings' )  ), min: 0 },
		{ field: 'infants',  label: __( 'Infants', 'hola-weddings' ),  desc: ageDesc( 'infant', __( 'Under 2', 'hola-weddings' )     ), min: 0 },
	];

function formatDate( dateStr ) {
	if ( ! dateStr ) return null;
	const [ y, m, d ] = dateStr.split( '-' );
	return new Date( y, m - 1, d ).toLocaleDateString( LOCALE, {
		month: 'short', day: 'numeric', year: 'numeric',
	} );
}

function guestSummary( search ) {
	const parts = [];
	/* translators: %d: number of adults */
	if ( search.adults   > 0 ) parts.push( sprintf( _n( '%d Adult', '%d Adults', search.adults, 'hola-weddings' ), search.adults ) );
	/* translators: %d: number of teens */
	if ( search.teens    > 0 ) parts.push( sprintf( _n( '%d Teen', '%d Teens', search.teens, 'hola-weddings' ), search.teens ) );
	/* translators: %d: number of children */
	if ( search.children > 0 ) parts.push( sprintf( _n( '%d Child', '%d Children', search.children, 'hola-weddings' ), search.children ) );
	/* translators: %d: number of infants */
	if ( search.infants  > 0 ) parts.push( sprintf( _n( '%d Infant', '%d Infants', search.infants, 'hola-weddings' ), search.infants ) );
	return parts.join( ', ' ) || __( 'Add guests', 'hola-weddings' );
}

function toYmd( date ) {
	const y = date.getFullYear();
	const m = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const d = String( date.getDate() ).padStart( 2, '0' );
	return `${ y }-${ m }-${ d }`;
}

const HOTEL_NAME    = window.hwBooking?.hotelName    ?? __( 'Hotel', 'hola-weddings' );
const HOTEL_DATA    = window.hwBooking?.hotelData    ?? null;
const WEDDING_DATE  = window.hwBooking?.weddingDate  ?? null;
const MIN_NIGHT     = parseInt( window.hwBooking?.minNights ?? window.hwBooking?.min_nights ?? 1 );

// ── Component ─────────────────────────────────────────────────────────────────
export default function SearchBox() {
	const { search, dispatch } = useBooking();
	const [ guestsOpen,      setGuestsOpen      ] = useState( false );
	const [ minNightsViolation, setMinNightsViolation ] = useState( false );
	const [ submitAttempted, setSubmitAttempted ] = useState( false );
	const [ mobileOpen,      setMobileOpen      ] = useState( false );
	const [ hotelOpen,       setHotelOpen       ] = useState( false );

	// Local draft — buffers date/guest changes until Search is clicked
	const [ draft, setDraft ] = useState( () => ( {
		checkin:  search.checkin  ?? null,
		checkout: search.checkout ?? null,
		adults:   search.adults   ?? 2,
		teens:    search.teens    ?? 0,
		children: search.children ?? 0,
		infants:  search.infants  ?? 0,
	} ) );

	useBodyScrollLock( mobileOpen );

	// Derived per-field validity — reactive: clears automatically when fixed
	const checkinInvalid  = submitAttempted && ! draft.checkin;
	const checkoutInvalid = submitAttempted && ! draft.checkout;
	const guestsInvalid   = submitAttempted && ( draft.adults || 0 ) < 1;

	const dpRef        = useRef( null );  // AirDatepicker range instance
	const dateAnchorRef = useRef( null ); // hidden input the picker anchors to
	const guestsRef     = useRef( null );

	// ── Datepicker init ───────────────────────────────────────────────────────
	useEffect( () => {
		if ( ! window.AirDatepicker || ! dateAnchorRef.current ) return;

		const hw            = window.hw         ?? {};
		const hwBookingData = window.hwBooking   ?? {};
		const minNight      = parseInt( hwBookingData.minNights ?? hwBookingData.min_nights ?? 1 );
		const locale        = LOCALE.startsWith( 'es' ) ? ES_LOCALE : EN_LOCALE;

		const startDate = hw.start_date ? ymdToLocalDate( hw.start_date ) : new Date();
		const endDate   = hw.end_date   ? ymdToLocalDate( hw.end_date )   : null;
		// Latest valid check-in: endDate − minNight (so a minimum stay can fit before the end)
		const ciEndDate = endDate ? addDays( endDate, -minNight ) : null;

		const dp = new window.AirDatepicker( dateAnchorRef.current, {
			range:         true,
			selectedDates: [
				...( search.checkin  ? [ ymdToLocalDate( search.checkin  ) ] : [] ),
				...( search.checkout ? [ ymdToLocalDate( search.checkout ) ] : [] ),
			],
			locale,
			minDate:        startDate,
			...( endDate && { maxDate: endDate } ),
			autoClose:      true,
			toggleSelected: false,
			offset:         4,

			// Disable dates the user must not select, dynamically based on pick phase.
			// Reads dp.selectedDates directly — AirDatepicker updates this array before
			// calling onRenderCell, so it is always in sync without any extra variable.
			//  • not picking checkout → block check-in dates too late for a valid checkout
			//  • 1 date selected      → block dates before check-in and within minNight of it
			onRenderCell: ( { date, cellType } ) => {
				if ( cellType !== 'day' ) return {};
				const selected = dp?.selectedDates ?? [];
				if ( selected.length === 1 ) {
					if ( date < selected[ 0 ] ) {
						return { disabled: true };
					}
					const minCheckout = addDays( selected[ 0 ], minNight );
					if ( date > selected[ 0 ] && date < minCheckout ) {
						return { disabled: true, classes: '-dp-min-nights-' };
					}
				} else if ( ciEndDate && date > ciEndDate ) {
					return { disabled: true };
				}
				return {};
			},

			onSelect: ( { date: dates } ) => {
				if ( ! dates || dates.length === 0 ) {
					setDraft( ( d ) => ( { ...d, checkin: null, checkout: null } ) );
				} else if ( dates.length === 1 ) {
					setDraft( ( d ) => ( { ...d, checkin: toYmd( dates[ 0 ] ), checkout: null } ) );
					setMinNightsViolation( false );
					// onRenderCell is not re-invoked automatically after a date is selected in
					// range mode; update({}) forces a full cell re-render so the min-nights
					// blocking takes effect. Deferred so AirDatepicker finishes its own
					// internal range-state update before we trigger the re-render.
					setTimeout( () => dp.update( {} ), 0 );
				} else {
					setDraft( ( d ) => ( { ...d, checkin: toYmd( dates[ 0 ] ), checkout: toYmd( dates[ 1 ] ) } ) );
					setMinNightsViolation( false );
				}
			},
		} );

		dpRef.current = dp;

		// pointerdown covers both desktop and iOS Safari (which skips mousedown on non-interactive elements)
		const onOutsidePointer = ( e ) => {
			if ( dp.$datepicker.classList.contains( '-active-' ) && ! dp.$datepicker.contains( e.target ) ) {
				dp.hide();
			}
		};
		document.addEventListener( 'pointerdown', onOutsidePointer );

		return () => {
			dp.destroy();
			document.removeEventListener( 'pointerdown', onOutsidePointer );
		};
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// ── Close guests dropdown on outside click ────────────────────────────────
	useEffect( () => {
		const handler = ( e ) => {
			if ( guestsRef.current && ! guestsRef.current.contains( e.target ) ) {
				setGuestsOpen( false );
			}
		};
		document.addEventListener( 'mousedown', handler );
		return () => document.removeEventListener( 'mousedown', handler );
	}, [] );

	function adjustGuest( field, delta ) {
		const min    = field === 'adults' ? 1 : 0;
		const newVal = Math.max( min, ( draft[ field ] || 0 ) + delta );
		setDraft( ( d ) => ( { ...d, [ field ]: newVal } ) );
	}

	function handleSubmit( e ) {
		e.preventDefault();
		setGuestsOpen( false );

		if ( ! draft.checkin || ! draft.checkout || ( draft.adults || 0 ) < 1 ) {
			setSubmitAttempted( true );
			return;
		}

		if ( MIN_NIGHT > 1 ) {
			const nights = ( ymdToLocalDate( draft.checkout ) - ymdToLocalDate( draft.checkin ) ) / ( 1000 * 3600 * 24 );
			if ( nights < MIN_NIGHT ) {
				setMinNightsViolation( true );
				return;
			}
		}

		// Reflect search params in the URL without adding a history entry
		const params = new URLSearchParams( window.location.search );
		params.set( 'checkin',  draft.checkin );
		params.set( 'checkout', draft.checkout );
		params.set( 'adults',   draft.adults   ?? 1 );
		[ 'teens', 'children', 'infants' ].forEach( ( key ) => {
			const val = draft[ key ] ?? 0;
			if ( val > 0 ) {
				params.set( key, val );
			} else {
				params.delete( key );
			}
		} );
		history.replaceState( null, '', `${ window.location.pathname }?${ params }` );

		setSubmitAttempted( false );
		setMobileOpen( false );
		dispatch( { type: 'SET_SEARCH', payload: draft } );
		dispatch( { type: 'RESET_BOOKING' } );
	}

	const triggerDates = draft.checkin && draft.checkout
		? `${ formatDate( draft.checkin ) } → ${ formatDate( draft.checkout ) }`
		: __( 'Select dates', 'hola-weddings' );

	// On mobile: show the inline form until the user has searched; after that show the compact trigger
	const hasSearched = !! ( search.checkin && search.checkout );

	const formClass = `sb-wrap${
		! hasSearched ? ' sb-wrap--inline' :
		mobileOpen    ? ' sb-wrap--open'   : ''
	}`;

	// Hotel block — rendered both above the mobile trigger and inside the bar.
	// The SCSS hides/shows the right one depending on viewport.
	const hotelBlock = (
		<div
			className="sb-hotel-block"
			onClick={ () => HOTEL_DATA && setHotelOpen( true ) }
			role={ HOTEL_DATA ? 'button' : undefined }
			tabIndex={ HOTEL_DATA ? 0 : undefined }
			onKeyDown={ HOTEL_DATA ? ( e ) => e.key === 'Enter' && setHotelOpen( true ) : undefined }
		>
			{ HOTEL_DATA?.mainImage && (
				<img src={ HOTEL_DATA.mainImage } alt={ HOTEL_NAME } className="sb-hotel-thumb" />
			) }
			<div className="sb-hotel-info">
				<span className="sb-value">
					<MapPin width={16} className='mr-2'/>
					{ HOTEL_NAME }
				</span>
				{ WEDDING_DATE && (
					<span className="sb-hotel-wedding-date">
						<Heart size={16} fill='#d90166' className='mr-2' />
						{ formatDate( WEDDING_DATE ) }
					</span>
				) }
			</div>
			{ HOTEL_DATA && (
				<div className="ml-auto d-flex align-items-center">
					<Info size={16} />
				</div>
			) }
		</div>
	);

	return (
		<>
		{ /* ── Mobile-only hotel card (above the trigger / inline form) ── */ }
		<div className="sb-hotel-mobile">
			{ hotelBlock }
		</div>

		{ /* ── Mobile trigger (shown only after the user has already searched) ── */ }
		{ hasSearched && (
			<div className="sb-mobile-trigger" onClick={ () => setMobileOpen( true ) }>
				<div className="sb-trigger__icon">
					<Search />
				</div>
				<div className="sb-trigger__info">
					<div className="sb-trigger__dates">{ triggerDates }</div>
					<div className="sb-trigger__sub">{ HOTEL_NAME } · { guestSummary( draft ) }</div>
				</div>
			</div>
		) }

		{ hotelOpen && <HotelModal onClose={ () => setHotelOpen( false ) } /> }

		{ /* ── Backdrop (only when overlay is open) ── */ }
		{ hasSearched && mobileOpen && (
			<div className="sb-mobile-backdrop" onClick={ () => setMobileOpen( false ) } />
		) }

		<form className={ formClass } onSubmit={ handleSubmit }>
			{ /* ── Mobile form header (close button — only when form is used as overlay) ── */ }
			{ hasSearched && mobileOpen && (
			<div className="sb-mobile-header">
				<button type="button" className="sb-mobile-header__close text-white" onClick={ () => setMobileOpen( false ) } aria-label={ __( 'Close', 'hola-weddings' ) }>
					<X />
				</button>
			</div>
			) }

			<div className="sb-bar">

				{ /* ── Hotel (desktop only — hidden on mobile via CSS) ── */ }
				<div className="sb-field sb-field--hotel">
					{ hotelBlock }
				</div>

				{ /* ── Dates (unified range picker) ── */ }
				<div
					className={ `sb-field sb-field--dates${ ( checkinInvalid || checkoutInvalid || minNightsViolation ) ? ' sb-field--invalid' : '' }` }
					onClick={ () => dpRef.current?.show() }
					role="button"
					tabIndex={ 0 }
					onKeyDown={ ( e ) => e.key === 'Enter' && dpRef.current?.show() }
				>
					{ /* Hidden anchor — AirDatepicker positions its popup relative to this */ }
					<span className="sb-label">
						<CalendarDays size={14} className='mr-1' />
						{ __( 'Travel dates', 'hola-weddings' ) }
					</span>
					<input ref={ dateAnchorRef } type="text" className="sb-anchor" readOnly />
					<div className="sb-date-range">
						<div className="sb-date-range__part">
							
							<span className={ `sb-value${ ! draft.checkin ? ' sb-value--empty' : '' }` }>
								{ draft.checkin ? formatDate( draft.checkin ) : __( 'Check-in', 'hola-weddings' ) }
							</span>
						</div>
						<span className="sb-date-range__sep">→</span>
						<div className="sb-date-range__part">
							<span className={ `sb-value${ ! draft.checkout ? ' sb-value--empty' : '' }` }>
								{ draft.checkout ? formatDate( draft.checkout ) : __( 'Check-out', 'hola-weddings' ) }
							</span>
						</div>
					</div>
					{ MIN_NIGHT > 1 && (
						<span className={ `sb-date-range__disclaimer${ minNightsViolation ? ' sb-date-range__disclaimer--error' : '' }` }>
							<Info size={14} className='mr-2' />
							{ sprintf(
								/* translators: %d: minimum number of nights */
								_n( 'A minimum of %d night is required.', 'A minimum of %d nights is required.', MIN_NIGHT, 'hola-weddings' ),
								MIN_NIGHT
							) }
						</span>
					) }
				</div>

				{ /* ── Guests ── */ }
				<div ref={ guestsRef } className={ `sb-field sb-field--guests${ guestsInvalid ? ' sb-field--invalid' : '' }` }>
					<div
						className="sb-guests-trigger"
						onClick={ () => setGuestsOpen( ( v ) => ! v ) }
						role="button"
						tabIndex={ 0 }
						onKeyDown={ ( e ) => e.key === 'Enter' && setGuestsOpen( ( v ) => ! v ) }
					>
						<span className="sb-label">
							<UsersRound size={14} className='mr-1' />
							{ __( 'Guests', 'hola-weddings' ) }
						</span>
						<span className="sb-value">{ guestSummary( draft ) }</span>
					</div>

					{ guestsOpen && (
						<div className="sb-guests-dropdown">
							{ GUEST_TYPES.map( ( { field, label, desc, min } ) => (
								<div key={ field } className="sb-guest-row">
									<div>
										<div className="sb-guest-label">{ label }</div>
										<div className="sb-guest-desc">{ desc }</div>
									</div>
									<div className="sb-guest-counter">
										<button
											type="button"
											className="sb-counter-btn"
											onClick={ () => adjustGuest( field, -1 ) }
											disabled={ ( draft[ field ] || 0 ) <= min }
										>−</button>
										<span className="sb-counter-val">{ draft[ field ] || 0 }</span>
										<button
											type="button"
											className="sb-counter-btn"
											onClick={ () => adjustGuest( field, +1 ) }
										>+</button>
									</div>
								</div>
							) ) }
						</div>
					) }
				</div>

				{ /* ── Search button ── */ }
				<div className="sb-action">
					<button type="submit" className="sb-search-btn" title={ __( 'Search', 'hola-weddings' ) } aria-label={ __( 'Search', 'hola-weddings' ) }>
						<Search />
					</button>
				</div>

			</div>

			</form>
		</>
	);
}
