import { __, _n, sprintf } from '@wordpress/i18n';
import { useState }           from '@wordpress/element';
import { useBooking }         from '../../hooks/useBooking';
import { useCart }            from '../../hooks/useCart';
import { useBodyScrollLock }  from '../../hooks/useBodyScrollLock';
import CartSidebar            from './CartSidebar';
import { ShoppingCart, X }     from 'lucide-react';
import { LOCALE }              from '../../utils/locale';

const CURRENCY = window.hwBooking?.currency ?? 'USD';

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

function formatDate( ymd ) {
	if ( ! ymd ) return '';
	const [ y, m, d ] = ymd.split( '-' ).map( Number );
	return new Date( y, m - 1, d ).toLocaleDateString( LOCALE, { month: 'short', day: 'numeric' } );
}

function guestSummary( search ) {
	const adults   = parseInt( search.adults   ?? 0 );
	const teens    = parseInt( search.teens    ?? 0 );
	const children = parseInt( search.children ?? 0 );
	const infants  = parseInt( search.infants  ?? 0 );

	return [
		/* translators: %d: number of adults */
		adults   > 0 && sprintf( _n( '%d adult', '%d adults', adults, 'hola-weddings' ), adults ),
		/* translators: %d: number of teens */
		teens    > 0 && sprintf( _n( '%d teen', '%d teens', teens, 'hola-weddings' ), teens ),
		/* translators: %d: number of children */
		children > 0 && sprintf( _n( '%d child', '%d children', children, 'hola-weddings' ), children ),
		/* translators: %d: number of infants */
		infants  > 0 && sprintf( _n( '%d infant', '%d infants', infants, 'hola-weddings' ), infants ),
	].filter( Boolean ).join( ' · ' );
}

export default function MobileSummary() {
	const { search, searched } = useBooking();
	const { grandTotal }       = useCart();
	const [ open, setOpen ]    = useState( false );

	useBodyScrollLock( open );

	return (
		<>
			{ /* ── Fixed preview pill ── */ }
			<div
				className="ms-preview d-lg-none"
				role="button"
				onClick={ () => setOpen( true ) }
			>
				<span className="ms-preview__icon">
					<ShoppingCart size={ 18 } />
				</span>

				{ searched && (
					<div className="ms-preview__info">
						<span className="ms-preview__dates">
							{ formatDate( search.checkin ) } <span className='ms-preview__dates-separator'>→</span> { formatDate( search.checkout ) }
						</span>
						<span className="ms-preview__guests">{ guestSummary( search ) }</span>
					</div>
				) }

				<span className="ms-preview__total">{ formatPrice( grandTotal ) }</span>
			</div>

			{ /* ── Floating drawer + backdrop ── */ }
			{ open && (
				<>
					<div className="ms-backdrop" onClick={ () => setOpen( false ) } />
					<div className="ms-drawer">
						<button
							type="button"
							className="ms-drawer__close"
							aria-label={ __( 'Close', 'hola-weddings' ) }
							onClick={ () => setOpen( false ) }
						>
							<X size={ 18 } />
						</button>
						<CartSidebar />
					</div>
				</>
			) }
		</>
	);
}
