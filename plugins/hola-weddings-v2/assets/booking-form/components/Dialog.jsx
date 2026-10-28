import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { ChevronRight, ChevronUp, CircleCheck, CircleX, Info, TriangleAlert } from 'lucide-react';

const TYPE_CONFIG = {
	success: { Icon: CircleCheck,   color: '#28a745' },
	error:   { Icon: CircleX,       color: '#dc3545' },
	info:    { Icon: Info,          color: '#0d6efd' },
	warning: { Icon: TriangleAlert, color: '#fd7e14' },
};

/**
 * Generic dialog for user-facing messages and confirmations.
 *
 * @param {Object}        props
 * @param {boolean}       props.open
 * @param {string}        props.type        — 'success' | 'error' | 'info' | 'warning'
 * @param {string}        props.title
 * @param {string}        [props.message]
 * @param {Object|null}   [props.errors]    — field-keyed server error messages
 * @param {Function}      props.onClose
 * @param {Array}         [props.actions]   — [{ label, onClick, primary }]
 */
export default function Dialog( { open, type = 'info', title, message, errors = null, onClose, actions = [], btnText, redirectUrl = null, redirectDelay = 3000 } ) {
	const [ expanded, setExpanded ] = useState( false );

	useEffect( () => {
		if ( open ) setExpanded( false );
	}, [ open ] );

	if ( ! open ) return null;

	const { Icon, color } = TYPE_CONFIG[ type ] ?? TYPE_CONFIG.info;
	const errorList = errors ? Object.values( errors ).filter( Boolean ) : [];

	return (
		<div className="hw-dialog-backdrop" onClick={ onClose }>
			<div className="hw-dialog" onClick={ e => e.stopPropagation() }>

				<div className="hw-dialog__icon-wrap" style={ { color } }>
					<Icon size={ 56 } />
				</div>

				{ title   && <h4 className="hw-dialog__title">{ title }</h4> }
				{ message && <p  className="hw-dialog__message">{ message }</p> }

				{ errorList.length > 0 && (
					<div className="hw-dialog__errors">
						<button
							type="button"
							className="hw-dialog__errors-toggle"
							onClick={ () => setExpanded( v => ! v ) }
						>
							{ expanded ? <ChevronUp size={ 14 } /> : <ChevronRight size={ 14 } /> }
							{ expanded ? __( 'Hide errors', 'hola-weddings' ) : __( 'Show errors', 'hola-weddings' ) }
						</button>
						{ expanded && (
							<ul className="hw-dialog__errors-list">
								{ errorList.map( ( msg, i ) => <li key={ i }>{ msg }</li> ) }
							</ul>
						) }
					</div>
				) }

				<div className="hw-dialog__actions">
					{ actions.length > 0
						? actions.map( ( action, i ) => (
							<button
								key={ i }
								className={ `btn ${ action.primary ? 'btn-primary' : 'btn-secondary' }` }
								onClick={ action.onClick ?? onClose }
							>
								{ action.label }
							</button>
						) )
						: (
							<button
								className="btn btn-primary"
								onClick={ onClose }
								disabled={ !! redirectUrl }
							>
								{ redirectUrl ? __( 'Please wait…', 'hola-weddings' ) : ( btnText ?? __( 'OK', 'hola-weddings' ) ) }
							</button>
						)
					}
				</div>

			</div>

			{ redirectUrl && (
				<div
					className="hw-dialog__redirect-bar"
					style={ { animationDuration: `${ redirectDelay }ms` } }
				/>
			) }

		</div>

	);
}
