let idCounter = 1;

export function generateId( prefix ) {
	const id = `${ prefix }_${ idCounter }`;
	idCounter += 1;
	return id;
}

export function syncIdCounter( layout ) {
	if ( ! layout?.columns ) {
		return;
	}

	const ids = [];
	layout.columns.forEach( ( column ) => {
		ids.push( column.id );
		( column.modules || [] ).forEach( ( mod ) => ids.push( mod.id ) );
	} );

	ids.forEach( ( id ) => {
		const match = /^[a-z]+_(\d+)$/.exec( id );
		if ( match ) {
			idCounter = Math.max( idCounter, parseInt( match[ 1 ], 10 ) + 1 );
		}
	} );
}
