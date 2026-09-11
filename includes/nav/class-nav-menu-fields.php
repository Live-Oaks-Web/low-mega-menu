<?php
/**
 * Nav menu item custom fields for mega menu attachment.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\PostTypes\MegaMenuCPT;
use LOW_MM\Utils\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the Attach Mega Menu field on menu items.
 */
class NavMenuFields {

	/**
	 * Cached published mega menus for this request.
	 *
	 * @var \WP_Post[]|null
	 */
	private static $menus = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_field' ), 10, 4 );
	}

	/**
	 * Render the attachment dropdown for a menu item.
	 *
	 * @param int      $item_id Menu item post ID.
	 * @param \WP_Post $item    Menu item post object.
	 * @param int      $depth   Menu depth.
	 * @param object   $args    Walker arguments.
	 * @return void
	 */
	public function render_field( int $item_id, $item, int $depth, $args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! Capabilities::can_manage() ) {
			return;
		}

		$attached_id = NavMenuItemMeta::get_attached_id( $item_id );
		$menus       = self::get_mega_menus();

		$field_id   = 'edit-menu-item-low-mm-attached-' . $item_id;
		$field_name = NavMenuItemMeta::POST_FIELD . '[' . $item_id . ']';
		?>
		<p class="field-low-mm-attached description description-wide">
			<label for="<?php echo esc_attr( $field_id ); ?>">
				<?php esc_html_e( 'Attach Mega Menu', 'low-mega-menu' ); ?><br />
				<select id="<?php echo esc_attr( $field_id ); ?>" class="widefat" name="<?php echo esc_attr( $field_name ); ?>">
					<option value="0"<?php selected( $attached_id, 0 ); ?>><?php esc_html_e( '— None —', 'low-mega-menu' ); ?></option>
					<?php foreach ( $menus as $menu ) : ?>
						<option value="<?php echo esc_attr( (string) $menu->ID ); ?>"<?php selected( $attached_id, $menu->ID ); ?>>
							<?php echo esc_html( $menu->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>
		<?php
	}

	/**
	 * Published mega menus, loaded once per request.
	 *
	 * @return \WP_Post[]
	 */
	private static function get_mega_menus(): array {
		if ( null !== self::$menus ) {
			return self::$menus;
		}

		$menus = get_posts(
			array(
				'post_type'              => MegaMenuCPT::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		self::$menus = is_array( $menus ) ? $menus : array();

		return self::$menus;
	}
}
