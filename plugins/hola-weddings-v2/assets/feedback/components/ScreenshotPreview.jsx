/**
 * A fixed-size thumbnail with a floating remove button.
 * Intended to be rendered inside a .fb-screenshot-preview card body.
 */
export default function ScreenshotPreview( { url, onDiscard } ) {
	if ( ! url ) return null;

	return (
		<div className="fb-screenshot-thumb">
			<a
				href={ url }
				target="_blank"
				rel="noopener noreferrer"
				className="fb-screenshot-thumb__link"
				title="View full size"
			>
				<img
					src={ url }
					alt="Screenshot"
					className="fb-screenshot-thumb__img"
				/>
			</a>
			<button
				type="button"
				className="fb-screenshot-thumb__remove"
				onClick={ onDiscard }
				aria-label="Remove screenshot"
				title="Remove screenshot"
			>
				<i className="fas fa-times" />
			</button>
		</div>
	);
}
