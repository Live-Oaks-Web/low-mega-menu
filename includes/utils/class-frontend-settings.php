<?php
/**
 * Front-end behavior settings.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Utils;

use LOW_MM\Nav\NavEnvironment;

defined( 'ABSPATH' ) || exit;

/**
 * Reads plugin options that affect public menu output and JS.
 */
class FrontendSettings {

	/**
	 * Option key: toggle aria-expanded on mega menu triggers.
	 */
	public const OPTION_ARIA_EXPANDED = 'low_mm_use_aria_expanded';

	/**
	 * Option key: replace Divi header markup with the plugin header shell.
	 */
	public const OPTION_OVERRIDE_DIVI_HEADER = 'low_mm_override_divi_header';

	/**
	 * Register front-end hooks driven by settings.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'body_class', array( self::class, 'filter_body_class' ) );
	}

	/**
	 * Add body classes when the Divi header override is active.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function filter_body_class( array $classes ): array {
		if ( is_admin() || ! self::override_divi_header() ) {
			return $classes;
		}

		$classes[] = 'low-mm-divi-header-active';

		return $classes;
	}

	/**
	 * Whether nav menu triggers should use aria-expanded.
	 *
	 * @return bool
	 */
	public static function use_aria_expanded(): bool {
		return (bool) get_option( self::OPTION_ARIA_EXPANDED, false );
	}

	/**
	 * Whether the plugin should replace Divi's header output.
	 *
	 * Defaults to enabled on Divi when the option has never been saved.
	 *
	 * @return bool
	 */
	public static function override_divi_header(): bool {
		if ( ! NavEnvironment::is_divi() || ! function_exists( 'et_get_option' ) ) {
			return false;
		}

		$stored = get_option( self::OPTION_OVERRIDE_DIVI_HEADER, null );

		if ( null === $stored ) {
			$value = true;
		} else {
			$value = (bool) $stored;
		}

		/**
		 * Whether to replace Divi's #et-top-navigation with plugin navigation.
		 *
		 * @param bool $override Replace Divi primary navigation.
		 */
		return (bool) apply_filters( 'low_mm_override_divi_header', $value );
	}

	/**
	 * Config passed to the public script.
	 *
	 * @return array<string, bool>
	 */
	public static function public_script_config(): array {
		return array(
			'useAriaExpanded'  => self::use_aria_expanded(),
			'overrideDiviNav'  => self::override_divi_header(),
			'singularPostId'   => is_singular() ? (int) get_queried_object_id() : 0,
			'scrollToOffset'   => max( 0, (int) apply_filters( 'low_mm_scroll_to_offset', 30 ) ),
		);
	}
}
