import { Button, Notice } from '@wordpress/components';

export default function SaveBar( { isDirty, isSaving, error, warnings = [], onSave } ) {
	return (
		<div className="low-mm-save-bar">
			{ isDirty && (
				<Notice status="warning" isDismissible={ false }>
					Unsaved changes
				</Notice>
			) }
			{ warnings.map( ( warning ) => (
				<Notice key={ warning } status="warning" isDismissible={ false }>
					{ warning }
				</Notice>
			) ) }
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Button variant="primary" onClick={ onSave } isBusy={ isSaving } disabled={ isSaving || ! isDirty }>
				{ isSaving ? 'Saving…' : 'Save' }
			</Button>
		</div>
	);
}
