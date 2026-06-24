<?php
/**
 * Theme-agnostic environment detection for nav menu integration.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

defined( 'ABSPATH' ) || exit;

/**
 * Sniffs WordPress/theme capabilities instead of hard-coding theme names.
 */
class NavEnvironment {

	/**
	 * Whether the active theme is a block / FSE theme.
	 *
	 * @return bool
	 */
	public static function is_block_theme(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Whether the theme (or a plugin) already registered nav menu locations.
	 *
	 * @return bool
	 */
	public static function has_registered_menu_locations(): bool {
		return ! empty( get_registered_nav_menus() );
	}

	/**
	 * Whether the plugin should register a fallback primary menu location.
	 *
	 * @return bool
	 */
	public static function should_register_primary_location(): bool {
		return ! self::has_registered_menu_locations();
	}

	/**
	 * Whether Appearance → Menus may need restoring for classic menu editing.
	 *
	 * @return bool
	 */
	public static function should_restore_menus_admin(): bool {
		if ( ! self::is_block_theme() ) {
			return false;
		}

		/**
		 * Whether to re-add Appearance → Menus on block themes.
		 *
		 * @param bool $restore Restore the classic menus admin screen.
		 */
		return (bool) apply_filters( 'low_mm_restore_menus_admin', true );
	}

	/**
	 * Whether the front end should swap core/navigation blocks for a classic menu.
	 *
	 * @return bool
	 */
	public static function should_replace_navigation_block(): bool {
		return self::is_block_theme();
	}

	/**
	 * Preferred header nav location slugs, in priority order.
	 *
	 * @return string[]
	 */
	public static function header_nav_location_slugs(): array {
		$slugs = array(
			'primary',
			'primary-menu',
			'main',
			'main-menu',
			'header',
			'header-menu',
		);

		/**
		 * Preferred theme menu location slugs for header output and admin guidance.
		 *
		 * @param string[] $slugs Location slugs in priority order.
		 */
		return apply_filters( 'low_mm_header_nav_location_slugs', $slugs );
	}

	/**
	 * Nav menu location slug used for header output and admin guidance.
	 *
	 * @return string
	 */
	public static function get_header_nav_location(): string {
		$registered = get_registered_nav_menus();

		foreach ( self::header_nav_location_slugs() as $slug ) {
			if ( isset( $registered[ $slug ] ) ) {
				return $slug;
			}
		}

		if ( ! empty( $registered ) ) {
			return (string) array_key_first( $registered );
		}

		return 'primary';
	}

	/**
	 * Human-readable label for the header nav menu location.
	 *
	 * @return string
	 */
	public static function get_header_nav_location_label(): string {
		$slug       = self::get_header_nav_location();
		$registered = get_registered_nav_menus();

		if ( isset( $registered[ $slug ] ) ) {
			return (string) $registered[ $slug ];
		}

		return __( 'Primary Navigation', 'low-mega-menu' );
	}

	/**
	 * Whether a theme likely assigns menus outside theme locations (page builders, header modules).
	 *
	 * Used for admin guidance only — not a specific theme check.
	 *
	 * @return bool
	 */
	public static function likely_uses_module_menu_picker(): bool {
		if ( self::is_block_theme() ) {
			return false;
		}

		$default = self::has_registered_menu_locations();

		/**
		 * Whether the active theme likely picks menus in a builder module instead of a location.
		 *
		 * @param bool $uses_module_picker Theme uses module/header menu settings.
		 */
		return (bool) apply_filters( 'low_mm_likely_uses_module_menu_picker', $default );
	}

	/**
	 * Whether the active theme is Divi (or Divi Builder is loaded).
	 *
	 * @return bool
	 */
	public static function is_divi(): bool {
		if ( defined( 'ET_BUILDER_VERSION' ) || class_exists( 'ET_Builder_Element', false ) ) {
			return true;
		}

		$theme = wp_get_theme();
		if ( ! $theme->exists() ) {
			return false;
		}

		$template = strtolower( (string) $theme->get_template() );
		$name     = strtolower( (string) $theme->get( 'Name' ) );

		return 'divi' === $template || false !== strpos( $name, 'divi' );
	}
}
