import './styles/index.scss';
import { createRoot } from '@wordpress/element';
import FeedbackButton from './components/FeedbackButton';

if ( window.hwFeedback ) {
	const container = document.createElement( 'div' );
	container.id = 'hw-feedback-root';
	document.body.appendChild( container );
	createRoot( container ).render( <FeedbackButton /> );
}
