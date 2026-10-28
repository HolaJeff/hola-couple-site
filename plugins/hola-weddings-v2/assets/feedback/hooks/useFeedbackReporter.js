import { useState, useCallback, useRef } from '@wordpress/element';
import html2canvas                         from 'html2canvas';
import { submitFeedbackReport }           from '../api/feedback';
import { collectClientContext }           from '../components/collectClientContext';

export const FEEDBACK_STATUS = {
	IDLE:       'idle',
	CAPTURING:  'capturing',
	SUBMITTING: 'submitting',
	SUCCESS:    'success',
	ERROR:      'error',
};

export const MAX_SCREENSHOTS = 5;

// ── Asset inlining ──────────────────────────────────────────────────────────────
// html2canvas re-renders the DOM in JS and must re-fetch every asset itself. It
// silently drops images it can't load cross-origin (CSS background-image on a
// multisite subdomain / CDN), producing white boxes. To fix this the way tools
// like BugHerd/Marker.io do, we pre-fetch each asset and inline it as a
// same-origin data-URL before capturing — falling back to a server-side PHP
// proxy whenever a direct browser fetch is blocked by CORS.

function blobToDataUrl( blob ) {
	return new Promise( ( resolve, reject ) => {
		const reader   = new FileReader();
		reader.onload  = () => resolve( reader.result );
		reader.onerror = reject;
		reader.readAsDataURL( blob );
	} );
}

/** Resolve a possibly-relative CSS/asset URL against the document. */
function absoluteUrl( url ) {
	try {
		return new URL( url, document.baseURI ).href;
	} catch {
		return url;
	}
}

/**
 * Fetch an asset and return it as a data-URL.
 * Tries a direct browser fetch first (fast, works same-origin); on failure
 * routes through the same-origin PHP proxy so cross-origin assets come back
 * untainted.
 *
 * @param {string} url
 * @returns {Promise<string|null>}
 */
async function fetchAsDataUrl( url ) {
	const proxyEndpoint = window.hwFeedback?.proxyEndpoint;
	const nonce         = window.hwFeedback?.nonce;

	// 1) Direct fetch — succeeds for same-origin assets.
	try {
		const res = await fetch( url, { credentials: 'same-origin' } );
		if ( res.ok ) {
			const blob = await res.blob();
			if ( blob.type.startsWith( 'image/' ) ) return await blobToDataUrl( blob );
		}
	} catch {
		// CORS / network — fall through to the proxy.
	}

	// 2) Server-side proxy fallback.
	if ( proxyEndpoint ) {
		try {
			const res = await fetch(
				`${ proxyEndpoint }?url=${ encodeURIComponent( url ) }`,
				{ headers: { 'X-WP-Nonce': nonce ?? '' }, credentials: 'same-origin' },
			);
			if ( res.ok ) return await blobToDataUrl( await res.blob() );
		} catch {
			// Give up — leave the asset as-is.
		}
	}

	return null;
}

/**
 * Inline every CSS background-image and <img> asset that overlaps the capture
 * area as a data-URL. Returns a restore function that reverts all overrides.
 *
 * @param {DOMRect} cropRect  getBoundingClientRect() of the target element.
 * @param {number}  pad       Shadow-bleed padding (px).
 * @returns {Promise<Function>}
 */
