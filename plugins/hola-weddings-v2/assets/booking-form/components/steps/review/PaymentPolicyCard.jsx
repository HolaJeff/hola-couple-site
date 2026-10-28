import { __ } from '@wordpress/i18n';
import { StepCard } from '../../StepCard';
import { CreditCard } from 'lucide-react';

const PAYMENT_POLICY = window.hwBooking?.paymentPolicy ?? '';

export default function PaymentPolicyCard() {
	if ( ! PAYMENT_POLICY ) return null;

	return (
		<StepCard id='payment-policy' variant="policy-payment" className="mb-3">
			<StepCard.Header icon={ <CreditCard size={ 16 } /> } title={ __( 'Payment Policy', 'hola-weddings' ) } />
			<div
				className="policy-block__body"
				dangerouslySetInnerHTML={ { __html: PAYMENT_POLICY } }
			/>
		</StepCard>
	);
}
