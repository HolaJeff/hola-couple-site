import { __, _n, sprintf } from '@wordpress/i18n';
import { useState }         from '@wordpress/element';
import { useBooking }       from '../../hooks/useBooking';
import { useRooms }         from '../../hooks/useRooms';
import { nightsBetween }    from '../../utils/dates';
import RoomDetailsModal     from './RoomDetailsModal';
import Button               from '../Button';
import Callout              from '../ui/Callout';
import { ArrowRight, Circle, CircleCheck, Image as ImageIcon, Info, TriangleAlert } from 'lucide-react';
import { LOCALE }           from '../../utils/locale';

const CURRENCY = window.hwBooking?.currency ?? 'USD';

function formatPrice( amount ) {
	return new Intl.NumberFormat( LOCALE, { style: 'currency', currency: CURRENCY } ).format( amount );
}

function excerptHtml( html, max = 150 ) {
	if ( ! html ) return '';
	const text = html.replace( /<[^>]*>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
	return text.length > max ? text.slice( 0, max ).trimEnd() + '…' : text;
}

export default function StepRooms() {
	const { search, searchVersion, selectedRoom, serverErrors, dispatch, nextStep } = useBooking();
	const { products, loading, error } = useRooms( search, searchVersion );

	const [ modalRoom, setModalRoom ] = useState( null );

	const nights = nightsBetween( search.checkin, search.checkout );

	function selectRoom( product ) {
		dispatch( { type: 'SET_ROOM', payload: product } );
	}

	return (
		<>
		{ modalRoom && (
			<RoomDetailsModal
				room={ modalRoom }
				onClose={ () => setModalRoom( null ) }
			/>
		) }
		<div>
			{ loading && [ 1, 2, 3 ].map( ( n ) => (
				<div key={ n } className="room-card room-card--skeleton">
					<div className="room-card__image skeleton-block" />
					<div className="room-card__body">
						<div className="d-flex flex-column flex-md-row align-items-start align-items-md-center">
							<div className="skeleton-block room-card-skeleton__tag mb-2 mb-md-0" />
							<div className="skeleton-block skeleton-line skeleton-line--title mb-0" />
						</div>

						<div>
							<div className="skeleton-block skeleton-line skeleton-line--sub mt-2 mb-0 w-50" />
							<div className="skeleton-block skeleton-line skeleton-line--sub mt-1" />
						</div>

						<div className="room-card__footer">
							<div className="skeleton-block room-card-skeleton__btn" />
							<div className="room-card__price">
								<div className="skeleton-block room-card-skeleton__price-label" />
								<div className="skeleton-block room-card-skeleton__price-amount" />
							</div>
						</div>
					</div>
				</div>
			) ) }
			{ error && <p className="alert alert-danger">{ error }</p> }

			{ ! loading && ! error && products.length === 0 && (
				<Callout variant="alert" icon={ <TriangleAlert /> }>
					{ __( 'No rooms available for the selected dates.', 'hola-weddings' ) }
				</Callout>
			) }

			{ [ ...products ]
			.map( ( product ) => ( { product, roomPrice: Number( product.prices?.total ?? product.total ?? 0 ) } ) )
			.filter( ( { roomPrice } ) => roomPrice > 0 )
			.sort( ( a, b ) => a.roomPrice - b.roomPrice )
			.map( ( { product, roomPrice } ) => {
				const isSelected = selectedRoom?.id === product.id;

				return (
					<div
						key={ product.id }
						className={ `room-card${ isSelected ? ' room-card--selected' : '' }` }
						onClick={ () => selectRoom( product ) }
						role="radio"
						aria-checked={ isSelected }
						tabIndex={ 0 }
						onKeyDown={ ( e ) => e.key === 'Enter' && selectRoom( product ) }
					>
						{ /* Image */ }
						<div
							className="room-card__image"
							style={ product.image ? { backgroundImage: `url(${ product.image })` } : undefined }
						>
							{ ! product.image && <ImageIcon className="room-card__image-placeholder" /> }
						</div>

						{ /* Content */ }
						<div className="room-card__body">
							<div className='d-flex flex-column flex-md-row align-items-md-center gap-3'>
								{ /* Availability badge */ }
								{ product.available ? (
									<div className='mb-2 mb-md-0'>
										<span className="room-tag room-tag--available">
											<Circle size={ 8 } fill="currentColor" strokeWidth={ 0 } />
											{ __( 'Available', 'hola-weddings' ) }
										</span>
									</div>
								) : (
									<div className='mb-2 mb-md-0'>
										<span className="room-tag room-tag--pending">
											{ __( 'On Request', 'hola-weddings' ) }
										</span>
									</div>
								) }
								{ /* Room Name */ }
								<div className="room-card__name">
									{ product.name }
								</div>
							</div>

							<p className="room-card__description">{ excerptHtml( product.description ) }</p>

							<div className="room-card__footer">
								<button
									type="button"
									className="room-card__details-btn btn btn-outline-primary"
									onClick={ ( e ) => { e.stopPropagation(); setModalRoom( product ); } }
								>
									<Info size={ 14 } />
									{ __( 'Room Details & Rates', 'hola-weddings' ) }
								</button>
								{ nights > 0 && roomPrice > 0 && (
									<div className="room-card__price">
										<div className="room-card__price-label">
											{ sprintf(
												/* translators: %d: number of nights */
												_n( 'Price for %d night', 'Price for %d nights', nights, 'hola-weddings' ),
												nights
											) }
										</div>
										<div className="room-card__price-amount primary-color">
											{ formatPrice( roomPrice ) }
										</div>
									</div>
								) }
							</div>
						</div>

						{ /* Selected indicator */ }
						{ isSelected && (
							<CircleCheck className="room-card__check" />
						) }
					</div>
				);
			} ) }

			<div className="mt-4 d-flex justify-content-end">
				<Button
					type="button"
					variant="primary"
					icon={ <ArrowRight size={ 18 } /> }
					onClick={ nextStep }
					disabled={ ! selectedRoom }
				>
					{ __( 'Next', 'hola-weddings' ) }
				</Button>
			</div>
		</div>
		</>
	);
}
