import { __, _n, sprintf } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import { useCart }    from '../../hooks/useCart';
import { useBooking } from '../../store/bookingContext';
import { StepCard }   from '../StepCard';
import { ChevronDown, ChevronUp, Info, MapPin, ShieldCheck, UsersRound, Van } from 'lucide-react';
import { LOCALE }     from '../../utils/locale';

const CURRENCY         = window.hwBooking?.currency             ?? 'USD';
const HOTEL_NAME       = window.hwBooking?.hotelName            ?? '';
const INSURANCE_PLANS  = window.hwBooking?.insuranceData?.plans ?? [];
const TITLE_LABELS     = {
	mr:   __( 'Mr.', 'hola-weddings' ),
	mrs:  __( 'Mrs.', 'hola-weddings' ),
	miss: __( 'Miss', 'hola-weddings' ),
	mstr: __( 'Mstr.', 'hola-weddings' ),
};
const TYPE_LABELS      = {
	adult:  __( 'Adult', 'hola-weddings' ),
	teen:   __( 'Teen', 'hola-weddings' ),
	child:  __( 'Child', 'hola-weddings' ),
	infant: __( 'Infant', 'hola-weddings' ),
};

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

function formatYmd( y, m, d ) {
	return new Date( parseInt( y ), parseInt( m ) - 1, parseInt( d ) )
		.toLocaleDateString( LOCALE, { month: 'short', day: 'numeric', year: 'numeric' } );
}

function formatDate( dateStr ) {
	if ( ! dateStr ) return '—';
	const [ y, m, d ] = dateStr.split( '-' );
	return formatYmd( y, m, d );
}

function formatDob( day, month, year ) {
	if ( ! day || ! month || ! year ) return '—';
	return formatYmd( year, month, day );
}

function calcAge( day, month, year, comp ) {
	const birth = new Date( parseInt( year ), parseInt( month ) - 1, parseInt( day ) );
	let age = comp.getFullYear() - birth.getFullYear();
	const m = comp.getMonth() - birth.getMonth();
	if ( m < 0 || ( m === 0 && comp.getDate() < birth.getDate() ) ) age--;
	return age;
}

function getGuestAge( guest, comp ) {
	const { day, month, year } = guest;
	if ( ! day || ! month || ! year ) return null;
	const d = new Date( parseInt( year ), parseInt( month ) - 1, parseInt( day ) );
	if (
		d.getDate()     !== parseInt( day   ) ||
		d.getMonth()    !== parseInt( month ) - 1 ||
		d.getFullYear() !== parseInt( year  )
	) return null;
	return calcAge( day, month, year, comp );
}

function buildGuestSummary( guests ) {
	const counts = {};
	guests.forEach( ( g ) => { counts[ g.personType ] = ( counts[ g.personType ] ?? 0 ) + 1; } );
	const n = ( t ) => counts[ t ] ?? 0;
	return [
		/* translators: %d: number of adults */
		n( 'adult' )  > 0 && sprintf( _n( '%d Adult', '%d Adults', n( 'adult' ), 'hola-weddings' ), n( 'adult' ) ),
		/* translators: %d: number of teens */
		n( 'teen' )   > 0 && sprintf( _n( '%d Teen', '%d Teens', n( 'teen' ), 'hola-weddings' ), n( 'teen' ) ),
		/* translators: %d: number of children */
		n( 'child' )  > 0 && sprintf( _n( '%d Child', '%d Children', n( 'child' ), 'hola-weddings' ), n( 'child' ) ),
		/* translators: %d: number of infants */
		n( 'infant' ) > 0 && sprintf( _n( '%d Infant', '%d Infants', n( 'infant' ), 'hola-weddings' ), n( 'infant' ) ),
	].filter( Boolean ).join( ', ' );
}

