/**
 * Client-side layout warnings — server validation remains authoritative.
 *
 * @param {import('../state/layoutReducer').LayoutState['layout']} layout
 * @returns {string[]}
 */
export function getLayoutWarnings( layout ) {
	if ( ! layout?.columns ) {
		return [];
	}

	/** @type {string[]} */
	const warnings = [];

	layout.columns.forEach( ( column ) => {
		column.modules.forEach( ( module ) => {
			const settings = module.settings || {};

			if ( module.type === 'link_list' ) {
				( settings.rows || [] ).forEach( ( row, index ) => {
					const label = ( row.label || '' ).trim();
					const url = ( row.url || '' ).trim();
					if ( label && ! url ) {
						warnings.push(
							`Link list row ${ index + 1 }: label is set but URL is empty.`
						);
					}
				} );
			}

			if ( module.type === 'image' && ! settings.attachment_id ) {
				warnings.push( 'Image module has no image selected — it will not display on the front end.' );
			}

			if ( module.type === 'cta' ) {
				const buttonLabel = ( settings.button_label || '' ).trim();
				const buttonUrl = ( settings.button_url || '' ).trim();
				if ( buttonLabel && ! buttonUrl ) {
					warnings.push( 'CTA module: button label is set but URL is empty.' );
				}
			}
		} );
	} );

	return warnings;
}

/**
 * @param {object} module
 * @returns {string[]}
 */
export function getModuleWarnings( module ) {
	if ( ! module ) {
		return [];
	}

	const settings = module.settings || {};
	/** @type {string[]} */
	const warnings = [];

	if ( module.type === 'link_list' ) {
		( settings.rows || [] ).forEach( ( row, index ) => {
			const label = ( row.label || '' ).trim();
			const url = ( row.url || '' ).trim();
			if ( label && ! url ) {
				warnings.push( `Row ${ index + 1 }: add a URL or remove the label.` );
			}
		} );
	}

	if ( module.type === 'image' && ! settings.attachment_id ) {
		warnings.push( 'Select an image for this module to display on the front end.' );
	}

	if ( module.type === 'cta' ) {
		const buttonLabel = ( settings.button_label || '' ).trim();
		const buttonUrl = ( settings.button_url || '' ).trim();
		if ( buttonLabel && ! buttonUrl ) {
			warnings.push( 'Add a button URL or remove the label.' );
		}
	}

	if ( module.type === 'post_query' ) {
		const taxonomy = ( settings.taxonomy || '' ).trim();
		const termId = parseInt( settings.term_id, 10 ) || 0;
		if ( taxonomy && termId <= 0 ) {
			warnings.push(
				'Taxonomy is set but no term is selected — the filter is ignored and all matching posts are shown.'
			);
		}
	}

	if ( module.type === 'scroll_to' ) {
		const postId = parseInt( settings.source_post_id, 10 ) || 0;
		const headingIndex = parseInt( settings.heading_index, 10 );
		if ( postId > 0 && ( ! Number.isFinite( headingIndex ) || headingIndex < 0 ) ) {
			warnings.push( 'Select a heading to enable this scroll link on the front end.' );
		}
	}

	return warnings;
}
