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
	 * Option key: enable the AJAX search bar in the mega menu.
	 */
	public const OPTION_SEARCH_ENABLED = 'low_mm_search_enabled';

	/**
	 * Option key: viewport width (px) at which desktop mega menu begins.
	 * Viewports below this value use the mobile drawer / takeover.
	 */
	public const OPTION_MOBILE_BREAKPOINT = 'low_mm_mobile_breakpoint';

	/**
	 * Default mobile/desktop breakpoint in pixels.
	 */
	public const DEFAULT_MOBILE_BREAKPOINT = 1024;

	/**
	 * Minimum allowed breakpoint.
	 */
	public const MIN_MOBILE_BREAKPOINT = 480;

	/**
	 * Maximum allowed breakpoint.
	 */
	public const MAX_MOBILE_BREAKPOINT = 1600;

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
	 * Whether the AJAX search bar should render and respond.
	 *
	 * @return bool
	 */
	public static function search_enabled(): bool {
		return (bool) get_option( self::OPTION_SEARCH_ENABLED, true );
	}

	/**
	 * Desktop starts at this width (px). Below it, mobile drawer / takeover is used.
	 *
	 * @return int
	 */
	public static function mobile_breakpoint(): int {
		$stored = get_option( self::OPTION_MOBILE_BREAKPOINT, null );
		$value  = null === $stored ? self::DEFAULT_MOBILE_BREAKPOINT : (int) $stored;

		return self::sanitize_mobile_breakpoint( $value );
	}

	/**
	 * Clamp a breakpoint value into the allowed range.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_mobile_breakpoint( $value ): int {
		$value = (int) $value;

		if ( $value < self::MIN_MOBILE_BREAKPOINT || $value > self::MAX_MOBILE_BREAKPOINT ) {
			return self::DEFAULT_MOBILE_BREAKPOINT;
		}

		return $value;
	}

	/**
	 * Post types the search endpoint queries.
	 *
	 * @return string[]
	 */
	public static function search_post_types(): array {
		$post_types = apply_filters( 'low_mm_search_post_types', array( 'post', 'page' ) );

		if ( ! is_array( $post_types ) || empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		return array_values( array_filter( array_map( 'sanitize_key', $post_types ), 'post_type_exists' ) );
	}

	/**
	 * Maximum number of search results returned.
	 *
	 * @return int
	 */
	public static function search_results_count(): int {
		$count = (int) apply_filters( 'low_mm_search_results_count', 6 );

		return max( 1, min( 20, $count ) );
	}

	/**
	 * Config passed to the public script.
	 *
	 * @return array<string, mixed>
	 */
	public static function public_script_config(): array {
		return array(
			'useAriaExpanded'  => self::use_aria_expanded(),
			'overrideDiviNav'  => self::override_divi_header(),
			'singularPostId'   => is_singular() ? (int) get_queried_object_id() : 0,
			'scrollToOffset'   => max( 0, (int) apply_filters( 'low_mm_scroll_to_offset', 30 ) ),
			'searchEnabled'    => self::search_enabled(),
			'searchEndpoint'   => esc_url_raw( rest_url( 'low-mm/v1/search' ) ),
			'restNonce'        => wp_create_nonce( 'wp_rest' ),
			'searchMinChars'   => 2,
			'mobileBreakpoint' => self::mobile_breakpoint(),
			'i18n'             => array(
				'searchNoResults' => __( 'No results found.', 'low-mega-menu' ),
				'searchLoading'   => __( 'Searching…', 'low-mega-menu' ),
				'searchError'     => __( 'Search failed. Please try again.', 'low-mega-menu' ),
			),
		);
	}
}
