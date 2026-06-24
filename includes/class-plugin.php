<?php
/**
 * Core plugin bootstrap.
 *
 * @package LOW_MM
 */

namespace LOW_MM;

use LOW_MM\Integrations\DiviHeaderOverride;
use LOW_MM\Admin\BuilderPage;
use LOW_MM\Admin\ListTableColumns;
use LOW_MM\Admin\SettingsPage;
use LOW_MM\Nav\ClassicMenusSupport;
use LOW_MM\Nav\FrontendNav;
use LOW_MM\Nav\MobileNavShell;
use LOW_MM\Nav\NavMenuFields;
use LOW_MM\Nav\NavMenuItemMeta;
use LOW_MM\Nav\NavMenuIntegration;
use LOW_MM\Nav\NavEnvironment;
use LOW_MM\PostTypes\MegaMenuCPT;
use LOW_MM\Render\AssetLoader;
use LOW_MM\REST\HeadingsController;
use LOW_MM\REST\MenusController;
use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Wires plugin components together.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks for all plugin components.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_modules();

		FrontendSettings::register_hooks();

		new MegaMenuCPT();
		new MenusController();
		new HeadingsController();
		new NavMenuFields();
		new NavMenuItemMeta();
		new ClassicMenusSupport();
		new BuilderPage();
		new SettingsPage();
		new ListTableColumns();
		new NavMenuIntegration();
		new MobileNavShell();
		if ( NavEnvironment::is_divi() ) {
			new DiviHeaderOverride();
		}
		new FrontendNav();
		new AssetLoader();
	}

	/**
	 * Load module classes so they self-register with ModuleRegistry.
	 *
	 * @return void
	 */
	private function load_modules(): void {
		$module_files = glob( LOW_MM_PLUGIN_DIR . 'includes/modules/*/class-*-module.php' );

		if ( ! is_array( $module_files ) ) {
			return;
		}

		foreach ( $module_files as $module_file ) {
			require_once $module_file;
		}
	}
}
