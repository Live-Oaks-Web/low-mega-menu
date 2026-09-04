<?php
/**
 * Plugin Name:       LOW Mega Menu
 * Description:       Build multi-column mega menu panels and attach them to WordPress nav menu items.
 * Version:           1.7.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Scott Hill
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       low-mega-menu
 * Update URI:        https://github.com/Live-Oaks-Web/low-mega-menu
 *
 * @package LOW_MM
 */

defined( 'ABSPATH' ) || exit;

define( 'LOW_MM_VERSION', '1.7.5' );
define( 'LOW_MM_PLUGIN_FILE', __FILE__ );
define( 'LOW_MM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOW_MM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$low_mm_autoloader = LOW_MM_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $low_mm_autoloader ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'LOW Mega Menu requires Composer dependencies. Run `composer install` in the plugin directory.', 'low-mega-menu' )
			);
		}
	);
	return;
}

require_once $low_mm_autoloader;

if ( ! class_exists( 'LOW_MM\Plugin' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'LOW Mega Menu autoloader is out of date. Run `composer dump-autoload` in the plugin directory.', 'low-mega-menu' )
			);
		}
	);
	return;
}

register_activation_hook( __FILE__, array( 'LOW_MM\Activation', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LOW_MM\Activation', 'deactivate' ) );

LOW_MM\Plugin::instance()->init();
