import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { MediaUpload } from '@wordpress/media-utils';
import { Button, CheckboxControl, TextControl } from '@wordpress/components';

function getMediaPreviewUrl( media ) {
	if ( ! media ) {
		return '';
	}

	return (
		media.sizes?.thumbnail?.url ||
		media.media_details?.sizes?.thumbnail?.source_url ||
		media.url ||
		media.source_url ||
		''
	);
}

export default function ImageFields( { settings, onChange } ) {
	const [ previewUrl, setPreviewUrl ] = useState( '' );
	const attachmentId = settings.attachment_id || 0;

	useEffect( () => {
		if ( ! attachmentId ) {
			setPreviewUrl( '' );
			return;
		}

		apiFetch( { path: `/wp/v2/media/${ attachmentId }` } )
			.then( ( media ) => {
				setPreviewUrl( getMediaPreviewUrl( media ) );
			} )
			.catch( () => {
				setPreviewUrl( '' );
			} );
	}, [ attachmentId ] );

	const clearImage = () => {
		onChange( { attachment_id: 0, alt_text: '' } );
		setPreviewUrl( '' );
	};

	return (
		<div className="low-mm-fields low-mm-image-fields">
			<div className="low-mm-image-fields__picker">
				{ previewUrl ? (
					<div className="low-mm-image-fields__preview">
						<img src={ previewUrl } alt="" />
					</div>
				) : null }
				<div className="low-mm-image-fields__actions">
					<MediaUpload
						onSelect={ ( media ) => {
							setPreviewUrl( getMediaPreviewUrl( media ) );
							onChange( {
								attachment_id: media.id,
								alt_text: media.alt || '',
							} );
						} }
						allowedTypes={ [ 'image' ] }
						value={ attachmentId }
						render={ ( { open } ) => (
							<Button variant="secondary" onClick={ open }>
								{ attachmentId ? 'Replace image' : 'Select image' }
							</Button>
						) }
					/>
					{ attachmentId ? (
						<Button variant="link" isDestructive onClick={ clearImage }>
							Remove image
						</Button>
					) : null }
				</div>
			</div>
			<TextControl
				label="Alt text"
				value={ settings.alt_text || '' }
				onChange={ ( value ) => onChange( { alt_text: value } ) }
			/>
			<TextControl
				label="Link URL"
				value={ settings.link_url || '' }
				onChange={ ( value ) => onChange( { link_url: value } ) }
			/>
			<CheckboxControl
				label="Open in new tab"
				checked={ !! settings.open_in_new_tab }
				onChange={ ( value ) => onChange( { open_in_new_tab: value } ) }
			/>
		</div>
	);
}
