import apiFetch from '@wordpress/api-fetch';

let configured = false;

export function configureApi() {
	if ( configured || ! window.lowMmBuilderData ) {
		return;
	}

	apiFetch.use( apiFetch.createNonceMiddleware( window.lowMmBuilderData.restNonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( window.lowMmBuilderData.restUrl ) );
	configured = true;
}

export function fetchLayout( postId ) {
	configureApi();
	return apiFetch( { path: `/low-mm/v1/menus/${ postId }` } );
}

export function saveLayout( postId, layout ) {
	configureApi();
	return apiFetch( {
		path: `/low-mm/v1/menus/${ postId }`,
		method: 'PATCH',
		data: layout,
	} );
}
