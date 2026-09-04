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
	 * Option key: palette colors from Settings → Styling.
	 */
	public const OPTION_STYLE_COLORS = 'low_mm_style_colors';

	/**
	 * Option key: optional custom CSS from Settings → Styling.
	 */
	public const OPTION_CUSTOM_CSS = 'low_mm_custom_css';

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

	/**
	 * Color keys available in Settings → Styling.
	 *
	 * Defaults match the current front-end palette.
	 *
	 * @return array<string, array{label:string,description:string,default:string}>
	 */
	public static function style_color_fields(): array {
		return array(
			'text'        => array(
				'label'       => __( 'Text color', 'low-mega-menu' ),
				'description' => __( 'Body and excerpt text inside mega panels.', 'low-mega-menu' ),
				'default'     => '#4b5563',
			),
			'heading'     => array(
				'label'       => __( 'Heading color', 'low-mega-menu' ),
				'description' => __( 'Titles and CTA headings inside panels.', 'low-mega-menu' ),
				'default'     => '#21303f',
			),
			'link'        => array(
				'label'       => __( 'Link color', 'low-mega-menu' ),
				'description' => __( 'Links inside mega panels and search results.', 'low-mega-menu' ),
				'default'     => '#21303f',
			),
			'link_hover'  => array(
				'label'       => __( 'Link hover color', 'low-mega-menu' ),
				'description' => __( 'Panel color on hover / focus inside panels.', 'low-mega-menu' ),
				'default'     => '#111827',
			),
			'button_bg'   => array(
				'label'       => __( 'Button background', 'low-mega-menu' ),
				'description' => __( 'CTA button backgrounds.', 'low-mega-menu' ),
				'default'     => '#111827',
			),
			'button_text' => array(
				'label'       => __( 'Button text', 'low-mega-menu' ),
				'description' => __( 'Text color on CTA buttons.', 'low-mega-menu' ),
				'default'     => '#ffffff',
			),
			'panel_bg'    => array(
				'label'       => __( 'Panel background', 'low-mega-menu' ),
				'description' => __( 'Desktop mega panel and search results panel background.', 'low-mega-menu' ),
				'default'     => '#ffffff',
			),
			'muted'       => array(
				'label'       => __( 'Muted text', 'low-mega-menu' ),
				'description' => __( 'Secondary labels, dates, meta, and search status text.', 'low-mega-menu' ),
				'default'     => '#8a8a8a',
			),
			'border'      => array(
				'label'       => __( 'Border / divider', 'low-mega-menu' ),
				'description' => __( 'Column borders and subtle separators.', 'low-mega-menu' ),
				'default'     => '#e5e7eb',
			),
			'accent'      => array(
				'label'       => __( 'Accent', 'low-mega-menu' ),
				'description' => __( 'Highlights such as post labels and search focus accents.', 'low-mega-menu' ),
				'default'     => '#bb4d1c',
			),
		);
	}

	/**
	 * Saved style colors with stylesheet defaults filled in when unset/empty.
	 *
	 * @return array<string, string>
	 */
	public static function style_colors(): array {
		$stored = get_option( self::OPTION_STYLE_COLORS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$colors = array();
		foreach ( self::style_color_fields() as $key => $field ) {
			$raw   = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
			$clean = self::sanitize_hex_color_value( $raw );
			$colors[ $key ] = '' !== $clean ? $clean : (string) $field['default'];
		}

		return $colors;
	}

	/**
	 * Sanitize the style colors option.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<string, string>
	 */
	public static function sanitize_style_colors( $value ): array {
		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$clean = array();
		foreach ( array_keys( self::style_color_fields() ) as $key ) {
			$raw           = isset( $value[ $key ] ) ? (string) $value[ $key ] : '';
			$clean[ $key ] = self::sanitize_hex_color_value( $raw );
		}

		return $clean;
	}

	/**
	 * Accept a 3/6/8 digit hex color, or empty string.
	 *
	 * @param string $color Raw color.
	 * @return string
	 */
	public static function sanitize_hex_color_value( string $color ): string {
		$color = trim( $color );
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return $color;
		}

		return '';
	}

	/**
	 * Custom CSS from Settings → Styling.
	 *
	 * @return string
	 */
	public static function custom_css(): string {
		return (string) get_option( self::OPTION_CUSTOM_CSS, '' );
	}

	/**
	 * Sanitize custom CSS (admins only; strip tags / null bytes).
	 *
	 * @param mixed $value Submitted CSS.
	 * @return string
	 */
	public static function sanitize_custom_css( $value ): string {
		$value = is_string( $value ) ? $value : '';
		$value = str_replace( "\0", '', $value );
		$value = wp_strip_all_tags( $value );

		/**
		 * Filter sanitized custom CSS before save.
		 *
		 * @param string $value CSS.
		 */
		return (string) apply_filters( 'low_mm_sanitize_custom_css', $value );
	}

	/**
	 * Useful class names for the Custom CSS help text.
	 *
	 * @return string
	 */
	public static function custom_css_class_help(): string {
		$classes = array(
			'.low-mm-panel',
			'.low-mm-panel__inner',
			'.low-mm-column',
			'.low-mm-column__label',
			'.low-mm-module',
			'.low-mm-link-list',
			'.low-mm-link-list__link',
			'.low-mm-post-query__title',
			'.low-mm-post-query__label',
			'.low-mm-cta',
			'.low-mm-cta__heading',
			'.low-mm-cta__button',
			'.low-mm-search',
			'.low-mm-search__input',
			'.low-mm-search__panel',
			'.low-mm-mobile-drawer__panel',
		);

		return implode( ', ', $classes );
	}

	/**
	 * Build an inline CSS block for saved palette colors + custom CSS.
	 *
	 * @return string
	 */
	public static function public_style_css(): string {
		$parts  = array();
		$vars   = array();
		$colors = self::style_colors();
		$map    = array(
			'text'        => '--low-mm-color-text',
			'heading'     => '--low-mm-color-heading',
			'link'        => '--low-mm-color-link',
			'link_hover'  => '--low-mm-color-link-hover',
			'button_bg'   => '--low-mm-color-button-bg',
			'button_text' => '--low-mm-color-button-text',
			'panel_bg'    => '--low-mm-color-panel-bg',
			'muted'       => '--low-mm-color-muted',
			'border'      => '--low-mm-color-border',
			'accent'      => '--low-mm-color-accent',
		);

		foreach ( $map as $key => $css_var ) {
			if ( ! empty( $colors[ $key ] ) ) {
				$vars[] = $css_var . ':' . $colors[ $key ];
			}
		}

		if ( ! empty( $vars ) ) {
			// Scope to mega surfaces only — never the Divi/theme top-level nav links.
			$parts[] = '.low-mm-panel,.low-mm-mobile-drawer,.low-mm-search{' . implode( ';', $vars ) . ';}';
		}

		$custom = trim( self::custom_css() );
		if ( '' !== $custom ) {
			$parts[] = $custom;
		}

		return implode( "\n", $parts );
	}
}
