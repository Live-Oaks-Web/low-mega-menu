<?php
/**
 * Plugin activation and deactivation lifecycle.
 *
 * @package LOW_MM
 */

namespace LOW_MM;

use LOW_MM\Utils\FrontendSettings;
use LOW_MM\Utils\ShortcodeGate;

defined( 'ABSPATH' ) || exit;

/**
 * Handles activation/deactivation hooks.
 */
class Activation {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( false === get_option( ShortcodeGate::OPTION_KEY, false ) ) {
			add_option( ShortcodeGate::OPTION_KEY, false, '', false );
		}

		if ( false === get_option( FrontendSettings::OPTION_ARIA_EXPANDED, false ) ) {
			add_option( FrontendSettings::OPTION_ARIA_EXPANDED, false, '', false );
		}

		flush_rewrite_rules();
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
