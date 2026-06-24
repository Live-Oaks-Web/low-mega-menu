<?php
/**
 * Server-side layout JSON validation.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Schema;

use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Validates layout JSON on every write.
 */
class LayoutValidator {

	/**
	 * Safe identifier pattern for column and module ids.
	 */
	private const ID_PATTERN = '/^[a-z0-9_]+$/';

	/**
	 * Validate a layout array.
	 *
	 * @param array<string, mixed> $layout Layout data to validate.
	 * @return true|\WP_Error
	 */
	public static function validate( array $layout ) {
		$required_keys = array( 'version', 'panel_settings', 'layout_preset', 'columns', 'mobile_order' );

		foreach ( $required_keys as $key ) {
			if ( ! array_key_exists( $key, $layout ) ) {
				return new \WP_Error(
					'low_mm_missing_key',
					sprintf(
						/* translators: %s: missing JSON key name */
						__( 'Missing required layout key: "%s".', 'low-mega-menu' ),
						$key
					),
					array( 'status' => 400 )
				);
			}
		}

		if ( ! is_int( $layout['version'] ) && ! is_numeric( $layout['version'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_version',
				__( 'Layout version must be a number.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		if ( (int) $layout['version'] !== LayoutSchema::VERSION ) {
			return new \WP_Error(
				'low_mm_unsupported_version',
				sprintf(
					/* translators: %d: unsupported version number */
					__( 'Unsupported layout version: %d.', 'low-mega-menu' ),
					(int) $layout['version']
				),
				array( 'status' => 400 )
			);
		}

		$panel_error = self::validate_panel_settings( $layout['panel_settings'] );
		if ( is_wp_error( $panel_error ) ) {
			return $panel_error;
		}

		if ( ! in_array( $layout['layout_preset'], LayoutSchema::recognized_layout_presets(), true ) ) {
			return new \WP_Error(
				'low_mm_invalid_layout_preset',
				sprintf(
					/* translators: %s: unrecognized layout preset */
					__( 'Unrecognized layout preset: "%s".', 'low-mega-menu' ),
					(string) $layout['layout_preset']
				),
				array( 'status' => 400 )
			);
		}

		if ( ! is_array( $layout['columns'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_columns',
				__( 'Layout "columns" must be an array.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		$column_ids = array();

		foreach ( $layout['columns'] as $index => $column ) {
			if ( ! is_array( $column ) ) {
				return new \WP_Error(
					'low_mm_invalid_column',
					sprintf(
						/* translators: %d: column array index */
						__( 'Column at index %d must be an object.', 'low-mega-menu' ),
						$index
					),
					array( 'status' => 400 )
				);
			}

			$column_error = self::validate_column( $column, $index );
			if ( is_wp_error( $column_error ) ) {
				return $column_error;
			}

			$column_ids[] = (string) $column['id'];
		}

		if ( ! is_array( $layout['mobile_order'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_mobile_order',
				__( 'Layout "mobile_order" must be an array.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		foreach ( $layout['mobile_order'] as $order_index => $column_id ) {
			if ( ! is_string( $column_id ) || '' === $column_id ) {
				return new \WP_Error(
					'low_mm_invalid_mobile_order_entry',
					sprintf(
						/* translators: %d: mobile_order array index */
						__( 'mobile_order entry at index %d must be a non-empty string.', 'low-mega-menu' ),
						$order_index
					),
					array( 'status' => 400 )
				);
			}

			if ( ! in_array( $column_id, $column_ids, true ) ) {
				return new \WP_Error(
					'low_mm_unknown_mobile_order_column',
					sprintf(
						/* translators: %s: column id */
						__( 'mobile_order references unknown column id: "%s".', 'low-mega-menu' ),
						$column_id
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Validate panel_settings object.
	 *
	 * @param mixed $panel_settings Panel settings value.
	 * @return true|\WP_Error
	 */
	private static function validate_panel_settings( $panel_settings ) {
		if ( ! is_array( $panel_settings ) ) {
			return new \WP_Error(
				'low_mm_invalid_panel_settings',
				__( 'Layout "panel_settings" must be an object.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		$required = array( 'max_width', 'background', 'animation', 'animation_speed_ms' );

		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $panel_settings ) ) {
				return new \WP_Error(
					'low_mm_missing_panel_setting',
					sprintf(
						/* translators: %s: panel setting key */
						__( 'Missing required panel setting: "%s".', 'low-mega-menu' ),
						$key
					),
					array( 'status' => 400 )
				);
			}
		}

		if ( ! in_array( $panel_settings['max_width'], LayoutSchema::recognized_max_widths(), true ) ) {
			return new \WP_Error(
				'low_mm_invalid_max_width',
				sprintf(
					/* translators: %s: max width value */
					__( 'Unrecognized panel max_width: "%s".', 'low-mega-menu' ),
					(string) $panel_settings['max_width']
				),
				array( 'status' => 400 )
			);
		}

		if ( ! is_string( $panel_settings['background'] ) || ! preg_match( '/^#[0-9a-fA-F]{6}$/', $panel_settings['background'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_background',
				__( 'Panel background must be a 6-digit hex color (e.g. #ffffff).', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		if ( ! in_array( $panel_settings['animation'], LayoutSchema::recognized_animations(), true ) ) {
			return new \WP_Error(
				'low_mm_invalid_animation',
				sprintf(
					/* translators: %s: animation value */
					__( 'Unrecognized panel animation: "%s".', 'low-mega-menu' ),
					(string) $panel_settings['animation']
				),
				array( 'status' => 400 )
			);
		}

		if ( ! is_int( $panel_settings['animation_speed_ms'] ) && ! is_numeric( $panel_settings['animation_speed_ms'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_animation_speed',
				__( 'Panel animation_speed_ms must be a number.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		$speed = (int) $panel_settings['animation_speed_ms'];
		if ( $speed < 0 || $speed > 5000 ) {
			return new \WP_Error(
				'low_mm_invalid_animation_speed_range',
				__( 'Panel animation_speed_ms must be between 0 and 5000.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate a single column object.
	 *
	 * @param array<string, mixed> $column Column data.
	 * @param int                  $index  Column index for error messages.
	 * @return true|\WP_Error
	 */
	private static function validate_column( array $column, int $index ) {
		$required = array( 'id', 'label', 'width_fraction', 'modules' );

		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $column ) ) {
				return new \WP_Error(
					'low_mm_missing_column_key',
					sprintf(
						/* translators: 1: column key, 2: column index */
						__( 'Column at index %2$d is missing required key: "%1$s".', 'low-mega-menu' ),
						$key,
						$index
					),
					array( 'status' => 400 )
				);
			}
		}

		$column_id = (string) $column['id'];
		$id_error  = self::validate_id( $column_id, 'column', $column_id );
		if ( is_wp_error( $id_error ) ) {
			return $id_error;
		}

		if ( ! is_string( $column['label'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_column_label',
				sprintf(
					/* translators: %s: column id */
					__( 'Column "%s" label must be a string.', 'low-mega-menu' ),
					$column_id
				),
				array( 'status' => 400 )
			);
		}

		if ( ! is_int( $column['width_fraction'] ) && ! is_numeric( $column['width_fraction'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_width_fraction',
				sprintf(
					/* translators: %s: column id */
					__( 'Column "%s" width_fraction must be a number.', 'low-mega-menu' ),
					$column_id
				),
				array( 'status' => 400 )
			);
		}

		$fraction = (float) $column['width_fraction'];
		if ( $fraction <= 0 ) {
			return new \WP_Error(
				'low_mm_invalid_width_fraction_range',
				sprintf(
					/* translators: %s: column id */
					__( 'Column "%s" width_fraction must be greater than 0.', 'low-mega-menu' ),
					$column_id
				),
				array( 'status' => 400 )
			);
		}

		foreach ( array( 'border_left', 'border_right' ) as $border_key ) {
			if ( array_key_exists( $border_key, $column ) && ! is_bool( $column[ $border_key ] ) ) {
				return new \WP_Error(
					'low_mm_invalid_column_border',
					sprintf(
						/* translators: 1: column id, 2: border setting key */
						__( 'Column "%1$s" %2$s must be true or false.', 'low-mega-menu' ),
						$column_id,
						$border_key
					),
					array( 'status' => 400 )
				);
			}
		}

		if ( ! is_array( $column['modules'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_modules',
				sprintf(
					/* translators: %s: column id */
					__( 'Column "%s" modules must be an array.', 'low-mega-menu' ),
					$column_id
				),
				array( 'status' => 400 )
			);
		}

		foreach ( $column['modules'] as $module_index => $module ) {
			if ( ! is_array( $module ) ) {
				return new \WP_Error(
					'low_mm_invalid_module',
					sprintf(
						/* translators: 1: column id, 2: module index */
						__( 'Module at index %2$d in column "%1$s" must be an object.', 'low-mega-menu' ),
						$column_id,
						$module_index
					),
					array( 'status' => 400 )
				);
			}

			$module_error = self::validate_module( $module, $column_id, $module_index );
			if ( is_wp_error( $module_error ) ) {
				return $module_error;
			}
		}

		return true;
	}

	/**
	 * Validate a single module object.
	 *
	 * @param array<string, mixed> $module       Module data.
	 * @param string                 $column_id    Parent column id.
	 * @param int                    $module_index Module index for error messages.
	 * @return true|\WP_Error
	 */
	private static function validate_module( array $module, string $column_id, int $module_index ) {
		$required = array( 'id', 'type', 'settings' );

		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $module ) ) {
				return new \WP_Error(
					'low_mm_missing_module_key',
					sprintf(
						/* translators: 1: module key, 2: column id, 3: module index */
						__( 'Module at index %3$d in column "%2$s" is missing required key: "%1$s".', 'low-mega-menu' ),
						$key,
						$column_id,
						$module_index
					),
					array( 'status' => 400 )
				);
			}
		}

		$module_id = (string) $module['id'];
		$id_error  = self::validate_id( $module_id, 'module', $column_id, $module_id );
		if ( is_wp_error( $id_error ) ) {
			return $id_error;
		}

		if ( ! in_array( $module['type'], LayoutSchema::recognized_module_types(), true ) ) {
			return new \WP_Error(
				'low_mm_invalid_module_type',
				sprintf(
					/* translators: 1: module type, 2: column id, 3: module id */
					__( 'Unrecognized module type: "%1$s" in column "%2$s", module "%3$s".', 'low-mega-menu' ),
					(string) $module['type'],
					$column_id,
					$module_id
				),
				array( 'status' => 400 )
			);
		}

		if ( ! is_array( $module['settings'] ) ) {
			return new \WP_Error(
				'low_mm_invalid_module_settings',
				sprintf(
					/* translators: 1: column id, 2: module id */
					__( 'Module "%2$s" in column "%1$s" settings must be an object.', 'low-mega-menu' ),
					$column_id,
					$module_id
				),
				array( 'status' => 400 )
			);
		}

		$module_class = ModuleRegistry::get_class( (string) $module['type'] );
		if ( null !== $module_class && method_exists( $module_class, 'validate_settings' ) ) {
			$settings_result = $module_class::validate_settings( $module['settings'] );
			if ( is_wp_error( $settings_result ) ) {
				return new \WP_Error(
					$settings_result->get_error_code(),
					sprintf(
						/* translators: 1: error message, 2: column id, 3: module id */
						__( '%1$s (column "%2$s", module "%3$s")', 'low-mega-menu' ),
						$settings_result->get_error_message(),
						$column_id,
						$module_id
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Validate an id string.
	 *
	 * @param string      $id          Identifier value.
	 * @param string      $entity_type Entity type label for errors.
	 * @param string      $column_id   Column id context.
	 * @param string|null $module_id   Module id context.
	 * @return true|\WP_Error
	 */
	private static function validate_id( string $id, string $entity_type, string $column_id, ?string $module_id = null ) {
		if ( '' === $id ) {
			if ( 'module' === $entity_type && null !== $module_id ) {
				return new \WP_Error(
					'low_mm_empty_module_id',
					sprintf(
						/* translators: 1: column id, 2: module index placeholder */
						__( 'Module id in column "%s" must be a non-empty string.', 'low-mega-menu' ),
						$column_id
					),
					array( 'status' => 400 )
				);
			}

			return new \WP_Error(
				'low_mm_empty_column_id',
				__( 'Column id must be a non-empty string.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		if ( ! preg_match( self::ID_PATTERN, $id ) ) {
			if ( 'module' === $entity_type && null !== $module_id ) {
				return new \WP_Error(
					'low_mm_invalid_module_id',
					sprintf(
						/* translators: 1: module id, 2: column id */
						__( 'Invalid module id "%1$s" in column "%2$s". IDs may only contain lowercase letters, numbers, and underscores.', 'low-mega-menu' ),
						$module_id,
						$column_id
					),
					array( 'status' => 400 )
				);
			}

			return new \WP_Error(
				'low_mm_invalid_column_id',
				sprintf(
					/* translators: %s: column id */
					__( 'Invalid column id "%s". IDs may only contain lowercase letters, numbers, and underscores.', 'low-mega-menu' ),
					$id
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}
}
