/** @type {(() => void) | null} */
let closeHandler = null;

/**
 * Register the function that closes all mega menu UI (desktop panels, mobile drawer, drill-down).
 *
 * @param {() => void} handler
 */
export function setNavCloseHandler( handler ) {
	closeHandler = handler;
}

/**
 * Close mega menu navigation before same-page scroll targets.
 */
export function closeNavUi() {
	if ( closeHandler ) {
		closeHandler();
	}
}
