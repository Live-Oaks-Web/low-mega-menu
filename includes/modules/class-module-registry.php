<?php
/**
 * Module type registry.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Self-registration registry for module types.
 */
class ModuleRegistry {

	/**
	 * Registered module classes keyed by type string.
	 *
	 * @var array<string, string>
	 */
	private static $registry = array();

	/**
	 * Register a module class.
	 *
	 * @param string $type  Module type slug.
	 * @param string $class Fully-qualified module class name.
	 * @return void
	 */
	public static function register( string $type, string $class ): void {
		self::$registry[ $type ] = $class;
	}

	/**
	 * Get all registered module type strings.
	 *
	 * @return string[]
	 */
	public static function get_registered_types(): array {
		return array_keys( self::$registry );
	}

	/**
	 * Get module metadata for the builder UI.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function get_builder_definitions(): array {
		$definitions = array();

		foreach ( self::$registry as $type => $class ) {
			if ( ! method_exists( $class, 'label' ) ) {
				continue;
			}

			$definitions[] = array(
				'type'  => $type,
				'label' => (string) $class::label(),
			);
		}

		return $definitions;
	}

	/**
	 * Get the class for a module type.
	 *
	 * @param string $type Module type slug.
	 * @return string|null
	 */
	public static function get_class( string $type ): ?string {
		return self::$registry[ $type ] ?? null;
	}

	/**
	 * Render a module instance.
	 *
	 * @param string               $type     Module type slug.
	 * @param array<string, mixed> $settings Module settings.
	 * @return string
	 */
	public static function render( string $type, array $settings ): string {
		$class = self::get_class( $type );

		if ( null === $class || ! method_exists( $class, 'render' ) ) {
			return '';
		}

		return (string) $class::render( $settings );
	}
}
