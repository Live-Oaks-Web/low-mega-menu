import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, SelectControl, TextControl } from '@wordpress/components';

export default function PostQueryFields( { settings, onChange } ) {
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ taxonomies, setTaxonomies ] = useState( [] );
	const [ taxonomyRestBases, setTaxonomyRestBases ] = useState( {} );
	const [ terms, setTerms ] = useState( [] );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/types' } ).then( ( types ) => {
			const options = Object.values( types )
				.filter( ( type ) => type.viewable )
				.map( ( type ) => ( { label: type.name, value: type.slug } ) );
			setPostTypes( options );
		} );
	}, [] );

	useEffect( () => {
		if ( ! settings.post_type ) {
			setTaxonomies( [] );
			return;
		}

		apiFetch( { path: `/wp/v2/taxonomies?type=${ settings.post_type }` } ).then( ( items ) => {
			const restBases = {};
			const options = Object.values( items ).map( ( taxonomy ) => {
				restBases[ taxonomy.slug ] = taxonomy.rest_base || taxonomy.slug;
				return {
					label: taxonomy.name,
					value: taxonomy.slug,
				};
			} );
			setTaxonomyRestBases( restBases );
			setTaxonomies( [ { label: '— None —', value: '' }, ...options ] );
		} );
	}, [ settings.post_type ] );

	useEffect( () => {
		if ( ! settings.taxonomy ) {
			setTerms( [] );
			return;
		}

		const restBase = taxonomyRestBases[ settings.taxonomy ];
		if ( ! restBase ) {
			return;
		}

		apiFetch( { path: `/wp/v2/${ restBase }?per_page=100` } )
			.then( ( items ) => {
				setTerms( [
					{ label: '— None —', value: '0' },
					...items.map( ( term ) => ( { label: term.name, value: String( term.id ) } ) ),
				] );
			} )
			.catch( () => {
				setTerms( [ { label: '— None —', value: '0' } ] );
			} );
	}, [ settings.taxonomy, taxonomyRestBases ] );

	return (
		<div className="low-mm-fields low-mm-post-query-fields">
			<SelectControl
				label="Post type"
				value={ settings.post_type || 'post' }
				options={ postTypes.length ? postTypes : [ { label: 'Post', value: 'post' } ] }
				onChange={ ( value ) =>
					onChange( { post_type: value, taxonomy: '', term_id: 0 } )
				}
			/>
			<SelectControl
				label="Taxonomy"
				value={ settings.taxonomy || '' }
				options={ taxonomies.length ? taxonomies : [ { label: '— None —', value: '' } ] }
				onChange={ ( value ) => onChange( { taxonomy: value, term_id: 0 } ) }
			/>
			<SelectControl
				label="Term"
				value={ String( settings.term_id || 0 ) }
				options={ terms.length ? terms : [ { label: '— None —', value: '0' } ] }
				onChange={ ( value ) => onChange( { term_id: parseInt( value, 10 ) || 0 } ) }
			/>
			<SelectControl
				label="Sort"
				value={ settings.sort || 'newest' }
				options={ [
					{ label: 'Newest first', value: 'newest' },
					{ label: 'Oldest first', value: 'oldest' },
					{ label: 'Sticky first', value: 'sticky_first' },
					{ label: 'Title A–Z', value: 'title' },
				] }
				onChange={ ( value ) => onChange( { sort: value } ) }
			/>
			<TextControl
				label="Count"
				type="number"
				value={ String( settings.count || 5 ) }
				onChange={ ( value ) => onChange( { count: parseInt( value, 10 ) || 1 } ) }
			/>
			<TextControl
				label="Offset"
				type="number"
				value={ String( settings.offset || 0 ) }
				onChange={ ( value ) => onChange( { offset: parseInt( value, 10 ) || 0 } ) }
			/>
			<CheckboxControl
				label="Show image"
				checked={ !! settings.show_image }
				onChange={ ( value ) => onChange( { show_image: value } ) }
			/>
			<CheckboxControl
				label="Show date"
				checked={ !! settings.show_date }
				onChange={ ( value ) => onChange( { show_date: value } ) }
			/>
			<CheckboxControl
				label="Show category label"
				checked={ !! settings.show_category_label }
				onChange={ ( value ) => onChange( { show_category_label: value } ) }
			/>
			<CheckboxControl
				label="Show excerpt"
				checked={ !! settings.show_excerpt }
				onChange={ ( value ) => onChange( { show_excerpt: value } ) }
			/>
			<TextControl
				label="View all label"
				value={ settings.view_all_label || '' }
				onChange={ ( value ) => onChange( { view_all_label: value } ) }
			/>
			<TextControl
				label="View all URL"
				value={ settings.view_all_url || '' }
				onChange={ ( value ) => onChange( { view_all_url: value } ) }
			/>
		</div>
	);
}
