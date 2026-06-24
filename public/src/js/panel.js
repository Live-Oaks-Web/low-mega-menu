import { useAriaExpanded } from './config';
import { isMobileViewport } from './constants';
import { focusFirstFocusable, trapFocus } from './focus-trap';
import {
	applyDesktopPanelPosition,
	isNavAnchorInView,
	resetDesktopPanelPosition,
	updateViewportPanelPosition,
} from './panel-position';

/**
 * Desktop click-to-open panel controller for a single nav item.
 */
export class PanelController {
	/**
	 * @param {object} options
	 * @param {HTMLAnchorElement} options.trigger
	 * @param {HTMLElement} options.panel
	 * @param {HTMLLIElement} options.item
	 * @param {HTMLElement} options.container
	 * @param {(panel: PanelController) => void} [options.onRequestOpen]
	 * @param {(panel: PanelController) => void} [options.onClose]
	 * @param {(panel: PanelController) => void} [options.onScrollActivity]
	 */
	constructor( { trigger, panel, item, container, onRequestOpen, onClose, onScrollActivity } ) {
		this.trigger = trigger;
		this.panel = panel;
		this.item = item;
		this.container = container;
		this.onRequestOpen = onRequestOpen;
		this.onClose = onClose;
		this.onScrollActivity = onScrollActivity;
		this.focusTrapHandler = ( event ) => trapFocus( this.panel, event );
		this.handleTriggerClick = this.handleTriggerClick.bind( this );
		/** @type {string|null} */
		this.positionMode = null;

		if ( useAriaExpanded() && ! this.trigger.hasAttribute( 'aria-expanded' ) ) {
			this.trigger.setAttribute( 'aria-expanded', 'false' );
		}

		this.panel.setAttribute( 'role', 'region' );
		const panelId = this.panel.getAttribute( 'id' );
		if ( useAriaExpanded() && panelId ) {
			this.trigger.setAttribute( 'aria-controls', panelId );
		}
		this.trigger.addEventListener( 'click', this.handleTriggerClick, true );
	}

	/**
	 * @param {MouseEvent} event
	 */
	handleTriggerClick( event ) {
		if ( isMobileViewport() ) {
			return;
		}

		event.preventDefault();

		if ( this.isOpen() ) {
			this.close();
			return;
		}

		this.onRequestOpen?.( this );
		this.open();
	}

	open() {
		this.panel.removeAttribute( 'hidden' );
		this.panel.classList.add( 'low-mm-panel-open', 'is-open' );
		this.item.classList.add( 'low-mm-trigger-active' );
		if ( useAriaExpanded() ) {
			this.trigger.setAttribute( 'aria-expanded', 'true' );
		}
		this.panel.addEventListener( 'keydown', this.focusTrapHandler );
		this.positionMode = applyDesktopPanelPosition( this.panel, this.container );
		this.onScrollActivity?.( this );
		focusFirstFocusable( this.panel );
	}

	reposition() {
		if ( ! this.isOpen() || isMobileViewport() ) {
			return;
		}

		updateViewportPanelPosition( this.panel, this.container );
	}

	/**
	 * @returns {boolean} Whether the panel should close after scroll.
	 */
	handleScroll() {
		if ( ! this.isOpen() || isMobileViewport() ) {
			return false;
		}

		if ( ! isNavAnchorInView( this.container ) ) {
			return true;
		}

		this.reposition();
		return false;
	}

	close() {
		if ( ! this.isOpen() ) {
			return;
		}

		resetDesktopPanelPosition( this.panel );
		this.positionMode = null;
		this.panel.setAttribute( 'hidden', '' );
		this.panel.classList.remove( 'low-mm-panel-open', 'is-open' );
		this.item.classList.remove( 'low-mm-trigger-active' );
		if ( useAriaExpanded() ) {
			this.trigger.setAttribute( 'aria-expanded', 'false' );
		}
		this.panel.removeEventListener( 'keydown', this.focusTrapHandler );
		this.trigger.focus();
		this.onClose?.( this );
	}

	/**
	 * @returns {boolean}
	 */
	isOpen() {
		return ! this.panel.hasAttribute( 'hidden' );
	}

	/**
	 * @param {EventTarget|null} target
	 * @returns {boolean}
	 */
	containsTarget( target ) {
		if ( ! ( target instanceof Node ) ) {
			return false;
		}

		return this.item.contains( target ) || this.panel.contains( target );
	}

	destroy() {
		this.trigger.removeEventListener( 'click', this.handleTriggerClick, true );
		this.panel.removeEventListener( 'keydown', this.focusTrapHandler );
	}
}
