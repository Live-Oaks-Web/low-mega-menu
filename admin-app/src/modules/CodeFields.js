import { SelectControl } from '@wordpress/components';
import WysiwygControl from '../components/WysiwygControl';

export default function CodeFields( { settings, onChange } ) {
	return (
		<div className="low-mm-fields low-mm-code-fields">
			<WysiwygControl
				label="HTML / shortcode content"
				value={ settings.content || '' }
				onChange={ ( value ) => onChange( { content: value } ) }
				defaultMode="code"
				rows={ 8 }
				help="Defaults to the code view so shortcodes and raw HTML stay intact. Switch off the code toggle for a visual editor."
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
