import { __, _n, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useBooking } from '../store/bookingContext';
import { nightsBetween } from '../utils/dates';

// ── Pure helpers ──────────────────────────────────────────────────────────────

/**
 * Sum a person-type column across all nightly rate entries.
 * Mirrors get_person_type_total() / get_person_type_rates_total() in cart.js.
 *
 * @param {Object} rates  prices.rates object ({ night_1: { adult, teen, child, infant }, … })
 * @param {string} type   'adult' | 'teen' | 'child' | 'infant'
 * @returns {number}
 */
function personTypeTotal( rates, type ) {
	return Object.values( rates ?? {} ).reduce(
		( sum, night ) => sum + ( parseInt( night[ type ] ) || 0 ),
		0,
	);
}

/**
 * Find the insurance price tier whose [min, max] range contains `target`.
 * Mirrors get_range() in cart.js.
 *
 * @param {Array}  plans   hw_cart.insurance_plans
 * @param {number} planId  selected plan id
 * @param {number} target  value to look up (nights, room total, cost-per-person, …)
 * @returns {Object|null}  tier price object or null if no match
 */
function getRange( plans, planId, target ) {
	const plan = ( plans ?? [] ).find( ( p ) => String( p.id ) === String( planId ) );
	if ( ! plan ) return null;
	return Object.values( plan.prices ?? {} ).find(
		( tier ) => parseInt( tier.min ) <= target && parseInt( tier.max ) >= target,
	) ?? null;
}

// ── useCart ───────────────────────────────────────────────────────────────────

/**
 * Derive full cart state from booking context.
 *
 * Mirrors the complete calculation pipeline in assets/js/cart.js:
 *   clear_cart → update_rooms → handle_transfer → update_insurance
 *
 * Returned `items` use a structured `breakdown` array instead of HTML strings,
 * so CartSidebar can render them however it likes.
 *
 * @returns {{
 *   items:          Array<{ quantity: number, title: string, price: number|'included', breakdown: Array }>,
 *   roomTotal:      number,
 *   transferTotal:  number,
 *   insuranceTotal: number,
 *   grandTotal:     number,
 *   deposit:        number,
 *   isRoomAvailable: boolean,
 * }}
 */
