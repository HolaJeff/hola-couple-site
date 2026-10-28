import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { getRoomDetails }       from '../../api/booking';
import { Tag }                from 'lucide-react';
import { LOCALE }             from '../../utils/locale';

const CURRENCY = window.hwBooking?.currency ?? 'USD';

// Build an allowlist of enabled person-type slugs from the page-load settings.
// This mirrors get_person_types() on the PHP side and acts as a frontend guard.
const ENABLED_PERSON_SLUGS = ( () => {
	const hw     = window.hwBooking ?? {};
	const slugs  = [ 'adult', 'child' ];
	if ( hw.isChild2Enabled ) slugs.push( 'child2' );
	if ( hw.isTeenEnabled   ) slugs.push( 'teen'   );
	if ( hw.isTeen2Enabled  ) slugs.push( 'teen2'  );
	return new Set( slugs );
} )();

function formatPrice( price ) {
	if ( price === '' || price === null || price === undefined ) return __( 'N/A', 'hola-weddings' );
	if ( Number( price ) === 0 ) return __( 'Free', 'hola-weddings' );
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( price );
}

function RatesTableSkeleton() {
	return (
		<table className="room-modal__table">
			<thead>
				<tr>
					{ [ 0, 1, 2, 3 ].map( i => (
						<th key={ i }><div className="skeleton-block skeleton-line" style={ { height: 14, width: i === 0 ? 100 : 60, margin: '0 auto' } } /></th>
					) ) }
				</tr>
			</thead>
			<tbody>
				{ [ 0, 1, 2 ].map( i => (
					<tr key={ i }>
						{ [ 0, 1, 2, 3 ].map( j => (
							<td key={ j }><div className="skeleton-block skeleton-line" style={ { height: 14, width: j === 0 ? 90 : 50, margin: '0 auto' } } /></td>
						) ) }
					</tr>
				) ) }
			</tbody>
		</table>
	);
}

export default function RoomDetailsModal( { room, onClose } ) {
	const [ details, setDetails ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error,   setError   ] = useState( null );

	useEffect( () => {
		if ( ! room ) return;
		setLoading( true );
		setError( null );
		setDetails( null );
		getRoomDetails( room.id )
			.then( data  => setDetails( data ) )
			.catch( err  => setError( err?.message ?? __( 'Could not load room details.', 'hola-weddings' ) ) )
			.finally( () => setLoading( false ) );
	}, [ room?.id ] );

	if ( ! room ) return null;

	const personTypes = details?.person_types ?? {};   // { adult: 'Adult', child: 'Child', … }
	const rateTypes   = details?.rate_types   ?? [];   // [{ name, slug }, …]
	const prices      = details?.prices       ?? {};   // { rate_slug: { person_slug: price } }
	const personSlugs = Object.keys( personTypes ).filter( s => ENABLED_PERSON_SLUGS.has( s ) );

	return (
		<div className="hw-dialog-backdrop" onClick={ onClose }>
			<div className="room-modal" onClick={ e => e.stopPropagation() }>

				{ /* Image */ }
				{ room.image && (
					<div
						className="room-modal__image"
						style={ { backgroundImage: `url(${ room.image })` } }
					/>
				) }

				{ /* Header */ }
				<div className="room-modal__header">
					<h4 className="room-modal__title">{ room.name }</h4>
					<button
						type="button"
						className="room-modal__close"
						aria-label={ __( 'Close', 'hola-weddings' ) }
						onClick={ onClose }
					>
						&times;
					</button>
				</div>

				<div className="room-modal__body">

					{ /* Description */ }
					{ room.description && (
						<div
							className="room-modal__description"
							dangerouslySetInnerHTML={ { __html: room.description } }
						/>
					) }

					{ /* Rates */ }
					<div className="room-modal__rates">
						<h5 className="room-modal__rates-title">
							<Tag size={ 16 } />
							{ __( 'Rates', 'hola-weddings' ) }
						</h5>

						{ loading && <RatesTableSkeleton /> }

						{ error && (
							<p className="alert alert-warning mb-0">{ error }</p>
						) }

						{ ! loading && ! error && rateTypes.length > 0 && (
							<div className="table-responsive">
								<table className="room-modal__table">
									<thead>
										<tr>
											<th></th>
											{ personSlugs.map( slug => (
												<th key={ slug }>{ personTypes[ slug ] }</th>
											) ) }
										</tr>
									</thead>
									<tbody>
										{ rateTypes.map( rate => (
											<tr key={ rate.slug }>
												<td>{ rate.name }</td>
												{ personSlugs.map( slug => {
													const rowPrices = prices[ rate.slug ] ?? {};
													const val = slug in rowPrices ? rowPrices[ slug ] : '';
													return <td key={ slug }>{ formatPrice( val ) }</td>;
												} ) }
											</tr>
										) ) }
									</tbody>
								</table>
							</div>
						) }
					</div>

				</div>
			</div>
		</div>
	);
}
