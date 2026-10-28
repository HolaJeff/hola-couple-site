import FeedbackToast from './FeedbackToast';

export default function FeedbackToastStack( { toasts, onDismiss } ) {
	if ( ! toasts.length ) return null;

	return (
		<div className="fb-toast-stack" aria-label="Feedback submissions">
			{ toasts.map( ( toast ) => (
				<FeedbackToast
					key={ toast.id }
					toast={ toast }
					onDismiss={ onDismiss }
				/>
			) ) }
		</div>
	);
}
