import { useBooking, saveBookingState } from '../store/bookingContext';

// Localised by Scripts.php: { enabled, url, label }. The URL already carries the
// return address, so there is nothing to assemble here.
const GOOGLE = window.hwBooking?.googleLogin ?? {};

/**
 * "Continue with Google" button.
 *
 * Renders nothing unless the network has OAuth credentials and this site has the
 * button switched on, so call sites need no condition of their own.
 *
 * Signing in leaves the page, so the in-progress booking is persisted first —
 * done here rather than by the caller, which is easy to forget.
 *
 * @param {Object} props
 * @param {string} [props.divider]   Separator text above the button, e.g. 'or'. Omit for none.
 * @param {string} [props.className] Extra classes for the anchor.
 */
export default function GoogleLoginButton( { divider = '', className = '', hideLabel = false } ) {
	const ctx = useBooking();

	if ( ! GOOGLE.enabled || ! GOOGLE.url ) return null;

	return (
		<>
			{ divider && (
				<div className="hw-auth-divider"><span>{ divider }</span></div>
			) }

			<a
				className={ `hw-google-btn ${ className }`.trim() }
				href={ GOOGLE.url }
				onClick={ () => saveBookingState( ctx ) }
			>
				{ /* Google's official mark, inlined — Font Awesome brands are not loaded here. */ }
				<svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
					<path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.92a8.78 8.78 0 0 0 2.68-6.62z" />
					<path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.92-2.26c-.81.54-1.84.86-3.04.86a5.36 5.36 0 0 1-5.03-3.7H1.05v2.34A9 9 0 0 0 9 18z" />
					<path fill="#FBBC05" d="M3.97 10.72a5.4 5.4 0 0 1 0-3.44V4.94H1.05a9 9 0 0 0 0 8.12l2.92-2.34z" />
					<path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.59A9 9 0 0 0 1.05 4.94l2.92 2.34A5.36 5.36 0 0 1 9 3.58z" />
				</svg>
				{ !hideLabel && (
					<span>{ GOOGLE.label }</span>
				) }
			</a>
		</>
	);
}
