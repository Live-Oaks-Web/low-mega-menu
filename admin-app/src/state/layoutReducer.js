import { buildColumnsForPreset } from '../utils/presets';

export const ACTIONS = {
	SET_LAYOUT: 'SET_LAYOUT',
	SET_LOADING: 'SET_LOADING',
	SET_SAVING: 'SET_SAVING',
	SET_ERROR: 'SET_ERROR',
	UPDATE_PANEL_SETTINGS: 'UPDATE_PANEL_SETTINGS',
	APPLY_PRESET: 'APPLY_PRESET',
	REORDER_COLUMNS: 'REORDER_COLUMNS',
	UPDATE_COLUMN: 'UPDATE_COLUMN',
	REORDER_MODULES: 'REORDER_MODULES',
	ADD_MODULE: 'ADD_MODULE',
	REMOVE_MODULE: 'REMOVE_MODULE',
	UPDATE_MODULE_SETTINGS: 'UPDATE_MODULE_SETTINGS',
	SELECT_MODULE: 'SELECT_MODULE',
	MARK_CLEAN: 'MARK_CLEAN',
};

export const initialState = {
	layout: null,
	isLoading: true,
	isSaving: false,
	isDirty: false,
	error: null,
	selectedModuleId: null,
};

function withDirty( layout ) {
	return {
		layout,
		isDirty: true,
		error: null,
	};
}

export function layoutReducer( state, action ) {
	switch ( action.type ) {
		case ACTIONS.SET_LOADING:
			return { ...state, isLoading: action.isLoading };

		case ACTIONS.SET_SAVING:
			return { ...state, isSaving: action.isSaving };

		case ACTIONS.SET_ERROR:
			return { ...state, error: action.error, isSaving: false };

		case ACTIONS.SET_LAYOUT:
			return {
				...state,
				layout: action.layout,
				isLoading: false,
				isDirty: action.markDirty ?? false,
				isSaving: false,
				error: null,
				selectedModuleId: action.keepSelection ? state.selectedModuleId : null,
			};

		case ACTIONS.MARK_CLEAN:
			return { ...state, isDirty: false, isSaving: false, error: null };

		case ACTIONS.UPDATE_PANEL_SETTINGS:
			return {
				...state,
				...withDirty( {
					...state.layout,
					panel_settings: {
						...state.layout.panel_settings,
						...action.settings,
					},
				} ),
			};

		case ACTIONS.APPLY_PRESET: {
			const built = buildColumnsForPreset( action.preset, state.layout.columns || [] );
			return {
				...state,
				...withDirty( {
					...state.layout,
					layout_preset: action.preset,
					columns: built.columns,
					mobile_order: built.mobile_order,
				} ),
			};
		}

		case ACTIONS.REORDER_COLUMNS:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: action.columns,
					mobile_order: action.columns.map( ( column ) => column.id ),
				} ),
			};

		case ACTIONS.UPDATE_COLUMN:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: state.layout.columns.map( ( column ) =>
						column.id === action.columnId ? { ...column, ...action.changes } : column
					),
				} ),
			};

		case ACTIONS.REORDER_MODULES:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: state.layout.columns.map( ( column ) =>
						column.id === action.columnId
							? { ...column, modules: action.modules }
							: column
					),
				} ),
			};

		case ACTIONS.ADD_MODULE:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: state.layout.columns.map( ( column ) =>
						column.id === action.columnId
							? { ...column, modules: [ ...column.modules, action.module ] }
							: column
					),
				} ),
				selectedModuleId: action.module.id,
			};

		case ACTIONS.REMOVE_MODULE:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: state.layout.columns.map( ( column ) =>
						column.id === action.columnId
							? {
									...column,
									modules: column.modules.filter(
										( mod ) => mod.id !== action.moduleId
									),
							  }
							: column
					),
				} ),
				selectedModuleId:
					state.selectedModuleId === action.moduleId ? null : state.selectedModuleId,
			};

		case ACTIONS.UPDATE_MODULE_SETTINGS:
			return {
				...state,
				...withDirty( {
					...state.layout,
					columns: state.layout.columns.map( ( column ) => ( {
						...column,
						modules: column.modules.map( ( mod ) =>
							mod.id === action.moduleId
								? { ...mod, settings: { ...mod.settings, ...action.settings } }
								: mod
						),
					} ) ),
				} ),
			};

		case ACTIONS.SELECT_MODULE:
			return { ...state, selectedModuleId: action.moduleId };

		default:
			return state;
	}
}
