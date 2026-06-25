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

/**
 * @returns {boolean}
 */
export function searchEnabled() {
	return getConfig().searchEnabled === true;
}

/**
 * @returns {string}
 */
export function getSearchEndpoint() {
	const { searchEndpoint } = getConfig();
	return typeof searchEndpoint === 'string' ? searchEndpoint : '';
}

/**
 * @returns {string}
 */
export function getRestNonce() {
	const { restNonce } = getConfig();
	return typeof restNonce === 'string' ? restNonce : '';
}

/**
 * @returns {number}
 */
export function getSearchMinChars() {
	const { searchMinChars } = getConfig();
	return typeof searchMinChars === 'number' && searchMinChars > 0 ? searchMinChars : 2;
}

/**
 * @param {string} key
 * @param {string} fallback
 * @returns {string}
 */
export function getString( key, fallback ) {
	const strings = getConfig().i18n;
	if ( strings && typeof strings === 'object' && typeof strings[ key ] === 'string' ) {
		return strings[ key ];
	}
	return fallback;
}
