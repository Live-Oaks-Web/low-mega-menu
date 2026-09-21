import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { MediaUpload } from '@wordpress/media-utils';
import { Button } from '@wordpress/components';

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

/**
 * Background image picker with a tiny thumbnail when an attachment is set.
 *
 * @param {Object}   props
 * @param {number}   props.imageId   Attachment ID (0 = none).
 * @param {Function} props.onChange  Called with next attachment ID.
 */
export default function BackgroundImagePicker( { imageId = 0, onChange } ) {
	const [ previewUrl, setPreviewUrl ] = useState( '' );
	const attachmentId = imageId || 0;

	useEffect( () => {
		if ( ! attachmentId ) {
			setPreviewUrl( '' );
			return;
		}

		let cancelled = false;

		apiFetch( { path: `/wp/v2/media/${ attachmentId }` } )
			.then( ( media ) => {
				if ( ! cancelled ) {
					setPreviewUrl( getMediaPreviewUrl( media ) );
				}
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setPreviewUrl( '' );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ attachmentId ] );

	return (
		<div className="low-mm-bg-image-picker">
			<MediaUpload
				onSelect={ ( media ) => {
					setPreviewUrl( getMediaPreviewUrl( media ) );
					onChange( media.id );
				} }
				allowedTypes={ [ 'image' ] }
				value={ attachmentId }
				render={ ( { open } ) => (
					<div className="low-mm-bg-image-picker__row">
						{ previewUrl ? (
							<button
								type="button"
								className="low-mm-bg-image-picker__thumb"
								onClick={ open }
								aria-label="Replace background image"
							>
								<img src={ previewUrl } alt="" />
							</button>
						) : null }
						<div className="low-mm-bg-image-picker__actions">
							<Button variant="secondary" onClick={ open }>
								{ attachmentId
									? 'Replace background image'
									: 'Select background image' }
							</Button>
							{ attachmentId ? (
								<Button
									variant="link"
									isDestructive
									onClick={ () => {
										setPreviewUrl( '' );
										onChange( 0 );
									} }
								>
									Remove
								</Button>
							) : null }
						</div>
					</div>
				) }
			/>
		</div>
	);
}
