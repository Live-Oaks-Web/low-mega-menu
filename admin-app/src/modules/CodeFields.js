import { SelectControl, TextareaControl } from '@wordpress/components';

export default function CodeFields( { settings, onChange } ) {
	return (
		<div className="low-mm-fields low-mm-code-fields">
			<TextareaControl
				label="HTML / shortcode content"
				value={ settings.content || '' }
				onChange={ ( value ) => onChange( { content: value } ) }
				rows={ 8 }
			/>
			<SelectControl
				label="Shortcode execution"
				value={ settings.shortcode_execution || 'inherit' }
				options={ [
					{ label: 'Inherit global setting (currently: off)', value: 'inherit' },
					{ label: 'On', value: 'on' },
					{ label: 'Off', value: 'off' },
				] }
				onChange={ ( value ) => onChange( { shortcode_execution: value } ) }
			/>
		</div>
	);
}
