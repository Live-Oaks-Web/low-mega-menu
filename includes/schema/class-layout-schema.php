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
				'max_width'          => 'default',
				'background'         => '#ffffff',
				'animation'          => 'fade',
				'animation_speed_ms' => 200,
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
	 * Read stored layout for a published mega menu post (front-end use).
	 *
	 * Returns null when the post is missing, unpublished, or has no saved layout.
	 *
	 * @param int $post_id Mega menu post ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_layout_for_post( int $post_id ): ?array {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || MegaMenuCPT::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		$layout = self::parse_stored_layout( get_post_meta( $post_id, MegaMenuCPT::LAYOUT_META_KEY, true ) );

		if ( null === $layout || ! self::layout_has_renderable_content( $layout ) ) {
			return null;
		}

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
