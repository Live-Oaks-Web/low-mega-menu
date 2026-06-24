<?php
/**
 * Wraps mega menu nav output with a mobile drawer shell and toggle button.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

defined( 'ABSPATH' ) || exit;

/**
 * Injects hamburger toggle + slide-in drawer markup around wp_nav_menu() output.
 */
class MobileNavShell {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'wp_nav_menu', array( $this, 'wrap_menu' ), 20, 2 );
	}

	/**
	 * Wrap low-mm nav menus with toggle + drawer when not already wrapped.
	 *
	 * @param string $nav_menu Rendered menu HTML.
	 * @param object $args     wp_nav_menu() arguments.
	 * @return string
	 */
	public function wrap_menu( string $nav_menu, $args ): string {
		if ( is_admin() || '' === $nav_menu ) {
			return $nav_menu;
		}

		if ( ! $this->is_low_mm_menu( $nav_menu, $args ) ) {
			return $nav_menu;
		}

		/**
		 * Whether to wrap a menu with the plugin mobile drawer shell.
		 *
		 * @param bool   $wrap     Wrap with toggle + drawer markup.
		 * @param string $nav_menu Rendered menu HTML.
		 * @param object $args     wp_nav_menu() arguments.
		 */
		if ( ! apply_filters( 'low_mm_wrap_mobile_nav_shell', true, $nav_menu, $args ) ) {
			return $nav_menu;
		}

		if ( false !== strpos( $nav_menu, 'low-mm-menu-toggle' ) ) {
			return $nav_menu;
		}

		$drawer_id = 'low-mm-drawer-' . wp_unique_id();

		$toggle = sprintf(
			'<button type="button" class="low-mm-menu-toggle" aria-expanded="false" aria-controls="%1$s" aria-label="%2$s"><span class="low-mm-menu-toggle__icon" aria-hidden="true"></span><span class="low-mm-menu-toggle__label">%2$s</span></button>',
			esc_attr( $drawer_id ),
			esc_attr__( 'Menu', 'low-mega-menu' )
		);

		$close = sprintf(
			'<button type="button" class="low-mm-drawer-close" aria-label="%1$s"><span aria-hidden="true">&times;</span></button>',
			esc_attr__( 'Close menu', 'low-mega-menu' )
		);

		$drawer_open = sprintf(
			'<div class="low-mm-mobile-drawer" id="%1$s" aria-hidden="true"><button type="button" class="low-mm-mobile-drawer__backdrop" tabindex="-1" aria-hidden="true"></button><div class="low-mm-mobile-drawer__panel">%2$s',
			esc_attr( $drawer_id ),
			$close
		);

		$drawer_close = '</div></div>';

		if ( preg_match( '/^(<div[^>]*low-mm-nav-container[^>]*>)([\s\S]*)(<\/div>\s*)$/', $nav_menu, $matches ) ) {
			return $matches[1] . $toggle . $drawer_open . $matches[2] . $drawer_close . $matches[3];
		}

		return $toggle . $drawer_open . $nav_menu . $drawer_close;
	}

	/**
	 * Whether this menu belongs to the plugin.
	 *
	 * @param string $nav_menu Rendered HTML.
	 * @param object $args     Menu args.
	 * @return bool
	 */
	private function is_low_mm_menu( string $nav_menu, $args ): bool {
		if ( false !== strpos( $nav_menu, 'low-mm-nav-container' ) ) {
			return true;
		}

		if ( false !== strpos( $nav_menu, 'data-low-mega-menu' ) || false !== strpos( $nav_menu, 'low-mm-panel' ) ) {
			return true;
		}

		$container_class = is_object( $args ) && isset( $args->container_class ) ? (string) $args->container_class : '';

		return false !== strpos( $container_class, 'low-mm-nav-container' );
	}
}
