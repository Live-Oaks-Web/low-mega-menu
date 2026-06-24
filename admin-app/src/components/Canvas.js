import { useMemo, useState } from '@wordpress/element';
import {
	DndContext,
	DragOverlay,
	closestCenter,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	arrayMove,
	horizontalListSortingStrategy,
} from '@dnd-kit/sortable';
import Column from './Column';
import ColumnPresetPicker from './ColumnPresetPicker';
import ModulePalette from './ModulePalette';
import PanelSettingsSidebar from './PanelSettingsSidebar';
import ModuleSettingsPanel from './ModuleSettingsPanel';
import SaveBar from './SaveBar';
import { ACTIONS } from '../state/layoutReducer';
import { generateId } from '../utils/ids';
import { getDefaultSettings, getModuleDefinition } from '../modules/moduleRegistry';
import { getLayoutWarnings } from '../utils/validation';

export default function Canvas( { state, dispatch, onSave } ) {
	const sensors = useSensors( useSensor( PointerSensor ) );
	const { layout, isDirty, isSaving, error, selectedModuleId } = state;
	const [ activeDragId, setActiveDragId ] = useState( null );

	const warnings = useMemo( () => getLayoutWarnings( layout ), [ layout ] );

	const selectedModule = useMemo( () => {
		if ( ! layout || ! selectedModuleId ) {
			return null;
		}
		for ( const column of layout.columns ) {
			const found = column.modules.find( ( mod ) => mod.id === selectedModuleId );
			if ( found ) {
				return found;
			}
		}
		return null;
	}, [ layout, selectedModuleId ] );

	if ( ! layout ) {
		return <p>Loading layout…</p>;
	}

	const activeDragLabel = useMemo( () => {
		if ( ! activeDragId || ! layout ) {
			return '';
		}

		const column = layout.columns.find( ( col ) => col.id === activeDragId );
		if ( column ) {
			return column.label || 'Column';
		}

		for ( const col of layout.columns ) {
			const module = col.modules.find( ( mod ) => mod.id === activeDragId );
			if ( module ) {
				return getModuleDefinition( module.type )?.label || module.type;
			}
		}

		return '';
	}, [ activeDragId, layout ] );

	const handleDragEnd = ( event ) => {
		setActiveDragId( null );
		const { active, over } = event;
		if ( ! over || active.id === over.id ) {
			return;
		}

		const activeColumn = layout.columns.find(
			( column ) =>
				column.id === active.id || column.modules.some( ( mod ) => mod.id === active.id )
		);
		const overColumn = layout.columns.find(
			( column ) =>
				column.id === over.id || column.modules.some( ( mod ) => mod.id === over.id )
		);

		if ( ! activeColumn || ! overColumn ) {
			return;
		}

		if ( activeColumn.id === active.id && overColumn.id === over.id ) {
			const oldIndex = layout.columns.findIndex( ( column ) => column.id === active.id );
			const newIndex = layout.columns.findIndex( ( column ) => column.id === over.id );
			dispatch( {
				type: ACTIONS.REORDER_COLUMNS,
				columns: arrayMove( layout.columns, oldIndex, newIndex ),
			} );
			return;
		}

		if ( activeColumn.id === overColumn.id ) {
			const oldIndex = activeColumn.modules.findIndex( ( mod ) => mod.id === active.id );
			const newIndex = activeColumn.modules.findIndex( ( mod ) => mod.id === over.id );
			dispatch( {
				type: ACTIONS.REORDER_MODULES,
				columnId: activeColumn.id,
				modules: arrayMove( activeColumn.modules, oldIndex, newIndex ),
			} );
		}
	};

	const addModuleToColumn = ( columnId, type ) => {
		dispatch( {
			type: ACTIONS.ADD_MODULE,
			columnId,
			module: {
				id: generateId( 'mod' ),
				type,
				settings: getDefaultSettings( type ),
			},
		} );
	};

	return (
		<div className="low-mm-builder">
			<SaveBar
				isDirty={ isDirty }
				isSaving={ isSaving }
				error={ error }
				warnings={ warnings }
				onSave={ onSave }
			/>
			<ColumnPresetPicker
				currentPreset={ layout.layout_preset }
				columns={ layout.columns }
				onSelectPreset={ ( preset ) =>
					dispatch( { type: ACTIONS.APPLY_PRESET, preset } )
				}
			/>
			<ModulePalette />
			<div className="low-mm-builder__body">
				<DndContext
					sensors={ sensors }
					collisionDetection={ closestCenter }
					onDragStart={ ( event ) => setActiveDragId( event.active.id ) }
					onDragEnd={ handleDragEnd }
				>
					<SortableContext
						items={ layout.columns.map( ( column ) => column.id ) }
						strategy={ horizontalListSortingStrategy }
					>
						<div className="low-mm-builder__columns">
							{ layout.columns.map( ( column ) => (
								<Column
									key={ column.id }
									column={ column }
									selectedModuleId={ selectedModuleId }
									onUpdateColumn={ ( columnId, changes ) =>
										dispatch( {
											type: ACTIONS.UPDATE_COLUMN,
											columnId,
											changes,
										} )
									}
									onSelectModule={ ( moduleId ) =>
										dispatch( { type: ACTIONS.SELECT_MODULE, moduleId } )
									}
									onRemoveModule={ ( columnId, moduleId ) =>
										dispatch( {
											type: ACTIONS.REMOVE_MODULE,
											columnId,
											moduleId,
										} )
									}
									onAddModule={ ( type ) => addModuleToColumn( column.id, type ) }
								/>
							) ) }
						</div>
					</SortableContext>
					<DragOverlay>
						{ activeDragLabel ? (
							<div className="low-mm-drag-overlay">{ activeDragLabel }</div>
						) : null }
					</DragOverlay>
				</DndContext>
				<aside className="low-mm-builder__sidebar">
					<PanelSettingsSidebar
						panelSettings={ layout.panel_settings }
						onChange={ ( settings ) =>
							dispatch( { type: ACTIONS.UPDATE_PANEL_SETTINGS, settings } )
						}
					/>
				</aside>
			</div>
			<ModuleSettingsPanel
				module={ selectedModule }
				onClose={ () => dispatch( { type: ACTIONS.SELECT_MODULE, moduleId: null } ) }
				onChange={ ( settings ) => {
					if ( ! selectedModule ) {
						return;
					}
					dispatch( {
						type: ACTIONS.UPDATE_MODULE_SETTINGS,
						moduleId: selectedModule.id,
						settings,
					} );
				} }
			/>
		</div>
	);
}
