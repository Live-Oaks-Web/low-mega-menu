import { overrideDiviNav } from './config';
import { getAdminBarHeight } from './admin-bar';

const NAV_ID = 'et-top-navigation';
const HEADER_ID = 'main-header';
const FIXED_HEADER_CLASS = 'et-fixed-header';
const HEIGHT_VAR = '--low-mm-divi-nav-height';
const HEADER_BOTTOM_VAR = '--low-mm-divi-header-bottom';
const DIVI_MOBILE_NAV_SELECTORS = [
	'#et_mobile_nav_menu',
	'.et_mobile_nav_menu',
	'#main-header .mobile_menu_bar',
	'#main-header .et_pb_header_toggle',
	'#main-header .mobile_nav',
];

/**
 * Resolve the plugin-owned Divi nav node.
 *
 * @returns {HTMLElement|null}
 */
function getNavElement() {
	const byId = document.getElementById( NAV_ID );
	if ( byId instanceof HTMLElement ) {
		return byId;
	}

	const byClass = document.querySelector( '#main-header .low-mm-header-navigation' );
	return byClass instanceof HTMLElement ? byClass : null;
}

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
 * Remove Divi hamburger / mobile nav markup if it reappears (Customizer / Divi JS).
 */
export function hideDiviMobileNavMarkup() {
	if ( ! overrideDiviNav() ) {
		return;
	}

	DIVI_MOBILE_NAV_SELECTORS.forEach( ( selector ) => {
		document.querySelectorAll( selector ).forEach( ( element ) => {
			if ( element instanceof HTMLElement && ! element.classList.contains( 'low-mm-menu-toggle' ) ) {
				element.remove();
			}
		} );
	} );
}

/**
 * Publish the Divi nav height so CSS can vertically center the menu within it,
 * and refresh the header-bottom metric used by the mobile drawer.
 */
export function syncDiviHeaderMetrics() {
	const nav = getNavElement();
	if ( ! ( nav instanceof HTMLElement ) ) {
		syncDiviHeaderBottom();
		return;
	}

	const header = document.getElementById( HEADER_ID );
	const height = getDiviNavHeight( nav, header );

	if ( height > 0 ) {
		nav.style.setProperty( HEIGHT_VAR, `${ height }px` );
		nav.setAttribute( 'data-height', String( parseInt( nav.getAttribute( 'data-height' ) || String( height ), 10 ) || height ) );
	} else {
		nav.style.removeProperty( HEIGHT_VAR );
	}

	syncDiviHeaderBottom();
	hideDiviMobileNavMarkup();
}

/**
 * Keep the Divi nav height in sync on load, scroll (sticky transition), and resize.
 */
export function initDiviHeaderHeight() {
	if ( ! overrideDiviNav() ) {
		return;
	}

	const nav = getNavElement();
	if ( ! ( nav instanceof HTMLElement ) ) {
		return;
	}

	syncDiviHeaderMetrics();
	hideDiviMobileNavMarkup();

	window.addEventListener( 'scroll', syncDiviHeaderMetrics, { passive: true } );
	window.addEventListener( 'resize', syncDiviHeaderMetrics, { passive: true } );

	const header = document.getElementById( HEADER_ID );
	if ( header instanceof HTMLElement && typeof MutationObserver !== 'undefined' ) {
		const observer = new MutationObserver( () => {
			syncDiviHeaderMetrics();
			hideDiviMobileNavMarkup();
		} );
		observer.observe( header, {
			attributes: true,
			attributeFilter: [ 'class' ],
			childList: true,
			subtree: true,
		} );
	}
}
