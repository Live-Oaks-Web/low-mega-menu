import { isMobileViewport } from './constants';
import { syncAdminBarOffset } from './admin-bar';
import { overrideDiviNav } from './config';

const THEME_TOGGLE_SELECTORS = [
	'.menu-toggle',
	'.wp-block-navigation__responsive-container-open',
	'.wp-block-navigation__responsive-container-close',
	'.nav-toggle',
	'.hamburger',
	'.mobile_menu_bar',
	'.et_mobile_nav_menu',
	'[data-toggle="mobile-menu"]',
];

/**
 * Slide-in primary nav drawer for tablet and smaller viewports.
 */
export class MobileDrawerController {
	/**
	 * @param {HTMLElement} container
	 * @param {object} [options]
	 * @param {() => void} [options.onClose]
	 */
	constructor( container, { onClose } = {} ) {
		this.container = container;
		this.onClose = onClose;
		this.toggle = container.querySelector( '.low-mm-menu-toggle' );
		this.drawer = container.querySelector( '.low-mm-mobile-drawer' );
		this.backdrop = container.querySelector( '.low-mm-mobile-drawer__backdrop' );
		this.closeButton = container.querySelector( '.low-mm-drawer-close' );
		this.usePluginDrawer = this.shouldUsePluginDrawer();

		if ( ! this.usePluginDrawer ) {
			this.hidePluginToggle();
			return;
		}

		if ( ! ( this.toggle instanceof HTMLButtonElement ) || ! ( this.drawer instanceof HTMLElement ) ) {
			return;
		}

		if ( overrideDiviNav() ) {
			this.hideDiviMobileNav();
			this.toggle.classList.add( 'low-mm-menu-toggle--divi-slot' );
		}

		this.handleToggleClick = this.handleToggleClick.bind( this );
		this.handleBackdropClick = this.handleBackdropClick.bind( this );
		this.handleCloseClick = this.handleCloseClick.bind( this );

		this.toggle.addEventListener( 'click', this.handleToggleClick );
		this.backdrop?.addEventListener( 'click', this.handleBackdropClick );
		this.closeButton?.addEventListener( 'click', this.handleCloseClick );
	}

	/**
	 * @returns {boolean}
	 */
	shouldUsePluginDrawer() {
		if ( overrideDiviNav() ) {
			return Boolean( this.container.querySelector( '.low-mm-menu-toggle' ) );
		}

		if ( this.container.querySelector( '.low-mm-menu-toggle' ) ) {
			const themeToggle = this.findThemeToggle();
			if ( themeToggle && themeToggle !== this.container.querySelector( '.low-mm-menu-toggle' ) ) {
				return false;
			}
			return true;
		}

		return ! this.findThemeToggle();
	}

	/**
	 * @returns {HTMLElement|null}
	 */
	findThemeToggle() {
		const header = this.container.closest( 'header, [role="banner"], .site-header' ) ?? document.body;

		for ( const selector of THEME_TOGGLE_SELECTORS ) {
			const toggle = header.querySelector( selector );
			if ( toggle instanceof HTMLElement && ! this.container.contains( toggle ) ) {
				return toggle;
			}
		}

		return null;
	}

	hidePluginToggle() {
		const toggle = this.container.querySelector( '.low-mm-menu-toggle' );
		if ( toggle instanceof HTMLElement ) {
			toggle.hidden = true;
		}
	}

	hideDiviMobileNav() {
		document.querySelectorAll( '#et_mobile_nav_menu, .mobile_menu_bar' ).forEach( ( element ) => {
			if ( element instanceof HTMLElement ) {
				element.remove();
			}
		} );

		document.body.classList.remove( 'et_pb_slide_menu_opened' );
	}

	handleToggleClick() {
		if ( this.isOpen() ) {
			this.close();
		} else {
			this.open();
		}
	}

	handleBackdropClick() {
		this.close();
	}

	handleCloseClick() {
		this.close();
	}

	open() {
		if ( ! isMobileViewport() || ! this.toggle || ! this.drawer ) {
			return;
		}

		if ( overrideDiviNav() ) {
			this.hideDiviMobileNav();
		}

		syncAdminBarOffset();
		this.container.classList.add( 'low-mm-drawer-open' );
		this.container.classList.remove( 'low-mm-mobile-closed' );
		this.drawer.setAttribute( 'aria-hidden', 'false' );
		this.toggle.setAttribute( 'aria-expanded', 'true' );
		document.body.classList.add( 'low-mm-mobile-nav-open' );
	}

	close() {
		if ( ! this.drawer || ! this.toggle ) {
			return;
		}

		this.container.classList.remove( 'low-mm-drawer-open' );
		this.container.classList.add( 'low-mm-mobile-closed' );
		this.drawer.setAttribute( 'aria-hidden', 'true' );
		this.toggle.setAttribute( 'aria-expanded', 'false' );
		document.body.classList.remove( 'low-mm-mobile-nav-open' );
		this.onClose?.();
	}

	/**
	 * @returns {boolean}
	 */
	isOpen() {
		return this.container.classList.contains( 'low-mm-drawer-open' );
	}

	destroy() {
		if ( this.toggle ) {
			this.toggle.removeEventListener( 'click', this.handleToggleClick );
		}
		this.backdrop?.removeEventListener( 'click', this.handleBackdropClick );
		this.closeButton?.removeEventListener( 'click', this.handleCloseClick );
	}
}

/**
 * @param {HTMLElement} container
 * @param {object} [options]
 * @returns {MobileDrawerController}
 */
export function createMobileDrawer( container, options = {} ) {
	return new MobileDrawerController( container, options );
}
