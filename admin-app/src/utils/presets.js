import { generateId } from '../utils/ids';

/**
 * Column width fractions per preset.
 * 3-col-widget: two equal content columns + narrower right widget column (OpenSecrets donor lookup pattern).
 */
export const PRESET_DEFINITIONS = {
	'2-col': { fractions: [ 1, 1 ] },
	'3-col': { fractions: [ 1, 1, 1 ] },
	'3-col-widget': { fractions: [ 1, 1, 0.75 ] },
	'4-col': { fractions: [ 1, 1, 1, 1 ] },
};

export function createEmptyColumn( fraction, index ) {
	return {
		id: generateId( 'col' ),
		label: `COLUMN ${ index + 1 }`,
		width_fraction: fraction,
		modules: [],
	};
}

export function buildColumnsForPreset( preset, existingColumns = [] ) {
	const definition = PRESET_DEFINITIONS[ preset ] || PRESET_DEFINITIONS[ '2-col' ];
	const targetCount = definition.fractions.length;
	const nextColumns = [];

	for ( let i = 0; i < targetCount; i += 1 ) {
		const fraction = definition.fractions[ i ];
		if ( existingColumns[ i ] ) {
			nextColumns.push( {
				...existingColumns[ i ],
				width_fraction: fraction,
			} );
		} else {
			nextColumns.push( createEmptyColumn( fraction, i ) );
		}
	}

	return {
		columns: nextColumns,
		mobile_order: nextColumns.map( ( column ) => column.id ),
	};
}

export function getRemovedColumnsWithModules( existingColumns, preset ) {
	const targetCount = ( PRESET_DEFINITIONS[ preset ] || PRESET_DEFINITIONS[ '2-col' ] ).fractions.length;
	if ( existingColumns.length <= targetCount ) {
		return [];
	}

	return existingColumns.slice( targetCount ).filter( ( column ) => ( column.modules || [] ).length > 0 );
}
