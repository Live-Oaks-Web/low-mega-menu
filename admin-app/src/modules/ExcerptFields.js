import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, SelectControl, TextControl, Spinner } from '@wordpress/components';

export default function ExcerptFields( { settings, onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ isSearching, setIsSearching ] = useState( false );

	useEffect( () => {
		if ( search.length < 2 ) {
			setResults( [] );
			return undefined;
		}

		const timer = setTimeout( () => {
			setIsSearching( true );
			apiFetch( {
				path: `/wp/v2/search?search=${ encodeURIComponent( search ) }&subtype=post,page&per_page=10`,
			} )
				.then( ( items ) => setResults( items ) )
				.catch( () => setResults( [] ) )
				.finally( () => setIsSearching( false ) );
		}, 300 );

		return () => clearTimeout( timer );
	}, [ search ] );

	const options = [
		{ label: 'Select a page or post…', value: '0' },
		...results.map( ( item ) => ( {
			label: item.title,
			value: String( item.id ),
		} ) ),
	];

	if ( settings.source_post_id && ! options.find( ( opt ) => opt.value === String( settings.source_post_id ) ) ) {
		options.push( {
			label: `Selected #${ settings.source_post_id }`,
			value: String( settings.source_post_id ),
		} );
	}

	return (
		<div className="low-mm-fields low-mm-excerpt-fields">
			<TextControl
				label="Search content"
				value={ search }
				onChange={ setSearch }
				help="Type at least two characters to search pages and posts."
			/>
			{ isSearching && <Spinner /> }
			<SelectControl
				label="Source content"
				value={ String( settings.source_post_id || 0 ) }
				options={ options }
				onChange={ ( value ) => onChange( { source_post_id: parseInt( value, 10 ) || 0 } ) }
			/>
			<CheckboxControl
				label="Show featured image"
				checked={ !! settings.show_image }
				onChange={ ( value ) => onChange( { show_image: value } ) }
			/>
			<CheckboxControl
				label="Show excerpt"
				checked={ !! settings.show_excerpt }
				onChange={ ( value ) => onChange( { show_excerpt: value } ) }
			/>
			<TextControl
				label="Excerpt length override"
				type="number"
				value={ String( settings.excerpt_length || 0 ) }
				onChange={ ( value ) => onChange( { excerpt_length: parseInt( value, 10 ) || 0 } ) }
				help="0 uses the site default word count."
			/>
			<CheckboxControl
				label="Use full rendered content instead of excerpt"
				checked={ !! settings.rich_text_override }
				onChange={ ( value ) => onChange( { rich_text_override: value } ) }
			/>
		</div>
	);
}
