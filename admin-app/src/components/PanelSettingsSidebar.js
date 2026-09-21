import { MediaUpload } from '@wordpress/media-utils';
import {
	Button,
	Panel,
	PanelBody,
	SelectControl,
	RangeControl,
	ColorPalette,
} from '@wordpress/components';

export default function PanelSettingsSidebar( { panelSettings, onChange } ) {
	const settings = panelSettings || {};
	const backgroundMode = settings.background_mode || 'color';

	return (
		<Panel className="low-mm-panel-settings">
			<PanelBody title="Panel settings" initialOpen>
				<SelectControl
					label="Max width"
					value={ settings.max_width || 'default' }
					options={ [
						{ label: 'Default', value: 'default' },
						{ label: 'Full width', value: 'full' },
						{ label: 'Custom', value: 'custom' },
					] }
					onChange={ ( value ) => onChange( { max_width: value } ) }
				/>
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
							value={ settings.background || '#ffffff' }
							onChange={ ( value ) => onChange( { background: value || '#ffffff' } ) }
						/>
					</div>
				) : (
					<MediaUpload
						onSelect={ ( media ) => onChange( { background_image_id: media.id } ) }
						allowedTypes={ [ 'image' ] }
						value={ settings.background_image_id || 0 }
						render={ ( { open } ) => (
							<div>
								<Button variant="secondary" onClick={ open }>
									{ settings.background_image_id
										? 'Replace background image'
										: 'Select background image' }
								</Button>
								{ !! settings.background_image_id && (
									<Button
										variant="link"
										isDestructive
										onClick={ () => onChange( { background_image_id: 0 } ) }
										style={ { marginLeft: '0.5rem' } }
									>
										Remove
									</Button>
								) }
							</div>
						) }
					/>
				) }
				<SelectControl
					label="Animation"
					value={ settings.animation || 'fade' }
					options={ [
						{ label: 'Fade', value: 'fade' },
						{ label: 'Slide down', value: 'slide-down' },
						{ label: 'None', value: 'none' },
					] }
					onChange={ ( value ) => onChange( { animation: value } ) }
				/>
				<RangeControl
					label="Animation speed (ms)"
					value={ settings.animation_speed_ms || 200 }
					onChange={ ( value ) => onChange( { animation_speed_ms: value } ) }
					min={ 100 }
					max={ 600 }
				/>
			</PanelBody>
		</Panel>
	);
}
