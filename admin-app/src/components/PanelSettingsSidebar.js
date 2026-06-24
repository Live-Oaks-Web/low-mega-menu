import { Panel, PanelBody, SelectControl, RangeControl, ColorPalette } from '@wordpress/components';

export default function PanelSettingsSidebar( { panelSettings, onChange } ) {
	const settings = panelSettings || {};

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
				<p className="components-base-control__label">Background</p>
				<ColorPalette
					value={ settings.background || '#ffffff' }
					onChange={ ( value ) => onChange( { background: value || '#ffffff' } ) }
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