async function inlineAssets( cropRect, pad ) {
	const restores = [];

	const left   = cropRect.left   - pad;
	const top    = cropRect.top    - pad;
	const right  = cropRect.right  + pad;
	const bottom = cropRect.bottom + pad;

	const overlaps = ( el ) => {
		const r = el.getBoundingClientRect();
		return ! ( r.right < left || r.left > right || r.bottom < top || r.top > bottom );
	};

	const els = Array.from( document.body.querySelectorAll( '*' ) ).filter( overlaps );

	await Promise.all( els.map( async ( el ) => {
		// ── CSS background-image (can be layered) ──
		const bg = window.getComputedStyle( el ).backgroundImage;
		if ( bg && bg !== 'none' ) {
			const matches = [ ...bg.matchAll( /url\(["']?([^"')]+)["']?\)/g ) ];
			let newBg   = bg;
			let changed = false;

			await Promise.all( matches.map( async ( m ) => {
				const raw = m[ 1 ];
				if ( raw.startsWith( 'data:' ) || raw.startsWith( 'blob:' ) ) return;
				const dataUrl = await fetchAsDataUrl( absoluteUrl( raw ) );
				if ( dataUrl ) {
					newBg   = newBg.replace( m[ 0 ], `url("${ dataUrl }")` );
					changed = true;
				}
			} ) );

			if ( changed ) {
				const prev = el.style.backgroundImage;
				el.style.backgroundImage = newBg;
				restores.push( () => { el.style.backgroundImage = prev; } );
			}
		}

		// ── <img> elements ──
		if ( el.tagName === 'IMG' && el.currentSrc && ! el.currentSrc.startsWith( 'data:' ) ) {
			const dataUrl = await fetchAsDataUrl( el.currentSrc );
			if ( dataUrl ) {
				const prevSrc    = el.getAttribute( 'src' );
				const prevSrcset = el.getAttribute( 'srcset' );
				el.setAttribute( 'src', dataUrl );
				if ( prevSrcset !== null ) el.removeAttribute( 'srcset' ); // force src
				restores.push( () => {
					if ( prevSrc !== null ) el.setAttribute( 'src', prevSrc ); else el.removeAttribute( 'src' );
					if ( prevSrcset !== null ) el.setAttribute( 'srcset', prevSrcset );
				} );
			}
		}
	} ) );

	return () => restores.forEach( ( fn ) => fn() );
}

// ── Hook ──────────────────────────────────────────────────────────────────────

/**
 * Orchestrates screenshot capture and report submission.
 *
 * @param {object}  options
 * @param {string} [options.nonce]      WP REST nonce.
 * @param {object} [options.appContext] { environment, version } injected from outside.
 * @returns {{ status, screenshots, error, jiraResult,
 *             captureElement, clearScreenshot, submit, reset }}
 */
