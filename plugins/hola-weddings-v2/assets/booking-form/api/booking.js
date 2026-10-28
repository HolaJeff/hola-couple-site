import apiFetch   from '@wordpress/api-fetch';
import { devStore } from '../devStore';

const REST = window.hwBooking?.restUrl ?? '/wp-json/hw/v1/';

/**
 * Fetch available rooms for given search params.
 *
 * @param {Object} params { checkin, checkout, adults, teens, children, infants }
 * @returns {Promise<Array>}
 */
export function getRooms( params ) {
	const query = new URLSearchParams( params ).toString();
	return apiFetch( { url: `${ REST }rooms?${ query }` } );
}

/**
 * Fetch rate table data for the room details modal.
 *
 * @param {number} roomBlockId
 * @returns {Promise<{ person_types: Object, rate_types: Array, prices: Object }>}
 */
export function getRoomDetails( roomBlockId ) {
	return apiFetch( { url: `${ REST }rooms/${ roomBlockId }/details` } );
}

/**
 * Fetch available packages for given search params.
 *
 * @param {Object} params
 * @returns {Promise<Array>}
 */
export function getPackages( params ) {
	const query = new URLSearchParams( params ).toString();
	return apiFetch( { url: `${ REST }packages?${ query }` } );
}

/**
 * Submit the booking quote.
 *
 * @param {Object} data Full form payload
 * @returns {Promise<Object>}
 */
export function submitQuote( data ) {
	const dev = window.hwBooking?.debug ? devStore.get() : null;

	const payload = ( dev?.testMode )
		? { ...data, _test: true, _test_outcome: dev.mockOutcome }
		: data;

	const promise = apiFetch( { url: `${ REST }quote`, method: 'POST', data: payload } );

	if ( dev?.testMode || dev?.inspectMode ) {
		const openInspect = dev?.inspectMode ? { inspectOpen: true } : {};
		promise.then(
			response => devStore.set( { lastPayload: data, lastResponse: response, lastStatus: 200, ...openInspect } ),
			err      => devStore.set( { lastPayload: data, lastResponse: { error: err?.message, code: err?.code, ...( err?.data ?? {} ) }, lastStatus: err?.data?.status ?? 500, ...openInspect } ),
		);
	}

	return promise;
}

/**
 * Persist partial form data to the server session.
 *
 * @param {Object} data
 * @returns {Promise<Object>}
 */
export function saveSession( data ) {
	return apiFetch( {
		url:    `${ REST }session`,
		method: 'POST',
		data,
	} );
}
