import { useEffect, useRef } from '@wordpress/element';

/**
 * Small confirmation dialog rendered over a dimmed backdrop.
 * Traps focus and closes on Escape or backdrop click.
 *
 * @param {object}   props
 * @param {string}   props.title        Dialog heading.
 * @param {string}   props.message      Body text.
 * @param {string}  [props.confirmLabel] Defaults to "Confirm".
 * @param {string}  [props.confirmClass] Extra class for the confirm button.
 * @param {boolean} [props.busy]         Show spinner and disable buttons.
 * @param {Function} props.onConfirm
 * @param {Function} props.onCancel
 */
export default function ConfirmDialog( {
	title,
	message,
	confirmLabel = 'Confirm',
	confirmClass = 'btn-danger',
	busy         = false,
	onConfirm,
	onCancel,
} ) {
	const cancelRef = useRef( null );

	useEffect( () => {
		cancelRef.current?.focus();

		const onKey = ( e ) => { if ( e.key === 'Escape' && ! busy ) onCancel(); };
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ busy, onCancel ] );

	return (
		<div
			className="fb-confirm-backdrop"
			onClick={ ( e ) => { if ( e.target === e.currentTarget && ! busy ) onCancel(); } }
			role="dialog"
			aria-modal="true"
			aria-label={ title }
		>
			<div className="fb-confirm">
				<div className="fb-confirm__header">
					<i className="fas fa-exclamation-triangle fb-confirm__icon" />
					<span className="fb-confirm__title">{ title }</span>
				</div>

				<p className="fb-confirm__message">{ message }</p>

				<div className="fb-confirm__actions">
					<button
						ref={ cancelRef }
						type="button"
						className="btn btn-sm btn-outline-secondary"
						onClick={ onCancel }
						disabled={ busy }
					>
						Cancel
					</button>
					<button
						type="button"
						className={ `btn btn-sm ${ confirmClass }` }
						onClick={ onConfirm }
						disabled={ busy }
					>
						{ busy
							? <><i className="fas fa-spinner fa-spin" /> Deleting…</>
							: confirmLabel
						}
					</button>
				</div>
			</div>
		</div>
	);
}
