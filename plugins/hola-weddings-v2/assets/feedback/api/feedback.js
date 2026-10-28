/**
 * Sends a feedback report to the WordPress REST endpoint.
 *
 * Uses plain fetch() rather than apiFetch because the request is
 * multipart/form-data (screenshot file upload), which apiFetch does not
 * handle natively. The Content-Type header is intentionally omitted so
 * the browser sets it with the correct multipart boundary.
 *
 * Authentication is handled by the WP REST nonce (X-WP-Nonce header).
 * Only administrators can submit — the server enforces this via manage_options.
 *
 * @param {object}   options
 * @param {object}   options.payload         Report data (will be JSON-stringified).
 * @param {Blob[]}  [options.screenshotBlobs] Optional array of PNG/JPEG blobs.
 * @param {string}  [options.endpoint]       Full URL to the WordPress endpoint.
 * @param {string}  [options.nonce]          WP REST nonce (X-WP-Nonce header).
 * @returns {Promise<{success: boolean, jira_key: string, jira_url: string, message: string}>}
 */
export async function submitFeedbackReport( {
	payload,
	screenshotBlobs = [],
	endpoint        = window.hwFeedback?.endpoint ?? '/api/hw/v1/feedback-report',
	nonce           = window.hwFeedback?.nonce,
} ) {
	const formData = new FormData();
	formData.append( 'payload', JSON.stringify( payload ) );

	screenshotBlobs.forEach( ( blob, i ) => {
		if ( blob ) {
			formData.append( 'screenshot[]', blob, `screenshot_${ i + 1 }.png` );
		}
	} );

	// Do NOT set Content-Type — the browser adds it with the correct boundary.
	const response = await fetch( endpoint, {
		method:  'POST',
		headers: { 'X-WP-Nonce': nonce ?? '' },
		body:    formData,
	} );

	const data = await response.json().catch( () => ( {} ) );

	if ( ! response.ok ) {
		const message = data?.message ?? `Server error ${ response.status }`;
		throw new Error( message );
	}

	return data;
}
