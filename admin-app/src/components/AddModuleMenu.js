import { Dropdown, MenuGroup, MenuItem, Button } from '@wordpress/components';
import { getPaletteItems } from '../modules/moduleRegistry';

export default function AddModuleMenu( { onAddModule } ) {
	const items = getPaletteItems();

	return (
		<Dropdown
			className="low-mm-add-module-menu"
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button variant="secondary" onClick={ onToggle } aria-expanded={ isOpen }>
					Add module
				</Button>
			) }
			renderContent={ ( { onClose } ) => (
				<MenuGroup>
					{ items.map( ( item ) => (
						<MenuItem
							key={ item.type }
							onClick={ () => {
								onAddModule( item.type );
								onClose();
							} }
						>
							{ item.label }
						</MenuItem>
					) ) }
				</MenuGroup>
			) }
		/>
	);
}
