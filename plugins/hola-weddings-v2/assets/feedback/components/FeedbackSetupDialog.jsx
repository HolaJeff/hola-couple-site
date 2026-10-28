export default function FeedbackSetupDialog( { onClose } ) {
	const settingsUrl = window.hwFeedback?.settingsUrl ?? '#';

	return (
		<div
			className="fb-modal-backdrop"
			onClick={ ( e ) => { if ( e.target === e.currentTarget ) onClose(); } }
			role="dialog"
			aria-modal="true"
			aria-label="Feedback setup required"
		>
			<div className="fb-modal fb-setup-dialog">
				<div className="fb-modal__header">
					<div className="fb-modal__title">
						<i className="fas fa-plug" /> Feedback Setup
					</div>
					<button
						type="button"
						className="fb-modal__close"
						onClick={ onClose }
						aria-label="Close"
					>
						<i className="fas fa-times" />
					</button>
				</div>

				<div className="fb-modal__body fb-setup-dialog__body">
					<div className="fb-setup-dialog__icon">
						<i className="fas fa-jira" />
					</div>
					<p className="fb-setup-dialog__message">
						The feedback widget requires a Jira connection and a project to be selected.
						Complete the setup in the network settings to start collecting feedback.
					</p>
					<a
						href={ settingsUrl }
						className="btn btn-primary"
					>
						<i className="fas fa-cog" /> Go to Jira Settings
					</a>
				</div>
			</div>
		</div>
	);
}
