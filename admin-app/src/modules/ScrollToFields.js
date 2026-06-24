import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { decodeEntities } from '@wordpress/html-entities';
import { Button, SelectControl, Spinner, TextControl, TextareaControl } from '@wordpress/components';

/**
 * @param {object} props
 * @param {Record<string, unknown>} props.settings
 * @param {(changes: Record<string, unknown>) => void} props.onChange
 */
export default function ScrollToFields( { settings, onChange } ) {
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ isSearching, setIsSearching ] = useState( false );
	const [ selectedPostTitle, setSelectedPostTitle ] = useState( '' );
	const [ headings, setHeadings ] = useState( [] );
	const [ isLoadingHeadings, setIsLoadingHeadings ] = useState( false );
	const [ headingsError, setHeadingsError ] = useState( '' );

	const postType = settings.post_type || 'page';
	const sourcePostId = parseInt( settings.source_post_id, 10 ) || 0;
	const headingIndex = parseInt( settings.heading_index, 10 );
	const hasSelectedPost = sourcePostId > 0;

	useEffect( () => {
		apiFetch( { path: '/wp/v2/types' } ).then( ( types ) => {
			const options = Object.values( types )
				.filter( ( type ) => type.viewable )
				.map( ( type ) => ( { label: type.name, value: type.slug } ) );
			setPostTypes( options );
		} );
	}, [] );

	// Step 1: search published content by title within the chosen post type.
	useEffect( () => {
		if ( search.trim().length < 2 ) {
			setSearchResults( [] );
			return undefined;
		}

		const timer = setTimeout( () => {
			setIsSearching( true );
			apiFetch( {
				path: `/wp/v2/search?search=${ encodeURIComponent(
					search.trim()
				) }&subtype=${ encodeURIComponent( postType ) }&per_page=20`,
			} )
				.then( ( items ) => setSearchResults( Array.isArray( items ) ? items : [] ) )
				.catch( () => setSearchResults( [] ) )
				.finally( () => setIsSearching( false ) );
		}, 300 );

		return () => clearTimeout( timer );
	}, [ search, postType ] );

	// Resolve the selected post title when reopening saved settings.
	useEffect( () => {
		if ( sourcePostId <= 0 ) {
			setSelectedPostTitle( '' );
			return undefined;
		}

		let cancelled = false;

		apiFetch( {
			path: `/wp/v2/${ postType }/${ sourcePostId }?_fields=id,title`,
		} )
			.then( ( post ) => {
				if ( cancelled ) {
					return;
				}
				const title = post?.title?.rendered
					? decodeEntities( post.title.rendered )
					: '';
				setSelectedPostTitle( title || `Content #${ sourcePostId }` );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setSelectedPostTitle( `Content #${ sourcePostId }` );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ sourcePostId, postType ] );

	// Step 2: load headings only after a post has been chosen.
	useEffect( () => {
		if ( sourcePostId <= 0 ) {
			setHeadings( [] );
			setHeadingsError( '' );
			return undefined;
		}

		let cancelled = false;
		setIsLoadingHeadings( true );
		setHeadingsError( '' );

		apiFetch( { path: `/low-mm/v1/headings/${ sourcePostId }` } )
			.then( ( response ) => {
				if ( cancelled ) {
					return;
				}
				const items = response.headings || [];
				setHeadings( items );
				if ( ! items.length ) {
					setHeadingsError( 'No headings found in this content.' );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setHeadings( [] );
					setHeadingsError( 'Could not load headings for this content.' );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setIsLoadingHeadings( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ sourcePostId ] );

	const selectPost = ( postId, title ) => {
		setSelectedPostTitle( title );
		setSearch( '' );
		setSearchResults( [] );
		onChange( {
			source_post_id: postId,
			heading_index: -1,
			title: '',
		} );
	};

	const clearPost = () => {
		setSelectedPostTitle( '' );
		setSearch( '' );
		setSearchResults( [] );
		setHeadings( [] );
		setHeadingsError( '' );
		onChange( {
			source_post_id: 0,
			heading_index: -1,
			title: '',
		} );
	};

	const headingOptions = [
		{ label: 'Select a heading…', value: '-1' },
		...headings.map( ( heading ) => ( {
			label: `${ heading.tag.toUpperCase() }: ${ heading.text }`,
			value: String( heading.index ),
		} ) ),
	];

	const selectedHeading = headings.find( ( heading ) => heading.index === headingIndex );

	return (
		<div className="low-mm-fields low-mm-scroll-to-fields">
			<SelectControl
				label="Post type"
				value={ postType }
				options={
					postTypes.length ? postTypes : [ { label: 'Page', value: 'page' } ]
				}
				onChange={ ( value ) => {
					setSearch( '' );
					setSearchResults( [] );
					setSelectedPostTitle( '' );
					setHeadings( [] );
					onChange( {
						post_type: value,
						source_post_id: 0,
						heading_index: -1,
						title: '',
					} );
				} }
			/>

			{ ! hasSelectedPost && (
				<>
					<TextControl
						label="Search by title"
						value={ search }
						onChange={ setSearch }
						help="Type at least two characters, then pick a result below."
					/>
					{ isSearching && <Spinner /> }
					{ search.trim().length >= 2 && ! isSearching && searchResults.length === 0 && (
						<p className="low-mm-scroll-to-fields__notice">No matching content found.</p>
					) }
					{ searchResults.length > 0 && (
						<ul className="low-mm-scroll-to-fields__results">
							{ searchResults.map( ( item ) => (
								<li key={ item.id }>
									<Button
										variant="secondary"
										onClick={ () => selectPost( item.id, decodeEntities( item.title ) ) }
									>
										{ decodeEntities( item.title ) }
									</Button>
								</li>
							) ) }
						</ul>
					) }
				</>
			) }

			{ hasSelectedPost && (
				<>
					<div className="low-mm-scroll-to-fields__selected">
						<p>
							<strong>Selected:</strong> { selectedPostTitle }
						</p>
						<Button variant="link" onClick={ clearPost }>
							Choose different content
						</Button>
					</div>

					{ isLoadingHeadings && <Spinner /> }
					{ headingsError && (
						<p className="low-mm-scroll-to-fields__notice">{ headingsError }</p>
					) }

					{ ! isLoadingHeadings && headings.length > 0 && (
						<SelectControl
							label="Scroll to heading"
							value={ Number.isFinite( headingIndex ) ? String( headingIndex ) : '-1' }
							options={ headingOptions }
							onChange={ ( value ) => {
								const index = parseInt( value, 10 );
								const heading = headings.find( ( item ) => item.index === index );
								const changes = {
									heading_index: Number.isFinite( index ) ? index : -1,
								};

								if ( heading && ! ( settings.title || '' ).trim() ) {
									changes.title = heading.text;
								}

								onChange( changes );
							} }
						/>
					) }

					{ selectedHeading && (
						<p className="low-mm-scroll-to-fields__help">
							Anchor: <code>{ selectedHeading.anchor_id }</code>
						</p>
					) }

					<TextControl
						label="Menu title"
						value={ settings.title || '' }
						onChange={ ( value ) => onChange( { title: value } ) }
						help="Shown in the mega menu. Defaults to the heading text when empty."
					/>
					<TextareaControl
						label="Description"
						value={ settings.content || '' }
						onChange={ ( value ) => onChange( { content: value } ) }
						help="Optional supporting text below the title."
					/>
				</>
			) }
		</div>
	);
}
