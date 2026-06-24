import { useState } from '@wordpress/element';
import { Button, ButtonGroup, Modal } from '@wordpress/components';
import { getRemovedColumnsWithModules } from '../utils/presets';

const PRESETS = [
	{ key: '2-col', label: '2 columns' },
	{ key: '3-col', label: '3 columns' },
	{ key: '3-col-widget', label: '3 columns + widget' },
	{ key: '4-col', label: '4 columns' },
];

export default function ColumnPresetPicker( { currentPreset, columns, onSelectPreset } ) {
	const [ pendingPreset, setPendingPreset ] = useState( null );

	const requestPreset = ( preset ) => {
		const removed = getRemovedColumnsWithModules( columns, preset );
		if ( removed.length > 0 ) {
			setPendingPreset( preset );
			return;
		}
		onSelectPreset( preset );
	};

	const confirmPreset = () => {
		if ( pendingPreset ) {
			onSelectPreset( pendingPreset );
			setPendingPreset( null );
		}
	};

	return (
		<div className="low-mm-preset-picker">
			<h2>Layout preset</h2>
			<ButtonGroup>
				{ PRESETS.map( ( preset ) => (
					<Button
						key={ preset.key }
						variant={ currentPreset === preset.key ? 'primary' : 'secondary' }
						onClick={ () => requestPreset( preset.key ) }
					>
						{ preset.label }
					</Button>
				) ) }
			</ButtonGroup>
			{ pendingPreset && (
				<Modal
					title="Switch layout preset?"
					onRequestClose={ () => setPendingPreset( null ) }
				>
					<p>
						Columns beyond this preset contain modules. Switching presets will remove them.
					</p>
					<Button variant="primary" onClick={ confirmPreset }>
						Switch preset
					</Button>
				</Modal>
			) }
		</div>
	);
}
