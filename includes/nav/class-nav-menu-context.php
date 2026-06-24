<?php
/**
 * Resolves wp_nav_menu() context and mega menu attachments.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for menu integration across themes.
 */
class NavMenuContext {

	/**
	 * Resolve a nav menu term ID from wp_nav_menu() arguments.
	 *
	 * @param array<string, mixed>|object $args Menu arguments.
	 * @return int
	 */
	public static function resolve_menu_term_id( $args ): int {
		$args = (array) $args;

		if ( ! empty( $args['menu'] ) ) {
			$menu = $args['menu'];
			if ( is_numeric( $menu ) ) {
				return (int) $menu;
			}

			$menu_object = wp_get_nav_menu_object( $menu );
			return $menu_object instanceof \WP_Term ? (int) $menu_object->term_id : 0;
		}

		if ( ! empty( $args['theme_location'] ) ) {
			$locations = get_nav_menu_locations();
			$slug      = (string) $args['theme_location'];

			return isset( $locations[ $slug ] ) ? (int) $locations[ $slug ] : 0;
		}

		return 0;
	}

	/**
	 * Whether a nav menu contains at least one mega menu attachment.
	 *
	 * @param int $menu_id Nav menu term ID.
	 * @return bool
	 */
	public static function menu_has_mega_attachment( int $menu_id ): bool {
		if ( $menu_id <= 0 ) {
			return false;
		}

		$items = wp_get_nav_menu_items( $menu_id );
		if ( ! is_array( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( NavMenuItemMeta::get_attached_id( (int) $item->ID ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether any menu item site-wide has a mega menu attachment.
	 *
	 * @return bool
	 */
	public static function site_has_mega_attachment(): bool {
		$cache_key = 'site_has_mega_attachment';
		$cached    = wp_cache_get( $cache_key, 'low_mm' );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => 'nav_menu_item',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Existence check for one menu item; result cached.
				'meta_query'             => array(
					array(
						'key'     => NavMenuItemMeta::META_KEY,
						'value'   => '0',
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$has_attachment = ! empty( $query->posts );
		wp_cache_set( $cache_key, $has_attachment ? 1 : 0, 'low_mm' );

		return $has_attachment;
	}

	/**
	 * Clear cached site-wide attachment lookup.
	 *
	 * @return void
	 */
	public static function invalidate_site_attachment_cache(): void {
		wp_cache_delete( 'site_has_mega_attachment', 'low_mm' );
	}
}
