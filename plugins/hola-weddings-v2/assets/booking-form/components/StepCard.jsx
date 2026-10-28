/**
 * StepCard — generic card used across booking steps.
 *
 * Usage:
 *   <StepCard variant="success" className="mb-3">
 *     <StepCard.Header icon={ <Check size={ 16 } /> } title="Done" subtitle="Optional" />
 *     ...body...
 *     <StepCard.Footer>
 *       <button>Back</button><button>Next</button>
 *     </StepCard.Footer>
 *   </StepCard>
 *
 * Variants: brand-secondary | alert | danger | success  (default = primary teal)
 */

export function StepCard( { children, variant, className = '', id, ...props } ) {
	const cls = [ 'step-card', variant && `step-card--${ variant }`, className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div id={ id } className={ cls } { ...props }>
			{ children }
		</div>
	);
}

StepCard.Header = function StepCardHeader( { icon, title, subtitle, children } ) {
	return (
		<div className="step-card__header">
			{ icon && (
				<div className="step-card__icon">
					{ icon }
				</div>
			) }
			<div>
				{ title    && <div className="step-card__title">{ title }</div> }
				{ subtitle && <div className="step-card__subtitle">{ subtitle }</div> }
				{ children }
			</div>
		</div>
	);
};

StepCard.Footer = function StepCardFooter( { children, className = '', ...props } ) {
	const cls = [ 'step-card__footer', className ].filter( Boolean ).join( ' ' );
	return (
		<div className={ cls } { ...props }>
			{ children }
		</div>
	);
};
