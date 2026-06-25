import { MediaUpload } from '@wordpress/media-utils';
import {
	Button,
	CheckboxControl,
	SelectControl,
	TextControl,
	ColorPalette,
} from '@wordpress/components';
import WysiwygControl from '../components/WysiwygControl';

export default function CtaFields( { settings, onChange } ) {
	const backgroundMode = settings.background_mode || 'color';

	return (
		<div className="low-mm-fields low-mm-cta-fields">
			<TextControl
				label="Heading"
				value={ settings.heading || '' }
				onChange={ ( value ) => onChange( { heading: value } ) }
			/>
			<WysiwygControl
				label="Body"
				value={ settings.body || '' }
				onChange={ ( value ) => onChange( { body: value } ) }
			/>
			<CheckboxControl
				label="Plain text only for body"
				checked={ !! settings.body_plain_text_only }
				onChange={ ( value ) => onChange( { body_plain_text_only: value } ) }
			/>
			<div>
				<p className="components-base-control__label">Text color (heading &amp; body)</p>
				<ColorPalette
					value={ settings.text_color || '' }
					onChange={ ( value ) => onChange( { text_color: value || '' } ) }
				/>
			</div>
			<TextControl
				label="Button label"
				value={ settings.button_label || '' }
				onChange={ ( value ) => onChange( { button_label: value } ) }
			/>
			<TextControl
				label="Button URL"
				value={ settings.button_url || '' }
				onChange={ ( value ) => onChange( { button_url: value } ) }
			/>
			<div>
				<p className="components-base-control__label">Button text color</p>
				<ColorPalette
					value={ settings.button_text_color || '' }
					onChange={ ( value ) => onChange( { button_text_color: value || '' } ) }
				/>
			</div>
			<div>
				<p className="components-base-control__label">Button background color</p>
				<ColorPalette
					value={ settings.button_background_color || '' }
					onChange={ ( value ) => onChange( { button_background_color: value || '' } ) }
				/>
			</div>
			<SelectControl
				label="Background"
				value={ backgroundMode }
				options={ [
					{ label: 'Color', value: 'color' },
					{ label: 'Image', value: 'image' },
				] }
				onChange={ ( value ) => onChange( { background_mode: value } ) }
			/>
			{ backgroundMode === 'color' ? (
				<div>
					<p className="components-base-control__label">Background color</p>
					<ColorPalette
						value={ settings.background_color || '#f5f5f5' }
						onChange={ ( value ) => onChange( { background_color: value || '#f5f5f5' } ) }
					/>
				</div>
			) : (
				<MediaUpload
					onSelect={ ( media ) => onChange( { background_image_id: media.id } ) }
					allowedTypes={ [ 'image' ] }
					value={ settings.background_image_id || 0 }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ settings.background_image_id ? 'Replace background image' : 'Select background image' }
						</Button>
					) }
				/>
			) }
			<SelectControl
				label="Alignment"
				value={ settings.alignment || 'left' }
				options={ [
					{ label: 'Left', value: 'left' },
					{ label: 'Center', value: 'center' },
					{ label: 'Right', value: 'right' },
				] }
				onChange={ ( value ) => onChange( { alignment: value } ) }
			/>
		</div>
	);
}
