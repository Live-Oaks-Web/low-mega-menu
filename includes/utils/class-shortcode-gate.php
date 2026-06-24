<?php
/**
 * Shortcode execution gate for Code modules.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces global and per-module shortcode execution policy.
 */
class ShortcodeGate {

	/**
	 * Global option key.
	 */
	public const OPTION_KEY = 'low_mm_allow_shortcode_execution';

	/**
	 * Whether shortcode execution is allowed for a Code module instance.
	 *
	 * @param string $per_module_setting inherit|on|off.
	 * @return bool
	 */
	public static function is_allowed( string $per_module_setting ): bool {
		if ( 'on' === $per_module_setting ) {
			return true;
		}

		if ( 'off' === $per_module_setting ) {
			return false;
		}

		return (bool) get_option( self::OPTION_KEY, false );
	}
}
