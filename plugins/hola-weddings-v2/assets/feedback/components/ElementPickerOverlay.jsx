import { useState, useEffect, useRef, useCallback } from '@wordpress/element';

/**
 * Full-screen element picker. Highlights elements as the user hovers and
 * captures the clicked element. Esc cancels.
 *
 * @param {object}   props
 * @param {Function} props.onCapture  Called with the clicked DOM element.
 * @param {Function} props.onCancel   Called when the user presses Esc.
 */
export default function ElementPickerOverlay( { onCapture, onCancel } ) {
	const overlayRef         = useRef( null );
	const hoveredRef         = useRef( null );
	const [ rect, setRect ]  = useState( null );

	useEffect( () => {
		const onKey = ( e ) => { if ( e.key === 'Escape' ) onCancel(); };
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onCancel ] );

	const handleMouseMove = useCallback( ( e ) => {
		const overlay = overlayRef.current;
		if ( ! overlay ) return;

		// Disable the overlay briefly so elementFromPoint sees what's underneath.
		overlay.style.pointerEvents = 'none';
		const el = document.elementFromPoint( e.clientX, e.clientY );
		overlay.style.pointerEvents = 'auto';

		if ( ! el || el === overlay ) return;

		hoveredRef.current = el;
		const r = el.getBoundingClientRect();
		setRect( { top: r.top, left: r.left, width: r.width, height: r.height } );
	}, [] );

	const handleClick = useCallback( ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		onCapture( hoveredRef.current );
	}, [ onCapture ] );

	return (
		<>
			<div
				ref={ overlayRef }
				className="fb-picker-overlay"
				onMouseMove={ handleMouseMove }
				onClick={ handleClick }
			/>

			{ rect && (
				<div
					className="fb-picker-highlight"
					style={ { top: rect.top, left: rect.left, width: rect.width, height: rect.height } }
				/>
			) }

			<div className="fb-picker-hint">
				<i className="fas fa-crosshairs" />
				{ ' ' }Click on any element to capture it
				<span className="fb-picker-hint__sep">·</span>
				<kbd>Esc</kbd> to cancel
			</div>
		</>
	);
}
