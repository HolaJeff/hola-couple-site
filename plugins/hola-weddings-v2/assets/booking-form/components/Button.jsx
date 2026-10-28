/**
 * Booking form button.
 *
 * Carries no styling of its own: appearance comes from the plugin's `.btn`
 * rules (assets/src/style/_bootstrap-reset.scss, shipped as `hw-main`), so a
 * theme that restyles the plugin's buttons restyles these too. Layout used to
 * be set inline here, which no stylesheet could override — it now lives on
 * `.btn--with-icon` in styles/_bootstrap-overrides.scss.
 *
 * @param {object}  props
 * @param {string}  [props.variant]       - Visual style: 'primary', 'danger', 'outline-primary', etc. → adds btn-{variant}
 * @param {*}       [props.icon]          - Lucide icon element, e.g. <ArrowRight size={ 18 } />
 * @param {string}  [props.iconPosition]  - 'left' | 'right' (default 'right')
 * @param {string}  [props.className]     - extra classes
 * @param {*}       [props.children]
 * @param {object}  [...props]            - all native <button> attributes (type, onClick, disabled, …)
 */
export default function Button( {
	children,
	variant,
	icon,
	iconPosition = 'right',
	className    = '',
	...props
} ) {
	const cls = [ 'btn', variant && `btn-${ variant }`, icon && 'btn--with-icon', className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<button className={ cls } { ...props }>
			{ icon && iconPosition === 'left'  && icon }
			{ children }
			{ icon && iconPosition === 'right' && icon }
		</button>
	);
}
