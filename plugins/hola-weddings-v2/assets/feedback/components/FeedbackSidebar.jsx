export default function FeedbackSidebar( { listOpen, onAddFeedback, onToggleList, onFold } ) {
	return (
		<div className="fb-sidebar" role="toolbar" aria-label="Feedback tools">
			<div className="fb-sidebar__brand">
				<i className="fas fa-bug" />
			</div>

			<button
				type="button"
				className="fb-sidebar__btn fb-sidebar__btn--add"
				onClick={ onAddFeedback }
				title="Add feedback"
				aria-label="Add feedback"
			>
				<i className="fas fa-plus" />
			</button>

			<button
				type="button"
				className={ `fb-sidebar__btn fb-sidebar__btn--list${ listOpen ? ' is-active' : '' }` }
				onClick={ onToggleList }
				title="View feedback list"
				aria-label="View feedback list"
				aria-pressed={ listOpen }
			>
				<i className="fas fa-list" />
			</button>

			<button
				type="button"
				className="fb-sidebar__btn fb-sidebar__btn--fold"
				onClick={ onFold }
				title="Hide sidebar"
				aria-label="Hide sidebar"
			>
				<i className="fas fa-chevron-right" />
			</button>
		</div>
	);
}
