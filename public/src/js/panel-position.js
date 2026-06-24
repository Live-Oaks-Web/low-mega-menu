import { getAdminBarMinTop } from './admin-bar';

/** @type {(keyof CSSStyleDeclaration)[]} */
const INLINE_POSITION_PROPS = [
	'position',
	'top',
	'left',
	'right',
	'width',
	'maxWidth',
	'marginLeft',
	'height',
];

/** Header is fixed/sticky — panel top stays stable while scrolling. */
export const POSITION_MODE_PINNED = 'pinned';

/** Header scrolls with the page — panel top is updated on scroll to follow it. */
export const POSITION_MODE_SCROLL = 'scroll';

/**
 * Prefer the full site header as the vertical anchor so panels drop below
 * the header bar, not below a narrow inline nav cluster.
 *
 * @param {HTMLElement} container Plugin nav container.
 * @returns {HTMLElement}
 */
export function findPanelAnchor( container ) {
	const selectors = [
		'header',
		'[role="banner"]',
		'.site-header',
	];

	for ( const selector of selectors ) {
		const anchor = container.closest( selector );
		if ( anchor instanceof HTMLElement ) {
			return anchor;
		}
	}

	return container;
}

/**
 * Whether the nav/header is pinned to the viewport (fixed/sticky).
 *
 * @param {HTMLElement} container
 * @returns {boolean}
 */
export function usesViewportPinnedNav( container ) {
	const anchor = findPanelAnchor( container );
	let node = anchor;

	while ( node && node !== document.documentElement ) {
		const { position } = window.getComputedStyle( node );
		if ( position === 'fixed' || position === 'sticky' ) {
			return true;
		}
		node = node.parentElement;
	}

	return false;
}

/**
 * @param {HTMLElement} container
 * @returns {typeof POSITION_MODE_PINNED | typeof POSITION_MODE_SCROLL}
 */
export function getDesktopPositionMode( container ) {
	return usesViewportPinnedNav( container ) ? POSITION_MODE_PINNED : POSITION_MODE_SCROLL;
}

/**
 * @param {HTMLElement} container
 * @returns {boolean}
 */
export function isNavAnchorInView( container ) {
	const anchor = findPanelAnchor( container );
	const rect = anchor.getBoundingClientRect();
	const minTop = getAdminBarMinTop();

	return rect.bottom > minTop && rect.top < window.innerHeight;
}

/**
 * Full-width viewport positioning below the header anchor.
 * Used for every desktop panel so width never inherits a narrow <li>.
 *
 * @param {HTMLElement} panel
 * @param {HTMLElement} container
 */
export function positionViewportPanel( panel, container ) {
	const anchor = findPanelAnchor( container );
	const rect = anchor.getBoundingClientRect();
	const minTop = getAdminBarMinTop();

	panel.style.position = 'fixed';
	panel.style.top = `${ Math.max( minTop, Math.round( rect.bottom ) ) }px`;
	panel.style.left = '0';
	panel.style.right = '0';
	panel.style.width = '100%';
	panel.style.maxWidth = 'none';
	panel.style.marginLeft = '0';
	panel.style.height = '';
}

/**
 * @param {HTMLElement} panel
 * @param {HTMLElement} container
 * @returns {typeof POSITION_MODE_PINNED | typeof POSITION_MODE_SCROLL}
 */
export function applyDesktopPanelPosition( panel, container ) {
	resetDesktopPanelPosition( panel );

	const mode = getDesktopPositionMode( container );
	panel.dataset.lowMmPositionMode = mode;
	panel.classList.add( 'low-mm-panel--desktop-fixed' );
	positionViewportPanel( panel, container );

	return mode;
}

/**
 * @param {HTMLElement} panel
 * @param {HTMLElement} container
 */
export function updateViewportPanelPosition( panel, container ) {
	positionViewportPanel( panel, container );
}

/**
 * @param {HTMLElement} panel
 */
export function resetDesktopPanelPosition( panel ) {
	panel.classList.remove( 'low-mm-panel--desktop-fixed' );
	delete panel.dataset.lowMmPositionMode;

	INLINE_POSITION_PROPS.forEach( ( prop ) => {
		panel.style[ prop ] = '';
	} );
}

/**
 * @param {HTMLElement} container
 * @returns {(Window | Element)[]}
 */
export function getScrollTargets( container ) {
	/** @type {(Window | Element)[]} */
	const targets = [ window ];
	let node = container.parentElement;

	while ( node ) {
		const style = window.getComputedStyle( node );
		const overflowY = style.overflowY;
		const overflow = style.overflow;

		if (
			/(auto|scroll|overlay)/.test( overflowY ) ||
			/(auto|scroll|overlay)/.test( overflow )
		) {
			targets.push( node );
		}

		node = node.parentElement;
	}

	return targets;
}
