import { overrideDiviNav } from './config';
import { getAdminBarHeight } from './admin-bar';

const NAV_ID = 'et-top-navigation';
const HEADER_ID = 'main-header';
const FIXED_HEADER_CLASS = 'et-fixed-header';
const HEIGHT_VAR = '--low-mm-divi-nav-height';
const HEADER_BOTTOM_VAR = '--low-mm-divi-header-bottom';

/**
 * Read the Divi-intended nav height from data attributes. Divi stores the
 * normal height in `data-height` and the shrunk/sticky height in
 * `data-fixed-height`; the active one depends on the fixed-header state.
 *
 * @param {HTMLElement} nav
 * @param {HTMLElement|null} header
 * @returns {number}
 */
function getDiviNavHeight( nav, header ) {
	const isFixed = header instanceof HTMLElement && header.classList.contains( FIXED_HEADER_CLASS );
	const attr = isFixed ? 'data-fixed-height' : 'data-height';
	const value = parseInt( nav.getAttribute( attr ) || '', 10 );

	return Number.isFinite( value ) && value > 0 ? value : 0;
}

/**
 * Live viewport position of #main-header's bottom edge in pixels. Clamped to the
 * admin bar so the value never tucks behind it when the header has scrolled away.
 *
 * @returns {number}
 */
export function getDiviHeaderBottom() {
	const header = document.getElementById( HEADER_ID );
	if ( ! ( header instanceof HTMLElement ) ) {
		return getAdminBarHeight();
	}

	const bottom = Math.round( header.getBoundingClientRect().bottom );

	return Math.max( 0, getAdminBarHeight(), bottom );
}

/**
 * Publish the header bottom edge so the mobile drawer can open flush beneath it.
 */
function syncDiviHeaderBottom() {
	document.documentElement.style.setProperty(
		HEADER_BOTTOM_VAR,
		`${ getDiviHeaderBottom() }px`
	);
}

/**
 * Publish the Divi nav height so CSS can vertically center the menu within it,
 * and refresh the header-bottom metric used by the mobile drawer.
 */
export function syncDiviHeaderMetrics() {
	const nav = document.getElementById( NAV_ID );
	if ( ! ( nav instanceof HTMLElement ) ) {
		return;
	}

	const header = document.getElementById( HEADER_ID );
	const height = getDiviNavHeight( nav, header );

	if ( height > 0 ) {
		nav.style.setProperty( HEIGHT_VAR, `${ height }px` );
	} else {
		nav.style.removeProperty( HEIGHT_VAR );
	}

	syncDiviHeaderBottom();
}

/**
 * Keep the Divi nav height in sync on load, scroll (sticky transition), and resize.
 */
export function initDiviHeaderHeight() {
	if ( ! overrideDiviNav() ) {
		return;
	}

	const nav = document.getElementById( NAV_ID );
	if ( ! ( nav instanceof HTMLElement ) ) {
		return;
	}

	syncDiviHeaderMetrics();

	window.addEventListener( 'scroll', syncDiviHeaderMetrics, { passive: true } );
	window.addEventListener( 'resize', syncDiviHeaderMetrics, { passive: true } );

	const header = document.getElementById( HEADER_ID );
	if ( header instanceof HTMLElement && typeof MutationObserver !== 'undefined' ) {
		const observer = new MutationObserver( syncDiviHeaderMetrics );
		observer.observe( header, { attributes: true, attributeFilter: [ 'class' ] } );
	}
}
