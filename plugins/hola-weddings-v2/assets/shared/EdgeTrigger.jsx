import { createPortal } from '@wordpress/element';

/**
 * Stack of floating tool triggers on the right edge of the screen, shared by
 * the booking form and feedback bundles.
 *
 * Each trigger renders into one fixed container, which stacks whatever is
 * present with the same gap. So a trigger that isn't shown for this user
 * (the dev toolbar and mock button are debug-only, under different flags)
 * leaves no hole, and neither bundle needs to know the other's offsets.
 * Styles: ./_edge-triggers.scss.
 */

const HOST_ID = 'hw-edge-triggers';

// Stack order, bottom to top.
export const EDGE_ORDER = {
	textSize:     1,
	feedback:     2,
	feedbackMock: 3,
	devToolbar:   4,
};

function getHost() {
	let host = document.getElementById( HOST_ID );
	if ( ! host ) {
		host = document.createElement( 'div' );
		host.id = HOST_ID;
		// Same reason as #hw-booking-form-root: the triggers translate themselves,
		// and TranslatePress rewriting React-managed DOM breaks them.
		host.setAttribute( 'data-no-translation', '' );
		document.body.appendChild( host );
	}
	return host;
}

/**
 * One 44×44 slot in the stack. Children are positioned inside the slot, so a
 * panel can be anchored to it (see TextSizeControl).
 *
 * @param {object} props
 * @param {number} props.order     - position in the stack, from EDGE_ORDER
 * @param {*}      props.children
 */
export default function EdgeTrigger( { order, children } ) {
	return createPortal(
		<div className="hw-edge-slot" style={ { order } }>
			{ children }
		</div>,
		getHost()
	);
}
