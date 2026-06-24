import { isMobileViewport, mobileMediaQuery } from './constants';

/**
 * Body classes many themes toggle when their mobile nav is open.
 * Detection is best-effort only — drill-down does not depend on this.
 */
const THEME_MENU_OPEN_BODY_CLASSES = [
	'menu-open',
	'nav-open',
	'mobile-menu-open',
	'is-menu-open',
	'opened',
	'navigation-open',
	'et_pb_slide_menu_opened',
];

/**
 * @param {object} options
 * @param {() => void} [options.onThemeMenuClose]
 * @param {() => void} [options.onViewportChange]
 */
export function initHamburgerCoexistence( { onThemeMenuClose, onViewportChange } = {} ) {
	const mediaQueryList = window.matchMedia( mobileMediaQuery );

	const syncMobileModeClass = () => {
		document.querySelectorAll( '.low-mm-nav-container' ).forEach( ( container ) => {
			if ( isMobileViewport() ) {
				container.classList.add( 'low-mm-mobile-mode' );
			} else {
				container.classList.remove( 'low-mm-mobile-mode' );
			}
		} );

		onViewportChange?.();
	};

	syncMobileModeClass();
	mediaQueryList.addEventListener( 'change', syncMobileModeClass );

	let themeMenuWasOpen = isThemeMenuOpen();

	const observer = new MutationObserver( () => {
		const isOpen = isThemeMenuOpen();
		if ( themeMenuWasOpen && ! isOpen ) {
			onThemeMenuClose?.();
		}
		themeMenuWasOpen = isOpen;
	} );

	observer.observe( document.body, {
		attributes: true,
		attributeFilter: [ 'class' ],
	} );

	// Optional: common hamburger selectors — used only to track state, not to gate drill-down.
	document.addEventListener(
		'click',
		( event ) => {
			const target = event.target;
			if ( ! ( target instanceof Element ) ) {
				return;
			}

			if (
				target.closest(
					'.menu-toggle, .hamburger, .nav-toggle, [aria-controls*="menu"], [data-toggle="mobile-menu"]'
				)
			) {
				window.setTimeout( () => {
					themeMenuWasOpen = isThemeMenuOpen();
				}, 0 );
			}
		},
		true
	);

	return () => {
		mediaQueryList.removeEventListener( 'change', syncMobileModeClass );
		observer.disconnect();
	};
}

/**
 * @returns {boolean}
 */
function isThemeMenuOpen() {
	return THEME_MENU_OPEN_BODY_CLASSES.some( ( className ) => document.body.classList.contains( className ) );
}