export function useCart() {
	const { search, selectedRoom, selectedPackage, insurance, billing, payment } = useBooking();

	return useMemo( () => {
		const cfg     = window[ 'hw_cart'   ] ?? {};   // legacy (may be absent on React page)
		const hb      = window[ 'hwBooking' ] ?? {};   // React-page localized data
		const strings = window[ 'messages'  ] ?? {};
		const mode    = cfg.mode ?? hb.mode ?? 'room';
		// Derived from the chosen dates, not from hw_cart: that object belongs to
		// the legacy form and is absent here, which pinned every nights-driven
		// total (night-mode insurance, the dynamic deposit) to a single night.
		const nights  = nightsBetween( search.checkin, search.checkout )
			|| parseInt( cfg.nights ?? 1 );

		const product = selectedRoom ?? selectedPackage;

		if ( ! product ) {
			return {
				items:           [],
				roomTotal:       0,
				transferTotal:   0,
				insuranceTotal:  0,
				grandTotal:      0,
				deposit:         0,
				isRoomAvailable: false,
			};
		}

		// Mutable accumulator — mirrors app.cart in cart.js
		const items = [];
		let cartTotal = 0;

		function addItem( item ) {
			items.push( item );
			if ( item.price !== 'included' ) {
				cartTotal += Number( item.price );
			}
		}

		// ── Room / Package  (mirrors update_rooms) ────────────────────────────

		const prices          = product.prices ?? {};
		const rates           = prices.rates   ?? {};
		const roomTotal       = Number( prices.total ?? product.total ?? 0 );
		const isRoomAvailable = Boolean( product.available );

		const { adults = 1, teens = 0, children = 0, infants = 0 } = search;

		const roomLabel = mode === 'room'
			? sprintf( /* translators: 1: room name. 2: number of nights */ _n( '%1$s (%2$d night)', '%1$s (%2$d nights)', nights, 'hola-weddings' ), product.name, nights )
			: sprintf( /* translators: %s: package name */ __( '%s Package', 'hola-weddings' ), product.name );

		addItem( {
			quantity:  1,
			title:     roomLabel,
			price:     roomTotal,
			breakdown: [
				{ count: parseInt( adults ),   label: strings.adult ?? __( 'Adult', 'hola-weddings' ),  total: personTypeTotal( rates, 'adult' ) },
				parseInt( teens )    > 0 ? { count: parseInt( teens ),    label: strings.teen  ?? __( 'Teen', 'hola-weddings' ),  total: personTypeTotal( rates, 'teen'  ) } : null,
				parseInt( children ) > 0 ? { count: parseInt( children ), label: strings.child ?? __( 'Child', 'hola-weddings' ), total: personTypeTotal( rates, 'child' ) } : null,
			].filter( Boolean ),
		} );

		// ── Airport transfer  (mirrors handle_transfer) ───────────────────────

		let transferTotal = 0;

		if ( cfg.has_transfer ?? hb.hasTransfer ) {
			const transferMode = cfg.transportation_mode ?? hb.transportationMode;
			const price        = parseInt( cfg.service_fee ?? hb.serviceFee ?? 0 );

			if ( price > 0 ) {
				if ( transferMode === 'room' ) {
					transferTotal = price;
					addItem( {
						quantity:  1,
						title:     strings.airport_transportation_round_trip ?? __( 'Airport Transfer (round-trip)', 'hola-weddings' ),
						price,
						breakdown: [],
					} );
				} else if ( transferMode === 'person' ) {
					const childPrice = parseInt( cfg.child_service_fee ?? hb.childServiceFee ?? 0 ) || price;
					const adultCount = parseInt( adults ) + parseInt( teens );
					const childCount = parseInt( children );
					const adultFee   = adultCount * price;
					const childFee   = childCount * childPrice;
					transferTotal    = adultFee + childFee;

					addItem( {
						quantity:  adultCount + childCount,
						title:     strings.airport_transportation_round_trip ?? __( 'Airport Transfer (round-trip)', 'hola-weddings' ),
						price:     transferTotal,
						breakdown: [
							{ count: adultCount, label: strings.adult ?? __( 'Adult', 'hola-weddings' ), total: adultFee },
							childCount > 0 ? { count: childCount, label: strings.child ?? __( 'Child', 'hola-weddings' ), total: childFee } : null,
						].filter( Boolean ),
					} );
				}
			} else {
				// Free transfer — included in room price
				addItem( {
					quantity:  1,
					title:     strings.airport_transportation_round_trip ?? __( 'Airport Transfer (round-trip)', 'hola-weddings' ),
					price:     'included',
					breakdown: [],
				} );
			}
		}

		// ── Insurance  (mirrors update_insurance) ─────────────────────────────

		let insuranceTotal  = 0;
		const insurancePlanId = insurance?.planId ?? null;
		const insuranceMode   = cfg.insurance_mode  ?? hb.insuranceData?.mode  ?? null;
		const insurancePlans  = cfg.insurance_plans ?? hb.insuranceData?.plans ?? [];
		const isTeenEnabled   = Boolean( cfg.is_teen_enabled ?? hb.isTeenEnabled );
		const isUS            = ( billing?.country ?? '' ) === 'US';

		if ( insurancePlanId && insuranceMode && isUS ) {
			const adultsMultiplier = parseInt( adults ) + ( isTeenEnabled ? parseInt( teens ) : 0 );

			// Mode: night — per adult/child per night
			if ( insuranceMode === 'night' ) {
				const tier = getRange( insurancePlans, insurancePlanId, nights );
				if ( tier ) {
					const adultIns = tier.adult * adultsMultiplier;
					addItem( {
						quantity:  adultsMultiplier,
						title:     `${ strings.adult_insurance ?? __( 'Adult Insurance', 'hola-weddings' ) } (${ tier.adult } USD)`,
						price:     adultIns,
						breakdown: [],
					} );
					insuranceTotal += adultIns;

					if ( parseInt( children ) > 0 ) {
						const childIns = tier.child * parseInt( children );
						addItem( {
							quantity:  parseInt( children ),
							title:     `${ strings.child_insurance ?? __( 'Child Insurance', 'hola-weddings' ) } (${ tier.child } USD)`,
							price:     childIns,
							breakdown: [],
						} );
						insuranceTotal += childIns;
					}
				}

			// Mode: price — tier based on room total
			} else if ( insuranceMode === 'price' ) {
				const tier = getRange( insurancePlans, insurancePlanId, roomTotal );
				if ( tier ) {
					const totalGuests = adultsMultiplier + parseInt( children );
					const ins = tier.adult * totalGuests;
					addItem( {
						quantity:  totalGuests,
						title:     `${ strings.insurance ?? __( 'Insurance', 'hola-weddings' ) } (${ tier.adult } USD)`,
						price:     ins,
						breakdown: [],
					} );
					insuranceTotal = ins;
				}

			// Mode: room_total — fixed amount or percentage of room total
			} else if ( insuranceMode === 'room_total' ) {
				const tier = getRange( insurancePlans, insurancePlanId, roomTotal );
				const plan = ( insurancePlans ?? [] ).find( ( p ) => String( p.id ) === String( insurancePlanId ) );
				if ( tier && plan ) {
					const calcType = plan.calculation_type ?? 'fixed';
					const cost = calcType === 'percentage'
						? Math.ceil( roomTotal *  parseInt( tier.amount ) / 100 )
						: parseInt( tier.amount );
					const totalGuests = adultsMultiplier + parseInt( children );
					addItem( {
						quantity:  totalGuests,
						/* translators: %s: insurance line label */
						title:     sprintf( __( '%s (Charged Per Room)', 'hola-weddings' ), strings.insurance ?? __( 'Insurance', 'hola-weddings' ) ),
						price:     cost,
						breakdown: [],
					} );
					insuranceTotal = cost;
				}

			// Mode: person_total — tier based on each person type's rate total + per-person transfer share
			} else if ( insuranceMode === 'person_total' ) {
				// Compute per-person transfer cost to add before tier lookup, mirroring legacy logic
				let adultTransferCost = 0;
				let childTransferCost = 0;
				if ( cfg.has_transfer ?? hb.hasTransfer ) {
					const tMode      = cfg.transportation_mode ?? hb.transportationMode;
					const serviceFee = parseInt( cfg.service_fee ?? 0 );
					if ( tMode === 'room' ) {
						const totalPersons = parseInt( adults ) + parseInt( teens ) + parseInt( children ) + parseInt( infants );
						adultTransferCost = childTransferCost = totalPersons > 0 ? serviceFee / totalPersons : 0;
					} else if ( tMode === 'person' ) {
						adultTransferCost = serviceFee;
						const childFee    = parseInt( cfg.child_service_fee ?? 0 );
						childTransferCost = childFee > 0 ? childFee : serviceFee;
					}
				}

				let insTotal   = 0;
				const breakdown = [];

				const adultTier = getRange( insurancePlans, insurancePlanId, personTypeTotal( rates, 'adult' ) + adultTransferCost );
				if ( adultTier ) {
					const amt = parseInt( adults ) * parseInt( adultTier.adult );
					insTotal += amt;
					breakdown.push( { count: parseInt( adults ), label: strings.adult ?? __( 'Adult', 'hola-weddings' ), total: amt } );
				}

				if ( parseInt( teens ) > 0 ) {
					const teenTier = getRange( insurancePlans, insurancePlanId, personTypeTotal( rates, 'teen' ) + adultTransferCost );
					if ( teenTier ) {
						const amt = parseInt( teens ) * parseInt( teenTier.teen );
						insTotal += amt;
						breakdown.push( { count: parseInt( teens ), label: strings.teen ?? __( 'Teen', 'hola-weddings' ), total: amt } );
					}
				}

				if ( parseInt( children ) > 0 ) {
					const childTier = getRange( insurancePlans, insurancePlanId, personTypeTotal( rates, 'child' ) + childTransferCost );
					if ( childTier ) {
						const amt = parseInt( children ) * parseInt( childTier.child );
						insTotal += amt;
						breakdown.push( { count: parseInt( children ), label: strings.child ?? __( 'Child', 'hola-weddings' ), total: amt } );
					}
				}

				if ( parseInt( infants ) > 0 ) {
					// Infants don't pay — look up tier at 0
					const infantTier = getRange( insurancePlans, insurancePlanId, 0 );
					if ( infantTier ) {
						const amt = parseInt( infants ) * parseInt( infantTier.infant );
						insTotal += amt;
						breakdown.push( { count: parseInt( infants ), label: strings.infant ?? __( 'Infant', 'hola-weddings' ), total: amt } );
					}
				}

				addItem( { quantity: 1, title: strings.insurance ?? __( 'Insurance', 'hola-weddings' ), price: insTotal, breakdown } );
				insuranceTotal = insTotal;

			// Mode: total_cost_per_person — tier based on (room + transfer) / total guests
			} else if ( insuranceMode === 'total_cost_per_person' ) {
				const personsCount   = parseInt( adults ) + parseInt( teens ) + parseInt( children ) + parseInt( infants );
				const pricePerPerson = personsCount > 0 ? ( roomTotal + transferTotal ) / personsCount : 0;
				const tier           = getRange( insurancePlans, insurancePlanId, pricePerPerson );

				if ( tier ) {
					const adultIns  = parseInt( adults )   * parseInt( tier.adult );
					const teenIns   = parseInt( teens )    * parseInt( tier.teen );
					const childIns  = parseInt( children ) * parseInt( tier.child );
					const infantIns = parseInt( infants )  * parseInt( tier.infant );
					const insTotal  = adultIns + teenIns + childIns + infantIns;

					addItem( {
						quantity:  1,
						title:     strings.insurance ?? __( 'Insurance', 'hola-weddings' ),
						price:     insTotal,
						breakdown: [
							{ count: parseInt( adults ),   label: strings.adult  ?? __( 'Adult', 'hola-weddings' ),  total: adultIns  },
							parseInt( teens )    > 0 ? { count: parseInt( teens ),    label: strings.teen   ?? __( 'Teen', 'hola-weddings' ),   total: teenIns   } : null,
							parseInt( children ) > 0 ? { count: parseInt( children ), label: strings.child  ?? __( 'Child', 'hola-weddings' ),  total: childIns  } : null,
							parseInt( infants )  > 0 ? { count: parseInt( infants ),  label: strings.infant ?? __( 'Infant', 'hola-weddings' ), total: infantIns } : null,
						].filter( Boolean ),
					} );
					insuranceTotal = insTotal;
				}
			}
		}

		// ── Deposit  (mirrors calc_deposit_amount + handle_deposit) ─────────

		const uiDepositType      = payment?.depositType ?? 'deposit';
		const phpDepositType     = cfg.deposit_type    ?? hb.depositType    ?? 'fixed';
		const minDeposit         = parseInt( cfg.min_deposit ?? hb.minDeposit ?? 0 );
		const nightsMultiplier   = parseInt( cfg.deposit_nights_multiplier ?? hb.depositNightsMultiplier ?? 1 );

		// Base deposit: fixed min or dynamic (first-night rate × multiplier)
		let baseDeposit = minDeposit;
		if ( phpDepositType === 'dynamic' ) {
			const totalPerDay    = prices.total_per_day ?? {};
			const firstDayTotal  = Number( Object.values( totalPerDay )[ 0 ] ?? 0 );
			const effectiveMult  = Math.min( nightsMultiplier, nights );
			if ( firstDayTotal > 0 ) {
				baseDeposit = firstDayTotal * effectiveMult;
			}
		}

		// Always add insurance; cap at grand total so deposit never exceeds full amount
		const minDepositAmount = Math.min( baseDeposit + insuranceTotal, cartTotal );

		let deposit;
		switch ( uiDepositType ) {
			case 'deposit':
				deposit = minDepositAmount;
				break;
			case 'custom':
				deposit = Number( payment?.depositAmount ?? minDepositAmount );
				break;
			case 'full':
			default:
				deposit = cartTotal;
		}

		return {
			items,
			roomTotal,
			transferTotal,
			insuranceTotal,
			grandTotal:       cartTotal,
			minDepositAmount,
			deposit,
			isRoomAvailable,
		};
	}, [
		selectedRoom,
		selectedPackage,
		insurance?.planId,
		billing?.country,
		payment?.depositType,
		payment?.depositAmount,
		search.adults,
		search.teens,
		search.children,
		search.infants,
	] );
}
