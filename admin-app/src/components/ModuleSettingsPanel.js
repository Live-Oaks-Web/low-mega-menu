import { Modal, Notice } from '@wordpress/components';
import { getModuleDefinition } from '../modules/moduleRegistry';
import { getModuleWarnings } from '../utils/validation';

export default function ModuleSettingsPanel( { module, onChange, onClose } ) {
	if ( ! module ) {
		return null;
	}

	const definition = getModuleDefinition( module.type );
	const FieldsComponent = definition?.component;
	const title = definition?.label || module.type;
	const warnings = getModuleWarnings( module );

	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			className="low-mm-module-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="low-mm-module-settings">
				{ warnings.map( ( warning ) => (
					<Notice key={ warning } status="warning" isDismissible={ false }>
						{ warning }
					</Notice>
				) ) }
				{ FieldsComponent ? (
					<FieldsComponent
						settings={ module.settings || {} }
						onChange={ ( changes ) => onChange( changes ) }
					/>
				) : (
					<p>No settings UI registered for this module type.</p>
				) }
			</div>
		</Modal>
	);
}
