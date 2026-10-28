/**
 * Nights between two YYYY-MM-DD dates, 0 when either is missing or unparseable.
 * Mirrors getNights() in PHP, which the server uses for every date-driven total.
 */
export function nightsBetween( checkin, checkout ) {
	if ( ! checkin || ! checkout ) return 0;

	const start = Date.parse( checkin );
	const end   = Date.parse( checkout );

	if ( Number.isNaN( start ) || Number.isNaN( end ) ) return 0;

	return Math.max( 0, Math.round( ( end - start ) / 86400000 ) );
}
