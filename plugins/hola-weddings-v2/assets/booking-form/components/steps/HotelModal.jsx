import { __ } from '@wordpress/i18n';
import { BedDouble, Martini, Plane, Utensils, WavesLadder } from 'lucide-react';

const HOTEL = window.hwBooking?.hotelData ?? null;

const FEATURE_MAP = [
	{ key: 'rooms',            Icon: BedDouble,   label: __( 'Rooms', 'hola-weddings' )            },
	{ key: 'restaurants',      Icon: Utensils,    label: __( 'Restaurants', 'hola-weddings' )      },
	{ key: 'bars',             Icon: Martini,     label: __( 'Bars', 'hola-weddings' )             },
	{ key: 'swimming_pools',   Icon: WavesLadder, label: __( 'Pools', 'hola-weddings' )            },
	{ key: 'ariport_distance', Icon: Plane,       label: __( 'Airport Distance', 'hola-weddings' ) },
];

export default function HotelModal( { onClose } ) {
	if ( ! HOTEL ) return null;

	const features   = HOTEL.features  ?? {};
	const travelInfo = HOTEL.travelInfo ?? [];

	const featureItems = FEATURE_MAP
		.map( ( { key, Icon, label } ) => features[ key ] != null ? { Icon, label, value: features[ key ] } : null )
		.filter( Boolean );

	return (
		<div className="hw-dialog-backdrop" onClick={ onClose }>
			<div className="room-modal" onClick={ ( e ) => e.stopPropagation() }>

				{ HOTEL.mainImage && (
					<div
						className="room-modal__image"
						style={ { backgroundImage: `url(${ HOTEL.mainImage })` } }
					/>
				) }

				<div className="room-modal__header">
					<h4 className="room-modal__title">{ HOTEL.name }</h4>
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

					{ HOTEL.description && (
						<div
							className="room-modal__description"
							dangerouslySetInnerHTML={ { __html: HOTEL.description } }
						/>
					) }

					{ featureItems.length > 0 && (
						<div>
							<h5 className="hotel-modal__section-title">{ __( 'Resort at a Glance', 'hola-weddings' ) }</h5>
							<div className="hotel-modal__features">
								{ featureItems.map( ( { Icon, label, value }, i ) => (
									<div key={ i } className="hotel-modal__feature">
										<Icon size={ 16 } />
										<span className="hotel-modal__feature-label mt-2">{ label }</span>
										<span className="hotel-modal__feature-value">{ value }</span>
									</div>
								) ) }
							</div>
						</div>
					) }

					{ travelInfo.length > 0 && (
						<div>
							<h5 className="hotel-modal__section-title">{ __( 'Travel Information', 'hola-weddings' ) }</h5>
							<div className="hotel-modal__travel">
								{ travelInfo.map( ( item, i ) => (
									<div key={ i } className="hotel-modal__travel-item">
										<div className="hotel-modal__travel-title">{ item.title }</div>
										<div className="hotel-modal__travel-value">{ item.value }</div>
									</div>
								) ) }
							</div>
						</div>
					) }

				</div>
			</div>
		</div>
	);
}
