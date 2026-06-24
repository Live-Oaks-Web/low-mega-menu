<?php
/**
 * Renders classic nav menus on block themes.
 *
 * Block themes (Twenty Twenty-Four, Twenty Twenty-Five, etc.) output a Navigation
 * block instead of wp_nav_menu(). This class swaps the first header Navigation
 * block for the classic menu assigned to the "primary" location when one exists.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\Nav\NavEnvironment;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end classic menu output for block themes.
 */
class FrontendNav {

	/**
	 * Whether the primary menu has already replaced a Navigation block.
	 *
	 * @var bool
	 */
	private static $primary_rendered = false;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'render_block', array( $this, 'maybe_replace_navigation_block' ), 10, 2 );
	}

	/**
	 * Replace the first Navigation block with the classic primary menu.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Block data.
	 * @return string
	 */
	public function maybe_replace_navigation_block( string $content, array $block ): string {
		if ( is_admin() || ( $block['blockName'] ?? '' ) !== 'core/navigation' ) {
			return $content;
		}

		if ( ! NavEnvironment::should_replace_navigation_block() ) {
			return $content;
		}

		if ( ! has_nav_menu( NavEnvironment::get_header_nav_location() ) ) {
			return $content;
		}

		if ( self::$primary_rendered ) {
			return $content;
		}

		/**
		 * Whether to replace a core/navigation block with the classic primary menu.
		 *
		 * @param bool  $replace Replace block output.
		 * @param array $block   Block data.
		 */
		if ( ! apply_filters( 'low_mm_replace_navigation_block_with_primary_menu', true, $block ) ) {
			return $content;
		}

		$menu_html = $this->render_primary_menu();
		if ( '' === $menu_html ) {
			return $content;
		}

		self::$primary_rendered = true;

		return $menu_html;
	}

	/**
	 * Render the menu assigned to the primary theme location.
	 *
	 * @return string
	 */
	private function render_primary_menu(): string {
		ob_start();
		wp_nav_menu(
			array(
				'theme_location' => NavEnvironment::get_header_nav_location(),
				'container'      => 'div',
				'container_class'  => 'low-mega-menu low-mm-nav-container low-mm-fallback-nav',
				'menu_class'       => 'low-mm-nav low-mm-nav--desktop',
				'fallback_cb'      => false,
				'depth'            => 0,
			)
		);

		return trim( (string) ob_get_clean() );
	}
}
