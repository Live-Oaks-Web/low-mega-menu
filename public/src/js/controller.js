import { isMobileViewport } from './constants';
import { initScrollTo } from './scroll-to';
import { initAdminBarOffset, syncAdminBarOffset } from './admin-bar';
import { discoverNavContainers, resolvePanelTrigger } from './discover-containers';
import { DrilldownController } from './drilldown';
import { initHamburgerCoexistence } from './hamburger-coexistence';
import { createMobileDrawer } from './mobile-drawer';
import { setNavCloseHandler } from './nav-ui';
import { resetMobilePanelInsets } from './mobile-panel-insets';
import { getScrollTargets, resetDesktopPanelPosition } from './panel-position';
import { PanelController } from './panel';

/** @type {PanelController|null} */
let currentOpenPanel = null;

/** @type {DrilldownController[]} */
const drilldownControllers = [];

/** @type {PanelController[]} */
const panelControllers = [];

/** @type {import('./mobile-drawer').MobileDrawerController[]} */
const mobileDrawers = [];

/** @type {Set<Window | Element>} */
const activeScrollTargets = new Set();

/**
 * @param {PanelController} panel
 */
function handlePanelRequestOpen( panel ) {
	if ( currentOpenPanel && currentOpenPanel !== panel ) {
		currentOpenPanel.close();
	}
	currentOpenPanel = panel;
}

/**
 * @param {PanelController} panel
 */
function handlePanelClose( panel ) {
	if ( currentOpenPanel === panel ) {
		currentOpenPanel = null;
		unbindScrollTracking();
	}
}

/**
 * @param {PanelController} panel
 */
function handlePanelOpen( panel ) {
	bindScrollTracking( panel.container );
}

/**
 * @param {HTMLElement} container
 */
function bindScrollTracking( container ) {
	unbindScrollTracking();

	getScrollTargets( container ).forEach( ( target ) => {
		target.addEventListener( 'scroll', handleScrollActivity, { passive: true, capture: true } );
		activeScrollTargets.add( target );
	} );
}

function unbindScrollTracking() {
	activeScrollTargets.forEach( ( target ) => {
		target.removeEventListener( 'scroll', handleScrollActivity, { capture: true } );
	} );
	activeScrollTargets.clear();
}

/**
 * @param {HTMLElement} container
 */
function initContainer( container ) {
	if ( isMobileViewport() ) {
		container.classList.add( 'low-mm-mobile-closed' );
	}

	const drawer = createMobileDrawer( container, {
		onClose: resetAllDrilldowns,
	} );
	mobileDrawers.push( drawer );

	const drilldown = new DrilldownController( container );
	drilldownControllers.push( drilldown );

	container.querySelectorAll( '.low-mm-nav-item--has-panel, li.low-mm-has-panel' ).forEach( ( item ) => {
		if ( ! ( item instanceof HTMLLIElement ) ) {
			return;
		}

		const resolved = resolvePanelTrigger( item );
		if ( ! resolved ) {
			return;
		}

		const { trigger, panel } = resolved;

		const controller = new PanelController( {
			trigger,
			panel,
			item,
			container,
			onRequestOpen: handlePanelRequestOpen,
			onClose: handlePanelClose,
			onScrollActivity: handlePanelOpen,
		} );

		panelControllers.push( controller );
	} );
}

function closeAllDesktopPanels() {
	panelControllers.forEach( ( panel ) => {
		if ( panel.isOpen() ) {
			panel.close();
		}
	} );
	currentOpenPanel = null;
	unbindScrollTracking();
}

function closeAllMobileDrawers() {
	mobileDrawers.forEach( ( drawer ) => drawer.close() );
}

function resetAllDrilldowns() {
	drilldownControllers.forEach( ( drilldown ) => drilldown.reset() );
}

/**
 * Ensure any open panel is hidden even if controller state is out of sync.
 */
