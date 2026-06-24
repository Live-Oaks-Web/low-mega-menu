import { useAriaExpanded } from './config';
import { isMobileViewport } from './constants';
import { resolvePanelTrigger } from './discover-containers';
import { focusFirstFocusable, trapFocus } from './focus-trap';
import { applyMobilePanelInsets, resetMobilePanelInsets } from './mobile-panel-insets';

/**
 * Mobile drill-down screen stack — distinct from desktop panel open/close.
 */
export class DrilldownController {
	/**
	 * @param {HTMLElement} container
	 */
	constructor( container ) {
		this.container = container;
		this.activeDrill = null;
		this.focusTrapHandler = null;
		this.handleClick = this.handleClick.bind( this );
		this.container.addEventListener( 'click', this.handleClick );
	}

	/**
	 * @param {MouseEvent} event
	 */
	handleClick( event ) {
		if ( ! isMobileViewport() ) {
			return;
		}

		const backButton = event.target.closest( '.low-mm-back' );
		if ( backButton && this.container.contains( backButton ) ) {
			event.preventDefault();
			this.drillOut();
			return;
		}

		const link = event.target.closest( 'a[data-low-mega-menu], .low-mm-nav-item--has-panel > a, .low-mm-has-panel > a' );
		if ( ! link || ! this.container.contains( link ) ) {
			return;
		}

		event.preventDefault();
		const item = link.closest( 'li' );
		if ( ! ( item instanceof HTMLLIElement ) ) {
			return;
		}

		const resolved = resolvePanelTrigger( item );
		if ( ! resolved ) {
			return;
		}

		this.drillIn( item );
	}

	/**
	 * @param {HTMLLIElement} item
	 */
	drillIn( item ) {
		const resolved = resolvePanelTrigger( item );
		if ( ! resolved ) {
			return;
		}

		const { panel, trigger } = resolved;

		this.drillOut( false );

		this.activeDrill = { item, panel, trigger };
		this.container.classList.add( 'low-mm-mobile-drill-active' );
		item.classList.add( 'low-mm-drill-active' );
		panel.removeAttribute( 'hidden' );
		panel.classList.add( 'low-mm-panel-open', 'is-open' );
		applyMobilePanelInsets( panel );
		if ( useAriaExpanded() ) {
			trigger.setAttribute( 'aria-expanded', 'true' );
		}
		panel.setAttribute( 'aria-modal', 'true' );

		const parentLabel = panel.getAttribute( 'data-low-mm-parent-label' );
		if ( parentLabel ) {
			panel.setAttribute( 'aria-label', parentLabel );
		}

		const header = panel.querySelector( '.low-mm-mobile-header' );
		if ( header ) {
			header.setAttribute( 'aria-hidden', 'false' );
		}

		const backButton = panel.querySelector( '.low-mm-back' );
		if ( backButton ) {
			backButton.setAttribute( 'tabindex', '0' );
		}

		this.focusTrapHandler = ( keyboardEvent ) => trapFocus( panel, keyboardEvent );
		panel.addEventListener( 'keydown', this.focusTrapHandler );
		focusFirstFocusable( panel );
	}

	/**
	 * @param {boolean} [returnFocus=true]
	 */
	drillOut( returnFocus = true ) {
		if ( ! this.activeDrill ) {
			return;
		}

		const { item, panel, trigger } = this.activeDrill;

		panel.setAttribute( 'hidden', '' );
		panel.classList.remove( 'low-mm-panel-open', 'is-open' );
		resetMobilePanelInsets( panel );
		item.classList.remove( 'low-mm-drill-active' );
		this.container.classList.remove( 'low-mm-mobile-drill-active' );
		if ( useAriaExpanded() ) {
			trigger.setAttribute( 'aria-expanded', 'false' );
		}
		panel.removeAttribute( 'aria-modal' );

		const header = panel.querySelector( '.low-mm-mobile-header' );
		if ( header ) {
			header.setAttribute( 'aria-hidden', 'true' );
		}

		const backButton = panel.querySelector( '.low-mm-back' );
		if ( backButton ) {
			backButton.setAttribute( 'tabindex', '-1' );
		}

		if ( this.focusTrapHandler ) {
			panel.removeEventListener( 'keydown', this.focusTrapHandler );
			this.focusTrapHandler = null;
		}

		if ( returnFocus ) {
			trigger.focus();
		}

		this.activeDrill = null;
	}

	/**
	 * @returns {boolean}
	 */
	isDrilledIn() {
		return this.activeDrill !== null;
	}

	/**
	 * @param {EventTarget|null} target
	 * @returns {boolean}
	 */
	containsTarget( target ) {
		if ( ! this.activeDrill || ! ( target instanceof Node ) ) {
			return false;
		}

		return this.activeDrill.item.contains( target );
	}

	reset() {
		this.drillOut( false );
	}

	repositionActivePanel() {
		if ( ! this.activeDrill ) {
			return;
		}

		applyMobilePanelInsets( this.activeDrill.panel );
	}

	destroy() {
		this.drillOut( false );
		this.container.removeEventListener( 'click', this.handleClick );
	}
}
