const ICON = {
	processing: <i className="fas fa-spinner fa-spin" />,
	success:    <i className="fas fa-check-circle" />,
	error:      <i className="fas fa-times-circle" />,
};

const STATUS_TEXT = {
	processing: 'Submitting…',
	success:    'Created',
	error:      'Failed',
};

export default function FeedbackToast( { toast, onDismiss } ) {
	const { id, status, title, jiraKey, jiraUrl, error, statusText } = toast;
	// Per-toast overrides (e.g. delete toasts use different labels than submit toasts).
	const text = { ...STATUS_TEXT, ...statusText };

	return (
		<div className={ `fb-toast fb-toast--${ status }` } role="status" aria-live="polite">
			<div className="fb-toast__icon">
				{ ICON[ status ] }
			</div>

			<div className="fb-toast__body">
				<div className="fb-toast__title" title={ title }>{ title }</div>
				<div className="fb-toast__meta">
					<span className="fb-toast__status-text">{ text[ status ] }</span>
					{ status === 'success' && jiraKey && (
						<>
							{ ' · ' }
							<a
								href={ jiraUrl ?? '#' }
								target="_blank"
								rel="noopener noreferrer"
								className="fb-toast__key"
							>
								{ jiraKey }
							</a>
						</>
					) }
				</div>
				{ status === 'error' && error && (
					<div className="fb-toast__error">{ error }</div>
				) }
			</div>

			<button
				type="button"
				className="fb-toast__close"
				onClick={ () => onDismiss( id ) }
				aria-label="Dismiss notification"
			>
				<i className="fas fa-times" />
			</button>
		</div>
	);
}
