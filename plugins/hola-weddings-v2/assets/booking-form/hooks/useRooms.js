import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { getRooms, getPackages } from '../api/booking';

/**
 * Fetch rooms or packages for the given search parameters.
 *
 * @param {Object|null} search  Search params (checkin, checkout, adults…)
 * @returns {{ products: Array, loading: boolean, error: string|null }}
 */
export function useRooms( search, searchVersion ) {
	const [ products, setProducts ] = useState( [] );
	const [ loading, setLoading ]   = useState( false );
	const [ error, setError ]       = useState( null );

	useEffect( () => {
		if ( ! search?.checkin || ! search?.checkout ) return;

		setLoading( true );
		setError( null );
		setProducts( [] );

		const fetch = window.hwBooking?.mode === 'package' ? getPackages : getRooms;

		fetch( search )
			.then( setProducts )
			.catch( ( err ) => setError( err?.message ?? __( 'Error loading products', 'hola-weddings' ) ) )
			.finally( () => setLoading( false ) );
	}, [ search?.checkin, search?.checkout, search?.adults, search?.teens, search?.children, search?.infants, searchVersion ] );

	return { products, loading, error };
}
