<?php
/**
 * Mega menu list table columns.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Admin;

use LOW_MM\Nav\NavMenuItemMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Adds attachment info to the mega_menu posts list table.
 */
class ListTableColumns {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'manage_mega_menu_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_mega_menu_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Add custom columns.
	 *
	 * @param string[] $columns Existing columns.
	 * @return string[]
	 */
	public function add_columns( array $columns ): array {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['low_mm_attached_to'] = __( 'Attached to', 'low-mega-menu' );
			}
		}

		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( string $column, int $post_id ): void {
		if ( 'low_mm_attached_to' !== $column ) {
			return;
		}

		$attachments = $this->find_attachments( $post_id );

		if ( empty( $attachments ) ) {
			echo '<span class="low-mm-not-attached">' . esc_html__( 'Not attached', 'low-mega-menu' ) . '</span>';
			return;
		}

		echo esc_html( implode( '; ', $attachments ) );
	}

	/**
	 * Find nav menu items attached to a mega menu post.
	 *
	 * @param int $mega_menu_id Mega menu post ID.
	 * @return string[]
	 */
	private function find_attachments( int $mega_menu_id ): array {
		$results = array();
		$menus   = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( NavMenuItemMeta::get_attached_id( (int) $item->ID ) === $mega_menu_id ) {
					$results[] = sprintf(
						'%s → %s',
						$menu->name,
						$item->title
					);
				}
			}
		}

		return $results;
	}
}