export default function CartSidebar() {
	const { selectedRoom, selectedPackage, search, insurance, step, guests } = useBooking();
	const { roomTotal, transferTotal, insuranceTotal, grandTotal, deposit, isRoomAvailable } = useCart();

	const [ guestsOpen, setGuestsOpen ] = useState( false );

	const product       = selectedRoom ?? selectedPackage;
	const insurancePlan = insurance?.planId && insurance.planId !== 0
		? INSURANCE_PLANS.find( ( p ) => String( p.id ) === String( insurance.planId ) )
		: null;

	const compDate = useMemo( () => {
		if ( search.checkout ) {
			const [ y, m, d ] = search.checkout.split( '-' ).map( Number );
			return new Date( y, m - 1, d );
		}
		return new Date();
	}, [ search.checkout ] );

	const showGuests = step > 1 && guests.length > 0;

	return (
		<StepCard className="cart-card">
			<div className="cs-title">{ __( 'Reservation Summary', 'hola-weddings' ) }</div>

			{ ! product ? (
				<p className="cs-empty">{ __( 'Select a room to continue.', 'hola-weddings' ) }</p>
			) : (
				<>
					{ /* ── Room section ── */ }
					<div className="cs-room">
						{ product.image && (
							<div
								className="cs-room__image"
								style={ { backgroundImage: `url(${ product.image })` } }
							/>
						) }
						<div className="cs-room__body">
							<div className="cs-room__name">{ product.name }</div>
							{ HOTEL_NAME && (
								<div className="cs-room__hotel">
									<MapPin size={ 12 } className="cs-meta-icon" />
									{ HOTEL_NAME }
								</div>
							) }
							<div className="cs-room__dates">
								{ formatDate( search.checkin ) } → { formatDate( search.checkout ) }
							</div>

							{ ! isRoomAvailable && (
								<div className="cs-confirm-notice">
									<span className="cs-confirm-notice__text">{ __( 'Room requires confirmation', 'hola-weddings' ) }</span>
									<span className="cs-confirm-notice__tip">
										<Info size={ 14 } />
										<span className="cs-confirm-notice__tooltip">
											{ __( 'This room is subject to availability. An agent will contact you to confirm your reservation before any payment is collected.', 'hola-weddings' ) }
										</span>
									</span>
								</div>
							) }

							<div className="cs-room__total">{ formatPrice( roomTotal ) }</div>
						</div>
					</div>

					{ /* ── Guests section ── */ }
					{ showGuests && (
						<>
							<div className="cs-divider" />
							<div className="cs-section">
								<div
									className="cs-guests__header"
									onClick={ () => setGuestsOpen( ( v ) => ! v ) }
									role="button"
									aria-expanded={ guestsOpen }
								>
									<span className="cs-guests__label">
										<UsersRound size={ 12 } className="cs-section-icon" />
										{ __( 'Guests', 'hola-weddings' ) }
									</span>
									<span className="cs-guests__meta">
										<span className="cs-guests__count">{ buildGuestSummary( guests ) }</span>
										{ guestsOpen
											? <ChevronUp   size={ 14 } className="cs-guests__chevron" />
											: <ChevronDown size={ 14 } className="cs-guests__chevron" />
										}
									</span>
								</div>
								{ guestsOpen && (
									<div className="cs-guests__body">
										{ guests.map( ( guest, idx ) => {
											const age       = getGuestAge( guest, compDate );
											const dob       = formatDob( guest.day, guest.month, guest.year );
											const tl        = TITLE_LABELS[ guest.title ] ?? guest.title;
											const typeLabel = TYPE_LABELS[ guest.personType ] ?? guest.personType;
											const name      = `${ tl } ${ guest.name } ${ guest.lastname }`.trim();
											return (
												<div key={ idx } className="cs-guest-item">
													<div className="cs-guest-item__name">{ name }</div>
													<div className="cs-guest-item__meta">
														{ typeLabel } · { dob }{ age !== null
														/* translators: %d: guest's age in years */
														? ' · ' + sprintf( _n( '%d yr', '%d yrs', age, 'hola-weddings' ), age )
														: '' }
													</div>
												</div>
											);
										} ) }
									</div>
								) }
							</div>
						</>
					) }

					{ /* ── Transfer section ── */ }
					{ transferTotal > 0 && (
						<>
							<div className="cs-divider" />
							<div className="cs-section">
								<div className="cs-row">
									<span className="cs-section__title">
										<Van size={ 12 } className="cs-section-icon" />
										{ __( 'Airport Transfer', 'hola-weddings' ) }
									</span>
									<span>{ formatPrice( transferTotal ) }</span>
								</div>
							</div>
						</>
					) }

					{ /* ── Insurance section ── */ }
					{ insurancePlan && (
						<>
							<div className="cs-divider" />
							<div className="cs-section">
								<div className="cs-section__title">
									<ShieldCheck size={ 12 } className="cs-section-icon" />
									{ __( 'Insurance', 'hola-weddings' ) }
								</div>
								<div className="cs-row">
									<span className="cs-insurance-name">{ insurancePlan.name }</span>
									<span>{ formatPrice( insuranceTotal ) }</span>
								</div>
							</div>
						</>
					) }

					{ /* ── Totals section ── */ }
					<div className="cs-divider" />
					<div className="cs-section">
						<div className="cs-row cs-row--total">
							<span>{ __( 'Total', 'hola-weddings' ) }</span>
							<span>{ formatPrice( grandTotal ) }</span>
						</div>
						<div className="cs-row cs-row--deposit">
							<span>{ __( 'Today you pay', 'hola-weddings' ) }</span>
							<span>{ formatPrice( deposit ) }</span>
						</div>
					</div>
				</>
			) }
		</StepCard>
	);
}
