const ADMIN_BAR_ID = 'wpadminbar';

/**
 * Whether the WordPress admin bar is present on the front end.
 *
 * @returns {boolean}
 */
export function isAdminBarPresent() {
	return (
		document.body.classList.contains( 'admin-bar' ) &&
		document.getElementById( ADMIN_BAR_ID ) instanceof HTMLElement
	);
}

/**
 * Live admin bar height in pixels (0 when absent or hidden).
 *
 * @returns {number}
 */
export function getAdminBarHeight() {
	if ( ! isAdminBarPresent() ) {
		return 0;
	}

	const bar = document.getElementById( ADMIN_BAR_ID );
	if ( ! ( bar instanceof HTMLElement ) ) {
		return 0;
	}

	const rect = bar.getBoundingClientRect();
	if ( rect.height <= 0 || rect.bottom <= 0 ) {
		return 0;
	}

	return Math.round( rect.height );
}

/**
 * Publish the current offset as a CSS custom property for mobile styles.
 *
 * @returns {number}
 */
export function syncAdminBarOffset() {
	const height = getAdminBarHeight();
	document.documentElement.style.setProperty( '--low-mm-admin-bar-offset', `${ height }px` );
	return height;
}

/** @type {ResizeObserver|null} */
let resizeObserver = null;

/**
 * Keep --low-mm-admin-bar-offset in sync when the admin bar is shown, hidden, or resized.
 */
export function initAdminBarOffset() {
	syncAdminBarOffset();

	window.addEventListener( 'resize', syncAdminBarOffset, { passive: true } );
	window.addEventListener( 'scroll', syncAdminBarOffset, { passive: true } );

	if ( typeof ResizeObserver === 'undefined' || ! isAdminBarPresent() ) {
		return;
	}

	const bar = document.getElementById( ADMIN_BAR_ID );
	if ( ! ( bar instanceof HTMLElement ) ) {
		return;
	}

	resizeObserver = new ResizeObserver( () => {
		syncAdminBarOffset();
	} );
	resizeObserver.observe( bar );
}

/**
 * Minimum viewport top for fixed UI (desktop panels) so content stays below the admin bar.
 *
 * @returns {number}
 */
export function getAdminBarMinTop() {
	return getAdminBarHeight();
}
