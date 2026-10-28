import './styles/index.scss';
import apiFetch from '@wordpress/api-fetch';
import { createRoot } from '@wordpress/element';
import { LucideProvider } from 'lucide-react';
import App from './App';

apiFetch.use( apiFetch.createNonceMiddleware( window.hwBooking?.nonce ) );

// Tell the server which language the page is in, so its messages match
// (see the rest_pre_dispatch hook in includes/hw-rest-hooks.php).
apiFetch.use( ( options, next ) => next( {
	...options,
	headers: { ...options.headers, 'X-HW-Locale': window.hwBooking?.wpLocale ?? '' },
} ) );

const root = document.getElementById( 'hw-booking-form-root' );

// Defaults for every lucide icon in the tree, so individual call sites only pass
// a size when they deliberately differ. Matches the Feather settings the theme
// uses elsewhere — feather.replace({ 'stroke-width': 1.5, height: 20, width: 20 })
// — so icons stay consistent while Font Awesome is being phased out.
const ICON_DEFAULTS = {
	size:        20,
	strokeWidth: 1.5,
};

if ( root ) {
	createRoot( root ).render(
		<LucideProvider { ...ICON_DEFAULTS }>
			<App />
		</LucideProvider>
	);
}
