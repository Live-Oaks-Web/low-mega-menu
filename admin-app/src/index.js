import { createRoot, useEffect, useReducer, useCallback } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import Canvas from './components/Canvas';
import { fetchLayout, saveLayout, configureApi } from './api/menusApi';
import { layoutReducer, initialState, ACTIONS } from './state/layoutReducer';
import { syncIdCounter } from './utils/ids';
import { buildColumnsForPreset } from './utils/presets';
import './builder.scss';

function getErrorMessage( err, fallback ) {
	if ( ! err ) {
		return fallback;
	}
	if ( typeof err === 'string' ) {
		return err;
	}
	if ( err.message ) {
		return err.message;
	}
	if ( err.data?.message ) {
		return err.data.message;
	}
	return fallback;
}

function BuilderApp( { postId } ) {
	const [ state, dispatch ] = useReducer( layoutReducer, initialState );

	useEffect( () => {
		dispatch( { type: ACTIONS.SET_LOADING, isLoading: true } );
		fetchLayout( postId )
			.then( ( layout ) => {
				syncIdCounter( layout );
				let nextLayout = layout;
				let markDirty = false;

				if ( ! layout.columns || layout.columns.length === 0 ) {
					const built = buildColumnsForPreset( layout.layout_preset || '2-col' );
					nextLayout = { ...layout, ...built };
					markDirty = true;
				}

				dispatch( { type: ACTIONS.SET_LAYOUT, layout: nextLayout, markDirty } );
			} )
			.catch( ( err ) => {
				dispatch( {
					type: ACTIONS.SET_ERROR,
					error: getErrorMessage( err, 'Failed to load layout.' ),
				} );
				dispatch( { type: ACTIONS.SET_LOADING, isLoading: false } );
			} );
	}, [ postId ] );

	useEffect( () => {
		const handleBeforeUnload = ( event ) => {
			if ( ! state.isDirty ) {
				return;
			}
			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', handleBeforeUnload );
		return () => window.removeEventListener( 'beforeunload', handleBeforeUnload );
	}, [ state.isDirty ] );

	const handleSave = useCallback( () => {
		if ( ! state.layout ) {
			return;
		}
		dispatch( { type: ACTIONS.SET_SAVING, isSaving: true } );
		dispatch( { type: ACTIONS.SET_ERROR, error: null } );
		saveLayout( postId, state.layout )
			.then( ( layout ) => {
				dispatch( { type: ACTIONS.SET_LAYOUT, layout, keepSelection: true } );
			} )
			.catch( ( err ) => {
				dispatch( {
					type: ACTIONS.SET_ERROR,
					error: getErrorMessage( err, 'Failed to save layout.' ),
				} );
			} );
	}, [ postId, state.layout ] );

	if ( state.isLoading ) {
		return (
			<div className="low-mm-builder-loading">
				<Spinner />
				<p>Loading builder…</p>
			</div>
		);
	}

	return <Canvas state={ state } dispatch={ dispatch } onSave={ handleSave } />;
}

const rootEl = document.getElementById( 'low-mm-builder-root' );
const postId = window.lowMmBuilderData?.postId || parseInt( rootEl?.dataset.postId || '0', 10 );

configureApi();

if ( rootEl && postId ) {
	const root = createRoot( rootEl );
	root.render( <BuilderApp postId={ postId } /> );
}
