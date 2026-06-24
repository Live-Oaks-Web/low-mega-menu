<?php
/**
 * Abstract base module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for all module types.
 */
abstract class Module {

	/**
	 * Module type slug.
	 *
	 * @return string
	 */
	abstract public static function type(): string;

	/**
	 * Human-readable module label.
	 *
	 * @return string
	 */
	abstract public static function label(): string;

	/**
	 * Default settings for a new instance.
	 *
	 * @return array<string, mixed>
	 */
	abstract public static function default_settings(): array;

	/**
	 * Validate module-specific settings.
	 *
	 * @param array<string, mixed> $settings Module settings.
	 * @return true|\WP_Error
	 */
	abstract public static function validate_settings( array $settings );

	/**
	 * Render module HTML.
	 *
	 * @param array<string, mixed> $settings Module settings.
	 * @return string
	 */
	abstract public static function render( array $settings ): string;

	/**
	 * Include a colocated render template.
	 *
	 * @param array<string, mixed> $settings Variables for the template.
	 * @return string
	 */
	protected static function render_template( array $settings ): string {
		$template = dirname( ( new \ReflectionClass( static::class ) )->getFileName() ) . '/render.php';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		ob_start();
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $settings, EXTR_SKIP );
		include $template;

		return (string) ob_get_clean();
	}
}
