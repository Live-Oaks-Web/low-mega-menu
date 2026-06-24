import { useSortable, SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Button, TextControl, ToggleControl } from '@wordpress/components';
import { getModuleDefinition } from '../modules/moduleRegistry';
import AddModuleMenu from './AddModuleMenu';

function SortableModule( { module, isSelected, onSelect, onRemove } ) {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable( {
		id: module.id,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
	};

	const label = getModuleDefinition( module.type )?.label || module.type;

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ [
				'low-mm-module-card',
				isSelected ? 'is-selected' : '',
				isDragging ? 'is-dragging' : '',
				isOver ? 'is-drop-target' : '',
			]
				.filter( Boolean )
				.join( ' ' ) }
		>
			<button type="button" className="low-mm-module-card__handle" { ...attributes } { ...listeners }>
				⋮⋮
			</button>
			<button type="button" className="low-mm-module-card__label" onClick={ () => onSelect( module.id ) }>
				{ label } — Edit
			</button>
			<Button isDestructive variant="link" onClick={ () => onRemove( module.id ) }>
				Remove
			</Button>
		</div>
	);
}

export default function Column( {
	column,
	selectedModuleId,
	onUpdateColumn,
	onSelectModule,
	onRemoveModule,
	onAddModule,
} ) {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging, isOver } = useSortable( {
		id: column.id,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		flex: column.width_fraction || 1,
	};

	const isEmpty = ! column.modules || column.modules.length === 0;

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ [
				'low-mm-column',
				isDragging ? 'is-dragging' : '',
				isOver ? 'is-drop-target' : '',
				column.border_left ? 'has-border-left' : '',
				column.border_right ? 'has-border-right' : '',
			]
				.filter( Boolean )
				.join( ' ' ) }
		>
			<div className="low-mm-column__header">
				<button type="button" className="low-mm-column__handle" { ...attributes } { ...listeners }>
					⋮⋮
				</button>
				<TextControl
					label="Column label"
					hideLabelFromVision
					value={ column.label || '' }
					onChange={ ( value ) => onUpdateColumn( column.id, { label: value } ) }
				/>
			</div>
			<div className="low-mm-column__borders">
				<ToggleControl
					label="Left border"
					checked={ !! column.border_left }
					onChange={ ( value ) => onUpdateColumn( column.id, { border_left: value } ) }
				/>
				<ToggleControl
					label="Right border"
					checked={ !! column.border_right }
					onChange={ ( value ) => onUpdateColumn( column.id, { border_right: value } ) }
				/>
			</div>
			<SortableContext items={ column.modules.map( ( mod ) => mod.id ) } strategy={ verticalListSortingStrategy }>
				<div className="low-mm-column__modules">
					{ isEmpty && (
						<p className="low-mm-column__empty">
							No modules yet. Use Add module below.
						</p>
					) }
					{ column.modules.map( ( module ) => (
						<SortableModule
							key={ module.id }
							module={ module }
							isSelected={ selectedModuleId === module.id }
							onSelect={ onSelectModule }
							onRemove={ ( moduleId ) => onRemoveModule( column.id, moduleId ) }
						/>
					) ) }
				</div>
			</SortableContext>
			<div className="low-mm-column__add">
				<AddModuleMenu onAddModule={ onAddModule } />
			</div>
		</div>
	);
}
