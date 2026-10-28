const INSURANCE_ID = Number( window.hwBooking?.insuranceData?.id ?? 0 );

export const DECLINE_ID = 0; // sentinel — user explicitly chose no insurance

// Classic insurance (id 2) does not sell the Elite plan (id 4) to New York
// residents. Mirrors hw_is_insurance_plan_allowed() in includes/functions.php
export function isPlanAllowed( planId, state ) {
	return ! (
		INSURANCE_ID === 2 &&
		Number( planId ) === 4 &&
		String( state ?? '' ).trim().toUpperCase() === 'NY'
	);
}

// Drops a selected plan the billing state no longer allows, so an Elite plan
// picked before switching the state to NY is not carried into the quote
export function sanitizeInsurance( insurance, billing ) {
	if ( ! insurance?.planId || billing?.country?.toUpperCase() !== 'US' ) {
		return insurance;
	}

	return isPlanAllowed( insurance.planId, billing.state )
		? insurance
		: { ...insurance, planId: DECLINE_ID };
}
