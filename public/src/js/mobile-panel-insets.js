import { getAdminBarHeight } from './admin-bar';

/**
 * Apply mobile drill-down insets so the panel sits below the WP admin bar.
 *
 * @param {HTMLElement} panel
 */
export function applyMobilePanelInsets( panel ) {
	const offset = getAdminBarHeight();

	if ( offset > 0 ) {
		panel.style.top = `${ offset }px`;
		panel.style.height = `calc(100% - ${ offset }px)`;
		panel.classList.add( 'low-mm-panel--admin-bar-offset' );
	} else {
		resetMobilePanelInsets( panel );
	}
}

/**
 * @param {HTMLElement} panel
 */
export function resetMobilePanelInsets( panel ) {
	panel.classList.remove( 'low-mm-panel--admin-bar-offset' );
	panel.style.top = '';
	panel.style.height = '';
}
