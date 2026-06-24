<?php
/**
 * Nav menu walker — optional manual integration.
 *
 * Front-end output normally uses NavMenuIntegration + walker_nav_menu_start_el.
 * Use this walker only when a theme bypasses Walker_Nav_Menu.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\Schema\LayoutSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Extends Walker_Nav_Menu to output attached mega menu panels.
 */
class MegaMenuWalker extends \Walker_Nav_Menu {

	/**
	 * Start the element output.
	 *
	 * @param string   $output Used to append additional content.
	 * @param \WP_Post $item   Menu item data object.
	 * @param int      $depth  Depth of menu item.
	 * @param object   $args   An object of wp_nav_menu() arguments.
	 * @param int      $id     Current item ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$attached_id = NavMenuItemMeta::get_attached_id( (int) $item->ID );
		$has_panel   = LayoutSchema::has_renderable_layout( $attached_id );

		if ( ! $has_panel ) {
			parent::start_el( $output, $item, $depth, $args, $id );
			return;
		}

		$length_before = strlen( $output );
		parent::start_el( $output, $item, $depth, $args, $id );
		$chunk         = substr( $output, $length_before );
		$chunk         = NavMenuItemEnhancer::enhance_start_el_output( $chunk, $item, $depth );

		$output = substr( $output, 0, $length_before ) . $chunk;
	}
}
