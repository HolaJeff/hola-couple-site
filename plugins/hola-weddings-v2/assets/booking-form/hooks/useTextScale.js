import { useState, useEffect } from '@wordpress/element';

// Steps the reader can move through with A− / A+. 1 is the form's default,
// which is already floored at a readable size (see styles/_variables.scss), so
// the scale only goes up from there.
export const TEXT_SCALES = [ 1, 1.125, 1.25, 1.375, 1.5 ];

const STORAGE_KEY = 'hw_booking_text_scale';

function readStored() {
	try {
		const i = TEXT_SCALES.indexOf( parseFloat( localStorage.getItem( STORAGE_KEY ) ) );
		return i === -1 ? 0 : i;
	} catch {
		return 0;
	}
}

/**
 * The reader's chosen text size for the booking form, remembered per browser.
 *
 * @return {{ scale: number, canIncrease: boolean, canDecrease: boolean, increase: Function, decrease: Function }}
 */
export function useTextScale() {
	const [ index, setIndex ] = useState( readStored );

	useEffect( () => {
		try {
			localStorage.setItem( STORAGE_KEY, String( TEXT_SCALES[ index ] ) );
		} catch {}
	}, [ index ] );

	return {
		scale:       TEXT_SCALES[ index ],
		canIncrease: index < TEXT_SCALES.length - 1,
		canDecrease: index > 0,
		increase:    () => setIndex( ( i ) => Math.min( i + 1, TEXT_SCALES.length - 1 ) ),
		decrease:    () => setIndex( ( i ) => Math.max( i - 1, 0 ) ),
	};
}
