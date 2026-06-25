import { getAdminBarHeight } from './admin-bar';
import { overrideDiviNav } from './config';

/**
 * Apply mobile drill-down insets so the panel sits below the WP admin bar — or,
 * under Divi, below the full #main-header so it aligns with the open drawer.
 *
 * Under Divi the panel is nested inside the drawer panel, whose transform makes
 * it the containing block for this fixed panel. That drawer panel already starts
 * at the header bottom, so the drill panel just fills it from top:0 — applying
 * the header offset again would push it down onto the menu items.
 *
 * @param {HTMLElement} panel
 */
export function applyMobilePanelInsets( panel ) {
	if ( overrideDiviNav() ) {
		panel.style.top = '0px';
		panel.style.height = '100%';
		panel.classList.add( 'low-mm-panel--admin-bar-offset' );
		return;
	}

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