function forceCloseOpenPanels() {
	document
		.querySelectorAll( '.low-mm-panel.low-mm-panel-open, .low-mm-panel.is-open, .low-mm-panel:not([hidden])' )
		.forEach( ( panel ) => {
			if ( ! ( panel instanceof HTMLElement ) ) {
				return;
			}

			resetDesktopPanelPosition( panel );
			resetMobilePanelInsets( panel );
			panel.setAttribute( 'hidden', '' );
			panel.classList.remove( 'low-mm-panel-open', 'is-open', 'low-mm-panel--desktop-fixed' );
			panel.removeAttribute( 'aria-modal' );

			const header = panel.querySelector( '.low-mm-mobile-header' );
			if ( header ) {
				header.setAttribute( 'aria-hidden', 'true' );
			}
		} );

	document
		.querySelectorAll( '.low-mm-nav-container [aria-expanded="true"]' )
		.forEach( ( trigger ) => {
			if ( trigger instanceof HTMLElement ) {
				trigger.setAttribute( 'aria-expanded', 'false' );
			}
		} );

	document.querySelectorAll( '.low-mm-trigger-active, .low-mm-drill-active' ).forEach( ( element ) => {
		element.classList.remove( 'low-mm-trigger-active', 'low-mm-drill-active' );
	} );

	document.querySelectorAll( '.low-mm-nav-container' ).forEach( ( container ) => {
		if ( container instanceof HTMLElement ) {
			container.classList.remove( 'low-mm-mobile-drill-active', 'low-mm-drawer-open' );
			container.classList.add( 'low-mm-mobile-closed' );
		}
	} );
}

function closeAllNavUi() {
	closeAllDesktopPanels();
	resetAllDrilldowns();
	closeAllMobileDrawers();
	forceCloseOpenPanels();
	document.body.classList.remove( 'low-mm-mobile-nav-open' );
}

function handleDocumentClick( event ) {
	if ( isMobileViewport() || ! currentOpenPanel ) {
		return;
	}

	if ( currentOpenPanel.containsTarget( event.target ) ) {
		return;
	}

	currentOpenPanel.close();
	currentOpenPanel = null;
}

function handleDocumentKeydown( event ) {
	if ( event.key !== 'Escape' ) {
		return;
	}

	const activeDrilldown = drilldownControllers.find( ( drilldown ) => drilldown.isDrilledIn() );
	if ( activeDrilldown ) {
		activeDrilldown.drillOut();
		return;
	}

	const openDrawer = mobileDrawers.find( ( drawer ) => drawer.isOpen() );
	if ( openDrawer ) {
		openDrawer.close();
		return;
	}

	if ( currentOpenPanel ) {
		currentOpenPanel.close();
		currentOpenPanel = null;
	}
}

function handleViewportChange() {
	document.querySelectorAll( '.low-mm-nav-container' ).forEach( ( container ) => {
		if ( isMobileViewport() ) {
			container.classList.add( 'low-mm-mobile-closed' );
		} else {
			container.classList.remove( 'low-mm-mobile-closed', 'low-mm-drawer-open' );
		}
	} );

	if ( isMobileViewport() ) {
		closeAllDesktopPanels();
	} else {
		resetAllDrilldowns();
		closeAllMobileDrawers();
		document.body.classList.remove( 'low-mm-mobile-nav-open' );
	}
}

/** @type {number|null} */
let scrollFrame = null;

function handleScrollActivity() {
	if ( isMobileViewport() ) {
		syncAdminBarOffset();
		repositionActiveMobileDrilldown();
	}

	if ( ! currentOpenPanel || isMobileViewport() ) {
		return;
	}

	if ( scrollFrame !== null ) {
		cancelAnimationFrame( scrollFrame );
	}

	scrollFrame = requestAnimationFrame( () => {
		scrollFrame = null;

		if ( ! currentOpenPanel ) {
			return;
		}

		if ( currentOpenPanel.handleScroll() ) {
			currentOpenPanel.close();
			currentOpenPanel = null;
		}
	} );
}

function handleResize() {
	syncAdminBarOffset();

	if ( ! currentOpenPanel || isMobileViewport() ) {
		repositionActiveMobileDrilldown();
		return;
	}

	currentOpenPanel.reposition();
}

function repositionActiveMobileDrilldown() {
	if ( ! isMobileViewport() ) {
		return;
	}

	const activeDrilldown = drilldownControllers.find( ( drilldown ) => drilldown.isDrilledIn() );
	activeDrilldown?.repositionActivePanel?.();
}

function init() {
	initAdminBarOffset();

	discoverNavContainers().forEach( ( container ) => {
		initContainer( container );
	} );

	setNavCloseHandler( closeAllNavUi );
	initScrollTo();

	document.addEventListener( 'click', handleDocumentClick );
	document.addEventListener( 'keydown', handleDocumentKeydown );
	window.addEventListener( 'resize', handleResize, { passive: true } );

	initHamburgerCoexistence( {
		onThemeMenuClose: () => {
			resetAllDrilldowns();
			closeAllMobileDrawers();
		},
		onViewportChange: handleViewportChange,
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