export function useFeedbackReporter( {
	nonce     = window.hwFeedback?.nonce,
	appContext = null,
} = {} ) {
	const [ status,      setStatus      ] = useState( FEEDBACK_STATUS.IDLE );
	const [ screenshots, setScreenshots ] = useState( [] ); // [{ blob, url }, ...]
	const [ error,       setError       ] = useState( null );
	const [ jiraResult,  setJiraResult  ] = useState( null );

	// Tracks all object URLs so we can revoke them on cleanup.
	const objectUrlsRef = useRef( [] );

	// ── Screenshot ────────────────────────────────────────────────────────────

	/**
	 * Capture a DOM element. We inline cross-origin assets as data-URLs (direct
	 * fetch, proxy fallback), then html2canvas crops to the element's rect +
	 * shadow padding. Inline overrides are reverted immediately after the clone
	 * is taken, so the live page never visibly changes.
	 *
	 * @param {HTMLElement} element
	 * @returns {Promise<Blob|null>}
	 */
	const captureElement = useCallback( async ( element ) => {
		if ( ! element ) return null;
		setStatus( FEEDBACK_STATUS.CAPTURING );

		try {
			const rect  = element.getBoundingClientRect();
			const pad   = 20; // extra px each side to catch box-shadow bleed
			const scale = Math.min( window.devicePixelRatio ?? 1, 2 );

			const restore = await inlineAssets( rect, pad );

			// Render the whole documentElement with no crop options. We use the
			// root (not body) so the canvas origin is the true document (0,0) —
			// any body margin would otherwise offset the mapping. html2canvas's
			// own x/y/width/height cropping also skews under scroll + fractional
			// devicePixelRatio, so we crop manually below for symmetric margins.
			const root = document.documentElement;
			let fullCanvas;
			try {
				fullCanvas = await html2canvas( root, {
					useCORS:    true,
					allowTaint: false,
					backgroundColor: null,
					scale,
					logging:    false,
				} );
			} finally {
				restore();
			}

			// Derive the ACTUAL px-per-CSS-px from the rendered canvas instead of
			// trusting `scale`; html2canvas rounds, and the drift accumulates with
			// distance from the origin (uneven padding on far edges).
			const actualScaleX = fullCanvas.width  / root.scrollWidth;
			const actualScaleY = fullCanvas.height / root.scrollHeight;

			// Element position in document coordinates.
			const docLeft = rect.left + window.scrollX;
			const docTop  = rect.top  + window.scrollY;

			let sx = Math.round( ( docLeft - pad ) * actualScaleX );
			let sy = Math.round( ( docTop  - pad ) * actualScaleY );
			let sw = Math.round( ( rect.width  + pad * 2 ) * actualScaleX );
			let sh = Math.round( ( rect.height + pad * 2 ) * actualScaleY );

			// Clamp to the source bounds so drawImage never reads outside it.
			sx = Math.max( 0, sx );
			sy = Math.max( 0, sy );
			sw = Math.min( sw, fullCanvas.width  - sx );
			sh = Math.min( sh, fullCanvas.height - sy );

			const canvas  = document.createElement( 'canvas' );
			canvas.width  = Math.max( 1, sw );
			canvas.height = Math.max( 1, sh );
			canvas.getContext( '2d' ).drawImage( fullCanvas, sx, sy, sw, sh, 0, 0, sw, sh );

			return new Promise( ( resolve ) => {
				try {
					canvas.toBlob( ( blob ) => {
						if ( blob ) {
							const url = URL.createObjectURL( blob );
							objectUrlsRef.current.push( url );
							setScreenshots( ( prev ) => {
								if ( prev.length >= MAX_SCREENSHOTS ) return prev;
								return [ ...prev, { blob, url } ];
							} );
						}
						setStatus( FEEDBACK_STATUS.IDLE );
						resolve( blob );
					}, 'image/png', 0.9 );
				} catch ( secErr ) {
					console.warn( '[FeedbackReporter] Canvas tainted — cannot export:', secErr );
					setStatus( FEEDBACK_STATUS.IDLE );
					resolve( null );
				}
			} );
		} catch ( err ) {
			console.warn( '[FeedbackReporter] Capture failed:', err );
			setStatus( FEEDBACK_STATUS.IDLE );
			return null;
		}
	}, [] );

	/**
	 * Remove a screenshot by index, or clear all if no index is given.
	 *
	 * @param {number} [index]
	 */
	const clearScreenshot = useCallback( ( index ) => {
		if ( index === undefined ) {
			objectUrlsRef.current.forEach( ( url ) => URL.revokeObjectURL( url ) );
			objectUrlsRef.current = [];
			setScreenshots( [] );
		} else {
			const url = objectUrlsRef.current[ index ];
			if ( url ) URL.revokeObjectURL( url );
			objectUrlsRef.current.splice( index, 1 );
			setScreenshots( ( prev ) => prev.filter( ( _, i ) => i !== index ) );
		}
	}, [] );

	// ── Submission ────────────────────────────────────────────────────────────

	/**
	 * Submit the report to the WordPress REST endpoint.
	 *
	 * @param {object} formValues Values from the feedback form.
	 * @returns {Promise<object>} Resolved Jira result on success.
	 */
	const submit = useCallback( async ( formValues ) => {
		setStatus( FEEDBACK_STATUS.SUBMITTING );
		setError( null );

		const clientContext = collectClientContext();

		const payload = {
			...clientContext,
			title:       formValues.title,
			description: formValues.description ?? '',
			priority:    formValues.priority    ?? 'Medium',
			type:        formValues.type        ?? 'Bug',
			reporter: {
				name:      formValues.reporterName      ?? '',
				email:     formValues.reporterEmail     ?? '',
				accountId: formValues.reporterAccountId ?? '',
			},
			app_context: {
				environment: appContext?.environment ?? '',
				version:     appContext?.version     ?? '',
			},
		};

		try {
			const result = await submitFeedbackReport( {
				payload,
				screenshotBlobs: screenshots.map( ( s ) => s.blob ),
				nonce,
			} );

			setJiraResult( result );
			setStatus( FEEDBACK_STATUS.SUCCESS );
			return result;
		} catch ( err ) {
			setError( err.message ?? 'Submission failed.' );
			setStatus( FEEDBACK_STATUS.ERROR );
			throw err;
		}
	}, [ screenshots, nonce, appContext ] );

	// ── Reset ─────────────────────────────────────────────────────────────────

	const reset = useCallback( () => {
		setStatus( FEEDBACK_STATUS.IDLE );
		setError( null );
		setJiraResult( null );
		clearScreenshot();
	}, [ clearScreenshot ] );

	// ── Derived booleans ──────────────────────────────────────────────────────

	return {
		status,
		screenshots,
		error,
		jiraResult,
		isCapturing:  status === FEEDBACK_STATUS.CAPTURING,
		isSubmitting: status === FEEDBACK_STATUS.SUBMITTING,
		isSuccess:    status === FEEDBACK_STATUS.SUCCESS,
		isError:      status === FEEDBACK_STATUS.ERROR,
		captureElement,
		clearScreenshot,
		submit,
		reset,
	};
}
