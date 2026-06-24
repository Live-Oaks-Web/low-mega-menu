<?php
/**
 * Shared markup enhancements for nav menu items (walker + filter paths).
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\Render\PanelRenderer;
use LOW_MM\Schema\LayoutSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Augments menu item HTML with mega menu classes, data attributes, and panels.
 */
class NavMenuItemEnhancer {

	/**
	 * Enhance a single menu item's start_el output.
	 *
	 * @param string        $item_output Rendered menu item HTML.
	 * @param \WP_Post      $item        Menu item object.
	 * @param int           $depth       Menu depth.
	 * @return string
	 */
	public static function enhance_start_el_output( string $item_output, \WP_Post $item, int $depth ): string {
		if ( false !== strpos( $item_output, 'low-mm-panel' ) ) {
			return $item_output;
		}

		$attached_id = NavMenuItemMeta::get_attached_id( (int) $item->ID );
		if ( ! LayoutSchema::has_renderable_layout( $attached_id ) ) {
			return $item_output;
		}

		$panel_html = self::build_panel_markup( (int) $attached_id, (int) $item->ID, (string) $item->title );
		if ( '' === $panel_html ) {
			return $item_output;
		}

		if ( preg_match( '/<\/a>/i', $item_output ) ) {
			return (string) preg_replace( '/<\/a>/i', '</a>' . $panel_html, $item_output, 1 );
		}

		return $item_output . $panel_html;
	}

	/**
	 * Add plugin classes and data attributes to the list item opening tag.
	 *
	 * @param string $chunk       Markup chunk for one start_el call.
	 * @param int    $attached_id Attached mega menu post ID.
	 * @param int    $depth       Menu depth.
	 * @return string
	 */
	public static function augment_list_item_opening_tag( string $chunk, int $attached_id, int $depth ): string {
		if ( ! preg_match( '/^<li\s([^>]*)>/', $chunk, $matches ) ) {
			return $chunk;
		}

		$attrs = $matches[1];
		$extra = array();

		if ( $attached_id > 0 ) {
			$extra[] = 'low-mm-has-panel';
			$extra[] = 'low-mm-nav-item--has-panel';
			$attrs  .= sprintf( ' data-low-mega-menu="%d"', $attached_id );
		}

		if ( empty( $extra ) ) {
			return $chunk;
		}

		if ( preg_match( '/class="([^"]*)"/', $attrs, $class_match ) ) {
			$new_class = trim( $class_match[1] . ' ' . implode( ' ', $extra ) );
			$attrs     = preg_replace( '/class="[^"]*"/', 'class="' . esc_attr( $new_class ) . '"', $attrs, 1 );
		} else {
			$attrs .= ' class="' . esc_attr( implode( ' ', $extra ) ) . '"';
		}

		return (string) preg_replace( '/^<li\s[^>]*>/', '<li ' . $attrs . '>', $chunk, 1 );
	}

	/**
	 * Build panel container markup.
	 *
	 * @param int    $mega_menu_post_id Mega menu post ID.
	 * @param int    $menu_item_id      Nav menu item post ID.
	 * @param string $parent_label      Parent nav item label.
	 * @return string
	 */
	public static function build_panel_markup( int $mega_menu_post_id, int $menu_item_id, string $parent_label ): string {
		$inner = PanelRenderer::render( $mega_menu_post_id );
		if ( '' === $inner ) {
			return '';
		}

		$layout     = LayoutSchema::get_layout_for_post( $mega_menu_post_id );
		$animation  = 'fade';
		$speed_ms   = 200;
		$background = '#ffffff';

		if ( is_array( $layout ) && is_array( $layout['panel_settings'] ?? null ) ) {
			$animation  = (string) ( $layout['panel_settings']['animation'] ?? 'fade' );
			$speed_ms   = (int) ( $layout['panel_settings']['animation_speed_ms'] ?? 200 );
			$background = (string) ( $layout['panel_settings']['background'] ?? '#ffffff' );
		}

		if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $background ) ) {
			$background = '#ffffff';
		}

		$panel_style = sprintf(
			'--low-mm-animation-speed:%dms;--low-mm-panel-bg:%s;',
			$speed_ms,
			esc_attr( $background )
		);

		ob_start();
		?>
		<div
			class="low-mega-menu low-mm-panel"
			id="<?php echo esc_attr( 'low-mm-panel-' . $mega_menu_post_id ); ?>"
			data-low-mm-panel-for="<?php echo esc_attr( (string) $menu_item_id ); ?>"
			data-low-mm-parent-label="<?php echo esc_attr( $parent_label ); ?>"
			data-low-mm-animation="<?php echo esc_attr( $animation ); ?>"
			style="<?php echo esc_attr( $panel_style ); ?>"
			role="region"
			aria-label="<?php echo esc_attr( $parent_label ); ?>"
			hidden
		>
			<div class="low-mm-mobile-header" aria-hidden="true">
				<button type="button" class="low-mm-back" tabindex="-1" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: parent nav item label */ __( 'Back to %s menu', 'low-mega-menu' ), $parent_label ) ); ?>">&#8249; <?php esc_html_e( 'Back', 'low-mega-menu' ); ?></button>
				<span class="low-mm-mobile-header__title"><?php echo esc_html( $parent_label ); ?></span>
			</div>
			<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
