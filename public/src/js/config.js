/**
 * @returns {Record<string, unknown>}
 */
export function getConfig() {
	return window.lowMmPublicConfig || {};
}

/**
 * @returns {boolean}
 */
export function useAriaExpanded() {
	return getConfig().useAriaExpanded === true;
}

/**
 * @returns {boolean}
 */
export function overrideDiviNav() {
	return getConfig().overrideDiviNav === true;
}
