/**
 * Collects browser / page metadata that is sent automatically with every report.
 * No user-identifiable data beyond what is already visible in the browser URL bar.
 *
 * @returns {object}
 */
export function collectClientContext() {
	return {
		page_url: window.location.href,
		user_agent: navigator.userAgent,
		screen: {
			width:  window.screen?.width  ?? 0,
			height: window.screen?.height ?? 0,
		},
		viewport: {
			width:  document.documentElement.clientWidth,
			height: document.documentElement.clientHeight,
		},
		device_pixel_ratio: window.devicePixelRatio ?? 1,
		referrer:  document.referrer,
		timestamp: new Date().toISOString(),
	};
}
