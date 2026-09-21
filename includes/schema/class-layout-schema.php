<?php
/**
 * Canonical layout JSON schema definition and defaults.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Schema;

use LOW_MM\Modules\ModuleRegistry;
use LOW_MM\PostTypes\MegaMenuCPT;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for layout JSON envelope shape.
 */
class LayoutSchema {

	/**
	 * Current schema version.
	 */
	public const VERSION = 1;

	/**
	 * Return a minimal valid empty layout for new posts.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_layout(): array {
		return array(
			'version'        => self::VERSION,
			'panel_settings' => array(
				'max_width'            => 'default',
				'background'           => '#ffffff',
				'background_mode'      => 'color',
				'background_image_id'  => 0,
				'padding_top'          => 32,
				'padding_right'        => 24,
				'padding_bottom'       => 32,
				'padding_left'         => 24,
				'animation'            => 'fade',
				'animation_speed_ms'   => 200,
			),
			'layout_preset'  => '2-col',
			'columns'        => array(),
			'mobile_order'   => array(),
		);
	}

	/**
	 * Recognized module types for v1.
	 *
	 * @return string[]
	 */
	public static function recognized_module_types(): array {
		return ModuleRegistry::get_registered_types();
	}

	/**
	 * Recognized column layout presets.
	 *
	 * @return string[]
	 */
	public static function recognized_layout_presets(): array {
		return array(
			'2-col',
			'3-col',
			'3-col-widget',
			'4-col',
		);
	}

	/**
	 * Recognized panel max-width values.
	 *
	 * @return string[]
	 */
	public static function recognized_max_widths(): array {
		return array(
			'default',
			'full',
			'custom',
		);
	}

	/**
	 * Recognized panel animation values.
	 *
	 * @return string[]
	 */
	public static function recognized_animations(): array {
		return array(
			'fade',
			'slide-down',
			'none',
		);
	}

	/**
	 * Recognized panel / module background modes.
	 *
	 * @return string[]
	 */
	public static function recognized_background_modes(): array {
		return array(
			'color',
			'image',
		);
	}

	/**
	 * Default panel padding (px) — matches former Tailwind py-8 / px-6.
	 */
	public const DEFAULT_PANEL_PADDING_TOP    = 32;
	public const DEFAULT_PANEL_PADDING_RIGHT  = 24;
	public const DEFAULT_PANEL_PADDING_BOTTOM = 32;
	public const DEFAULT_PANEL_PADDING_LEFT   = 24;

	/**
	 * Clamp a padding value into an allowed px range.
	 *
	 * @param mixed $value   Raw value.
	 * @param int   $default Fallback when missing/invalid.
	 * @return int
	 */
	public static function sanitize_padding_px( $value, int $default ): int {
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		$value = (int) $value;
		if ( $value < 0 || $value > 200 ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Resolve top/right/bottom/left padding from settings.
	 *
	 * Falls back to legacy padding_x / padding_y when the four-side keys are absent.
	 *
	 * @param array<string, mixed> $settings Settings bag.
	 * @param array{top:int,right:int,bottom:int,left:int} $defaults Per-side defaults.
	 * @return array{top:int,right:int,bottom:int,left:int}
	 */
	public static function resolve_padding_box( array $settings, array $defaults ): array {
		$legacy_x = array_key_exists( 'padding_x', $settings ) && is_numeric( $settings['padding_x'] )
			? (int) $settings['padding_x']
			: null;
		$legacy_y = array_key_exists( 'padding_y', $settings ) && is_numeric( $settings['padding_y'] )
			? (int) $settings['padding_y']
			: null;

		$fallback = array(
			'top'    => null !== $legacy_y ? $legacy_y : $defaults['top'],
			'right'  => null !== $legacy_x ? $legacy_x : $defaults['right'],
			'bottom' => null !== $legacy_y ? $legacy_y : $defaults['bottom'],
			'left'   => null !== $legacy_x ? $legacy_x : $defaults['left'],
		);

		return array(
			'top'    => self::sanitize_padding_px( $settings['padding_top'] ?? $fallback['top'], $fallback['top'] ),
			'right'  => self::sanitize_padding_px( $settings['padding_right'] ?? $fallback['right'], $fallback['right'] ),
			'bottom' => self::sanitize_padding_px( $settings['padding_bottom'] ?? $fallback['bottom'], $fallback['bottom'] ),
			'left'   => self::sanitize_padding_px( $settings['padding_left'] ?? $fallback['left'], $fallback['left'] ),
		);
	}

	/**
	 * Build inline CSS custom properties for panel background + animation.
	 *
	 * @param array<string, mixed> $panel_settings Panel settings from layout JSON.
	 * @return string Inline style attribute value (may be empty segments joined).
	 */
	public static function panel_style_vars( array $panel_settings ): string {
		$speed_ms   = max( 0, (int) ( $panel_settings['animation_speed_ms'] ?? 200 ) );
		$background = (string) ( $panel_settings['background'] ?? '#ffffff' );
		if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $background ) ) {
			$background = '#ffffff';
		}

		$pad = self::resolve_padding_box(
			$panel_settings,
			array(
				'top'    => self::DEFAULT_PANEL_PADDING_TOP,
				'right'  => self::DEFAULT_PANEL_PADDING_RIGHT,
				'bottom' => self::DEFAULT_PANEL_PADDING_BOTTOM,
				'left'   => self::DEFAULT_PANEL_PADDING_LEFT,
			)
		);

		$parts = array(
			sprintf( '--low-mm-animation-speed:%dms', $speed_ms ),
			sprintf( '--low-mm-panel-bg:%s', $background ),
			sprintf( '--low-mm-panel-padding-top:%dpx', $pad['top'] ),
			sprintf( '--low-mm-panel-padding-right:%dpx', $pad['right'] ),
			sprintf( '--low-mm-panel-padding-bottom:%dpx', $pad['bottom'] ),
			sprintf( '--low-mm-panel-padding-left:%dpx', $pad['left'] ),
		);

		$mode = (string) ( $panel_settings['background_mode'] ?? 'color' );
		if ( ! in_array( $mode, self::recognized_background_modes(), true ) ) {
			$mode = 'color';
		}

		if ( 'image' === $mode ) {
			$image_id = (int) ( $panel_settings['background_image_id'] ?? 0 );
			$image    = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
			if ( $image ) {
				$parts[] = '--low-mm-panel-bg-image:url(' . esc_url( $image ) . ')';
			}
		}

		return implode( ';', $parts ) . ';';
	}

