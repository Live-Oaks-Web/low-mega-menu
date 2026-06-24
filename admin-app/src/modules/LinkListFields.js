import { Button, CheckboxControl, TextControl } from '@wordpress/components';
import { RichText } from '@wordpress/block-editor';

export default function LinkListFields( { settings, onChange } ) {
	const rows = settings.rows || [];
	const rowWarnings = rows.map( ( row, index ) => {
		const label = ( row.label || '' ).trim();
		const url = ( row.url || '' ).trim();
		return label && ! url ? index : -1;
	} );

	const updateRow = ( index, changes ) => {
		const nextRows = rows.map( ( row, rowIndex ) =>
			rowIndex === index ? { ...row, ...changes } : row
		);
		onChange( { rows: nextRows } );
	};

	const addRow = () => {
		onChange( {
			rows: [
				...rows,
				{ label: '', url: '', description: '', open_in_new_tab: false },
			],
		} );
	};

	const removeRow = ( index ) => {
		onChange( { rows: rows.filter( ( _, rowIndex ) => rowIndex !== index ) } );
	};

	return (
		<div className="low-mm-fields low-mm-link-list-fields">
			<CheckboxControl
				label="Plain text only for descriptions"
				checked={ !! settings.description_plain_text_only }
				onChange={ ( value ) => onChange( { description_plain_text_only: value } ) }
			/>
			{ rows.map( ( row, index ) => (
				<div key={ index } className="low-mm-link-list-fields__row">
					<TextControl
						label="Label"
						value={ row.label || '' }
						onChange={ ( value ) => updateRow( index, { label: value } ) }
					/>
					<TextControl
						label="URL"
						value={ row.url || '' }
						onChange={ ( value ) => updateRow( index, { url: value } ) }
						help={
							rowWarnings.includes( index )
								? 'Add a URL or remove the label.'
								: undefined
						}
					/>
					<label className="components-base-control__label">Description</label>
					<RichText
						tagName="div"
						value={ row.description || '' }
						onChange={ ( value ) => updateRow( index, { description: value } ) }
						allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
					/>
					<CheckboxControl
						label="Open in new tab"
						checked={ !! row.open_in_new_tab }
						onChange={ ( value ) => updateRow( index, { open_in_new_tab: value } ) }
					/>
					<Button isDestructive variant="secondary" onClick={ () => removeRow( index ) }>
						Remove row
					</Button>
				</div>
			) ) }
			<Button variant="secondary" onClick={ addRow }>
				Add row
			</Button>
		</div>
	);
}
