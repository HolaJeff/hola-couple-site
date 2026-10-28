import { __ } from '@wordpress/i18n';
import { StepCard } from '../../StepCard';
import { Ban }       from 'lucide-react';

const CANCELLATION_POLICY = window.hwBooking?.cancellationPolicy ?? '';

export default function CancellationCard() {
	if ( ! CANCELLATION_POLICY ) return null;

	return (
		<StepCard id='cancellation-policy' variant="cancellation" className="mb-3">
			<StepCard.Header icon={ <Ban size={ 16 } /> } title={ __( 'Cancellation Policy', 'hola-weddings' ) } />
			<div
				className="policy-block__body"
				dangerouslySetInnerHTML={ { __html: CANCELLATION_POLICY } }
			/>
		</StepCard>
	);
}