	/**
	 * Read stored layout for a published mega menu post (front-end use).
	 *
	 * Returns null when the post is missing, unpublished, or has no saved layout.
	 *
	 * @param int $post_id Mega menu post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_layout_for_post( int $post_id ): ?array {
		$cached = \LOW_MM\Utils\Cache::get_layout( $post_id );
		if ( false !== $cached ) {
			return $cached;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || MegaMenuCPT::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			\LOW_MM\Utils\Cache::set_layout( $post_id, null );
			return null;
		}

		$layout = self::parse_stored_layout( get_post_meta( $post_id, MegaMenuCPT::LAYOUT_META_KEY, true ) );

		if ( null === $layout || ! self::layout_has_renderable_content( $layout ) ) {
			\LOW_MM\Utils\Cache::set_layout( $post_id, null );
			return null;
		}

		\LOW_MM\Utils\Cache::set_layout( $post_id, $layout );

		return $layout;
	}

	/**
	 * Whether a published mega menu has layout content worth rendering on the front end.
	 *
	 * @param int $post_id Mega menu post ID.
	 * @return bool
	 */
	public static function has_renderable_layout( int $post_id ): bool {
		return null !== self::get_layout_for_post( $post_id );
	}

	/**
	 * Read layout for admin/REST — falls back to default when empty or corrupt.
	 *
	 * @param int $post_id Mega menu post ID.
	 * @return array<string, mixed>
	 */
	public static function get_layout_or_default( int $post_id ): array {
		$layout = self::parse_stored_layout( get_post_meta( $post_id, MegaMenuCPT::LAYOUT_META_KEY, true ) );

		if ( null === $layout || empty( $layout ) ) {
			return self::default_layout();
		}

		return $layout;
	}

	/**
	 * Normalize stored layout meta — tolerates corrupt JSON strings.
	 *
	 * @param mixed $raw Value from get_post_meta().
	 * @return array<string, mixed>|null
	 */
	public static function parse_stored_layout( $raw ): ?array {
		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return null;
		}

		$decoded = json_decode( $raw, true );

		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Whether a layout envelope contains at least one module to render.
	 *
	 * @param array<string, mixed> $layout Layout data.
	 * @return bool
	 */
	public static function layout_has_renderable_content( array $layout ): bool {
		$columns = $layout['columns'] ?? array();

		if ( ! is_array( $columns ) || empty( $columns ) ) {
			return false;
		}

		foreach ( $columns as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}

			$modules = $column['modules'] ?? array();
			if ( is_array( $modules ) && ! empty( $modules ) ) {
				return true;
			}
		}

		return false;
	}
}
