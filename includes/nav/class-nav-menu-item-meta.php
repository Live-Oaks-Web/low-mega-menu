<?php
/**
 * Nav menu item mega menu attachment meta.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

defined( 'ABSPATH' ) || exit;

/**
 * Persists and reads menu item attachment meta.
 */
class NavMenuItemMeta {

	/**
	 * Post meta key for attached mega menu ID.
	 */
	public const META_KEY = '_low_mm_attached_menu_id';

	/**
	 * POST field name for menu item attachment values.
	 */
	public const POST_FIELD = 'menu-item-low-mm-attached';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_attachment' ), 10, 3 );
	}

	/**
	 * Get attached mega menu post ID for a menu item.
	 *
	 * @param int $menu_item_id Nav menu item post ID.
	 * @return int Zero when nothing is attached.
	 */
	public static function get_attached_id( int $menu_item_id ): int {
		$attached = (int) get_post_meta( $menu_item_id, self::META_KEY, true );

		return $attached > 0 ? $attached : 0;
	}

	/**
	 * Save attachment on menu update.
	 *
	 * @param int   $menu_id         Menu term ID.
	 * @param int   $menu_item_db_id Menu item post ID.
	 * @param array $args            Menu item arguments.
	 * @return void
	 */
	public function save_attachment( int $menu_id, int $menu_item_db_id, array $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['update-nav-menu-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['update-nav-menu-nonce'] ) ), 'update-nav_menu' ) ) {
			return;
		}

		$posted = isset( $_POST[ self::POST_FIELD ] ) && is_array( $_POST[ self::POST_FIELD ] )
			? wp_unslash( $_POST[ self::POST_FIELD ] )
			: array();

		if ( ! array_key_exists( $menu_item_db_id, $posted ) ) {
			return;
		}

		$attached_id = (int) $posted[ $menu_item_db_id ];

		if ( $attached_id <= 0 ) {
			delete_post_meta( $menu_item_db_id, self::META_KEY );
			NavMenuContext::invalidate_site_attachment_cache();
			return;
		}

		$post = get_post( $attached_id );
		if ( ! $post || 'mega_menu' !== $post->post_type || 'publish' !== $post->post_status ) {
			delete_post_meta( $menu_item_db_id, self::META_KEY );
			NavMenuContext::invalidate_site_attachment_cache();
			return;
		}

		update_post_meta( $menu_item_db_id, self::META_KEY, $attached_id );
		NavMenuContext::invalidate_site_attachment_cache();
	}
}
