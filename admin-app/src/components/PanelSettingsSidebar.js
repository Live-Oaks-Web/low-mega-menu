import {
	Panel,
	PanelBody,
	SelectControl,
	RangeControl,
	ColorPalette,
} from '@wordpress/components';
import BackgroundImagePicker from './BackgroundImagePicker';

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
					<BackgroundImagePicker
						imageId={ settings.background_image_id || 0 }
						onChange={ ( id ) => onChange( { background_image_id: id } ) }
					/>
				) }
				<RangeControl
					label="Padding top (px)"
					value={
						typeof settings.padding_top === 'number'
							? settings.padding_top
							: 32
					}
					onChange={ ( value ) =>
						onChange( { padding_top: typeof value === 'number' ? value : 32 } )
					}
					min={ 0 }
					max={ 200 }
				/>
				<RangeControl
					label="Padding right (px)"
					value={
						typeof settings.padding_right === 'number'
							? settings.padding_right
							: 24
					}
					onChange={ ( value ) =>
						onChange( { padding_right: typeof value === 'number' ? value : 24 } )
					}
					min={ 0 }
					max={ 200 }
				/>
				<RangeControl
					label="Padding bottom (px)"
					value={
						typeof settings.padding_bottom === 'number'
							? settings.padding_bottom
							: 32
					}
					onChange={ ( value ) =>
						onChange( { padding_bottom: typeof value === 'number' ? value : 32 } )
					}
					min={ 0 }
					max={ 200 }
				/>
				<RangeControl
					label="Padding left (px)"
					value={
						typeof settings.padding_left === 'number'
							? settings.padding_left
							: 24
					}
					onChange={ ( value ) =>
						onChange( { padding_left: typeof value === 'number' ? value : 24 } )
					}
					min={ 0 }
					max={ 200 }
				/>
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
