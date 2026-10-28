import { useBooking as useBookingContext } from '../store/bookingContext';

/**
 * Re-export the context hook and expose step navigation helpers.
 */
export function useBooking() {
	const ctx = useBookingContext();

	function nextStep() {
		ctx.dispatch( { type: 'NEXT_STEP' } );
	}

	function prevStep() {
		ctx.dispatch( { type: 'PREV_STEP' } );
	}

	function goToStep( n ) {
		ctx.dispatch( { type: 'SET_STEP', payload: n } );
	}

	return { ...ctx, nextStep, prevStep, goToStep };
}
