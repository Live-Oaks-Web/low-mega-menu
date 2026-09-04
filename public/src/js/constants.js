/**
 * Shared breakpoint — sourced from Settings → Mobile breakpoint (default 1024).
 * Keep CSS source at 1024; AssetLoader rewrites media queries when customized.
 */

/**
 * @returns {number}
 */
export function getMobileBreakpointPx() {
	const { mobileBreakpoint } = window.lowMmPublicConfig || {};
	if ( typeof mobileBreakpoint === 'number' && mobileBreakpoint > 0 ) {
		return mobileBreakpoint;
	}
	return 1024;
}

/**
 * @returns {string}
 */
export function getMobileMediaQuery() {
	return `(max-width: ${ getMobileBreakpointPx() - 1 }px)`;
}

/**
 * @returns {boolean}
 */
export function isMobileViewport() {
	return window.matchMedia( getMobileMediaQuery() ).matches;
}
