/**
 * Find nav roots that contain mega menu panels (theme-agnostic).
 *
 * @returns {HTMLElement[]}
 */
export function discoverNavContainers() {
	/** @type {Set<HTMLElement>} */
	const containers = new Set();

	document.querySelectorAll( '.low-mm-nav-container' ).forEach( ( node ) => {
		if ( node instanceof HTMLElement ) {
			containers.add( node );
		}
	} );

	document.querySelectorAll( '.low-mm-panel' ).forEach( ( panel ) => {
		if ( ! ( panel instanceof HTMLElement ) ) {
			return;
		}

		const container =
			panel.closest( '.low-mm-nav-container' ) ??
			panel.closest( 'nav' ) ??
			panel.closest( '.et_pb_menu, .et_pb_fullwidth_menu, [class*="et_pb_menu"]' ) ??
			panel.closest( 'ul' )?.parentElement ??
			panel.parentElement;

		if ( container instanceof HTMLElement ) {
			container.classList.add( 'low-mm-nav-container' );
			if ( ! container.classList.contains( 'low-mega-menu' ) ) {
				container.classList.add( 'low-mega-menu' );
			}
			containers.add( container );
		}
	} );

	return Array.from( containers );
}

/**
 * Resolve trigger link and panel for a mega menu list item.
 *
 * @param {HTMLLIElement} item
 * @returns {{ trigger: HTMLAnchorElement, panel: HTMLElement }|null}
 */
export function resolvePanelTrigger( item ) {
	const panel =
		item.querySelector( ':scope > .low-mm-panel' ) ?? item.querySelector( '.low-mm-panel' );

	if ( ! ( panel instanceof HTMLElement ) ) {
		return null;
	}

	const trigger =
		item.querySelector( ':scope > a[data-low-mega-menu]' ) ??
		item.querySelector( 'a[data-low-mega-menu]' ) ??
		item.querySelector( ':scope > a' ) ??
		item.querySelector( 'a' );

	if ( ! ( trigger instanceof HTMLAnchorElement ) ) {
		return null;
	}

	return { trigger, panel };
}
