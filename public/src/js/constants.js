/**
 * Shared breakpoint — keep in sync with CSS `--low-mm-breakpoint` in main.css.
 */
export const MOBILE_BREAKPOINT_PX = 1024;

export const mobileMediaQuery = `(max-width: ${ MOBILE_BREAKPOINT_PX - 1 }px)`;

/**
 * @returns {boolean}
 */
export function isMobileViewport() {
	return window.matchMedia( mobileMediaQuery ).matches;
}
