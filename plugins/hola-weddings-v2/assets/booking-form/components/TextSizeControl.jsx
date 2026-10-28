import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { AArrowUp, ALargeSmall, Minus, Plus, X } from 'lucide-react';
import EdgeTrigger, { EDGE_ORDER } from '../../shared/EdgeTrigger';

/**
 * Text size tool, in the shared edge-trigger stack on the right of the screen
 * alongside the feedback and dev toolbar triggers. Collapsed it shows an "aA" trigger;
 * clicking it slides the trigger out and the A+ / A− panel in.
 *
 * Both are always rendered so they can animate in both directions; the hidden
 * one is taken out of the tab order by `visibility` in the stylesheet.
 *
 * @param {object}   props
 * @param {number}   props.scale
 * @param {boolean}  props.canIncrease
 * @param {boolean}  props.canDecrease
 * @param {Function} props.increase
 * @param {Function} props.decrease
 */
export default function TextSizeControl( { scale, canIncrease, canDecrease, increase, decrease } ) {
	const [ open, setOpen ] = useState( false );
	const panelRef   = useRef( null );
	const triggerRef = useRef( null );
	const firstRef   = useRef( null );
	const wasOpen    = useRef( false );

	// Move focus into the panel on open, and back to the trigger on close, so
	// keyboard users stay on the tool rather than on a now-hidden button.
	useEffect( () => {
		if ( open ) {
			firstRef.current?.focus();
		} else if ( wasOpen.current ) {
			triggerRef.current?.focus();
		}
		wasOpen.current = open;
	}, [ open ] );

	useEffect( () => {
		if ( ! open ) return;
		const onMouse = ( e ) => {
			if ( panelRef.current && ! panelRef.current.contains( e.target ) ) setOpen( false );
		};
		const onKey = ( e ) => { if ( e.key === 'Escape' ) setOpen( false ); };
		document.addEventListener( 'mousedown', onMouse );
		document.addEventListener( 'keydown', onKey );
		return () => {
			document.removeEventListener( 'mousedown', onMouse );
			document.removeEventListener( 'keydown', onKey );
		};
	}, [ open ] );

	return (
		<EdgeTrigger order={ EDGE_ORDER.textSize }>
			<button
				ref={ triggerRef }
				type="button"
				className={ `hw-edge-trigger tsc-trigger${ open ? ' tsc-trigger--hidden' : '' }` }
				onClick={ () => setOpen( true ) }
				aria-label={ __( 'Text size', 'hola-weddings' ) }
				aria-expanded={ open }
				title={ __( 'Text size', 'hola-weddings' ) }
			>
				<AArrowUp size={ 20 } />
			</button>

			<div
				ref={ panelRef }
				className={ `tsc-panel${ open ? ' tsc-panel--open' : '' }` }
				role="group"
				aria-label={ __( 'Text size', 'hola-weddings' ) }
			>
				<button
					ref={ firstRef }
					type="button"
					className="tsc-panel__btn"
					onClick={ increase }
					disabled={ ! canIncrease }
					aria-label={ __( 'Increase text size', 'hola-weddings' ) }
					title={ __( 'Increase text size', 'hola-weddings' ) }
				>
					<span aria-hidden="true">A</span><Plus size={ 12 } strokeWidth={ 2.5 } />
				</button>
				<span className="tsc-panel__value" aria-live="polite">{ Math.round( scale * 100 ) }%</span>
				<button
					type="button"
					className="tsc-panel__btn"
					onClick={ decrease }
					disabled={ ! canDecrease }
					aria-label={ __( 'Decrease text size', 'hola-weddings' ) }
					title={ __( 'Decrease text size', 'hola-weddings' ) }
				>
					<span aria-hidden="true">A</span><Minus size={ 12 } strokeWidth={ 2.5 } />
				</button>
				<button
					type="button"
					className="tsc-panel__btn tsc-panel__btn--close"
					onClick={ () => setOpen( false ) }
					aria-label={ __( 'Close text size controls', 'hola-weddings' ) }
					title={ __( 'Close', 'hola-weddings' ) }
				>
					<X size={ 16 } />
				</button>
			</div>
		</EdgeTrigger>
	);
}
