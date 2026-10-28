import { __ } from '@wordpress/i18n';
import { useEffect, createInterpolateElement } from '@wordpress/element';
import { useBooking } from '../../hooks/useBooking';
import { DECLINE_ID, isPlanAllowed } from '../../utils/insurance';
import { StepCard }   from '../StepCard';
import Callout        from '../ui/Callout';
import { ArrowLeft, ArrowRight, ShieldCheck, TriangleAlert } from 'lucide-react';

const insuranceData = window.hwBooking?.insuranceData ?? null;
const PLANS         = insuranceData?.plans       ?? [];
const DESCRIPTION   = insuranceData?.description ?? '';
const IMAGE         = insuranceData?.image       ?? '';

export default function StepInsurance() {
	const { billing, insurance, dispatch, nextStep, prevStep } = useBooking();

	const isUS       = billing?.country?.toUpperCase() === 'US';
	const plans      = PLANS.filter( ( plan ) => isPlanAllowed( plan.id, billing?.state ) );
	const hasPlans   = isUS && plans.length > 0;
	const selectedId = insurance?.planId ?? null; // null = no choice yet

	// Auto-advance for non-US guests — done in effect, never during render
	useEffect( () => {
		if ( ! isUS ) {
			dispatch( { type: 'SET_INSURANCE', payload: { planId: DECLINE_ID } } );
			nextStep();
		}
	}, [ isUS ] );

	// Pre-select decline so the select always has a valid value
	useEffect( () => {
		if ( isUS && selectedId === null ) {
			dispatch( { type: 'SET_INSURANCE', payload: { planId: DECLINE_ID } } );
		}
	}, [ isUS ] );

	function select( planId ) {
		dispatch( { type: 'SET_INSURANCE', payload: { planId } } );
	}

	const canContinue = selectedId !== null;

	// ── Non-US: render nothing while useEffect redirects ──────────────────────
	if ( ! isUS ) return null;

	// ── No plans configured ───────────────────────────────────────────────────
	if ( ! hasPlans ) {
		return (
			<div>
				<StepCard className="mb-3">
					<p className="text-muted mb-0">{ __( 'No insurance plans are available at this time.', 'hola-weddings' ) }</p>
				</StepCard>
				<div className="mt-3 d-flex justify-content-between">
					<button type="button" className="btn btn-secondary" onClick={ prevStep }>{ __( 'Back', 'hola-weddings' ) }</button>
					<button type="button" className="btn btn-info"      onClick={ nextStep }>{ __( 'Next', 'hola-weddings' ) }</button>
				</div>
			</div>
		);
	}

	// ── US with plans ─────────────────────────────────────────────────────────
	return (
		<div>

			<StepCard className="mb-3">
				{ IMAGE && <img src={ IMAGE } alt="" className="ins-provider__image mb-3" /> }
				{ DESCRIPTION && (
					<div
						className="ins-provider__desc mb-3"
						dangerouslySetInnerHTML={ { __html: DESCRIPTION } }
					/>
				) }
			</StepCard>

			<StepCard variant="brand-secondary" className="mb-3">
				<StepCard.Header icon={ <ShieldCheck size={ 16 } /> } title={ __( 'Choose your insurance plan', 'hola-weddings' ) } />

				<div className="form-group mb-3">
					<label className="ins-plan-label font-weight-bold">{ __( 'Choose your insurance plan', 'hola-weddings' ) }</label>
					<select
						className="form-control"
						value={ selectedId ?? '' }
						onChange={ ( e ) => {
							const val = e.target.value;
							select( val === '' ? null : Number( val ) );
						} }
					>
						<option value={ DECLINE_ID }>{ __( 'No, I do not need an insurance plan.', 'hola-weddings' ) }</option>
						{ plans.map( ( plan ) => (
							<option key={ plan.id } value={ plan.id }>{ plan.name }</option>
						) ) }
					</select>
				</div>

				<Callout variant="alert" icon={ <TriangleAlert /> } className="mb-0">
					{ createInterpolateElement(
						__( 'Without insurance the trip is <strong>100% non-refundable</strong> after a certain date. Please also note that the cost of the insurance policy itself is non-refundable.', 'hola-weddings' ),
						{ strong: <strong /> }
					) }
				</Callout>
			</StepCard>

			<div className="mt-3 d-flex justify-content-between">
				<button type="button" className="btn btn-secondary" onClick={ prevStep }>
					<ArrowLeft size={ 18 } />
					{ __( 'Back', 'hola-weddings' ) }
				</button>
				<button type="button" className="btn btn-primary" onClick={ nextStep } disabled={ ! canContinue }>
					{ __( 'Next', 'hola-weddings' ) }
					<ArrowRight size={ 18 } />
				</button>
			</div>
		</div>
	);
}
