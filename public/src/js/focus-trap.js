const FOCUSABLE_SELECTOR =
	'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
export function getFocusableElements( container ) {
	return Array.from( container.querySelectorAll( FOCUSABLE_SELECTOR ) ).filter(
		( element ) => ! element.hasAttribute( 'disabled' ) && element.getAttribute( 'aria-hidden' ) !== 'true'
	);
}

/**
 * Move focus to the first interactive element inside a panel, or the panel itself.
 *
 * @param {HTMLElement} container
 */
export function focusFirstFocusable( container ) {
	const focusable = getFocusableElements( container );
	if ( focusable.length > 0 ) {
		focusable[ 0 ].focus();
		return;
	}

	container.setAttribute( 'tabindex', '-1' );
	container.focus();
}

/**
 * Trap Tab / Shift+Tab within a container while a panel is open.
 * Used on desktop and mobile — trapping is the default for overlay panels.
 *
 * @param {HTMLElement} container
 * @param {KeyboardEvent} event
 */
export function trapFocus( container, event ) {
	if ( event.key !== 'Tab' ) {
		return;
	}

	const focusable = getFocusableElements( container );
	if ( focusable.length === 0 ) {
		return;
	}

	const first = focusable[ 0 ];
	const last = focusable[ focusable.length - 1 ];

	if ( event.shiftKey && document.activeElement === first ) {
		event.preventDefault();
		last.focus();
	} else if ( ! event.shiftKey && document.activeElement === last ) {
		event.preventDefault();
		first.focus();
	}
}
