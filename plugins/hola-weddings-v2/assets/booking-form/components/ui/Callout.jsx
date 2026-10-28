export default function Callout( { variant, icon, className, children } ) {
	const cls = [
		'callout',
		variant   ? `callout--${ variant }` : null,
		className ?? null,
	].filter( Boolean ).join( ' ' );

	return (
		<div className={ cls } role="alert">
			{ icon && <span className="callout__icon flex-shrink-0">{ icon }</span> }
			<div className="callout__body">{ children }</div>
		</div>
	);
}
