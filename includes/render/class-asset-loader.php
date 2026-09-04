<?php
/**
 * Conditional front-end asset loading.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Render;

use LOW_MM\Nav\NavMenuContext;
use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues public CSS/JS only when mega menus may render.
 */
class AssetLoader {

	/**
	 * Whether assets should load on this request.
	 *
	 * @var bool|null
	 */
	private static $should_enqueue = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'detect_page_menus' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_action( 'wp_nav_menu', array( $this, 'flag_menu_rendered' ), 1, 2 );
	}

	/**
	 * Inspect theme menu locations assigned on this site.
	 *
	 * @return void
	 */
	public function detect_page_menus(): void {
		if ( is_admin() ) {
			return;
		}

		if ( is_singular() ) {
			$post_type = get_post_type();
			if ( is_string( $post_type ) && is_post_type_viewable( $post_type ) ) {
				self::$should_enqueue = true;
				return;
			}
		}

		$locations = get_nav_menu_locations();

		if ( empty( $locations ) ) {
			self::$should_enqueue = NavMenuContext::site_has_mega_attachment();
			return;
		}

		foreach ( $locations as $menu_id ) {
			if ( NavMenuContext::menu_has_mega_attachment( (int) $menu_id ) ) {
				self::$should_enqueue = true;
				return;
			}
		}

		self::$should_enqueue = NavMenuContext::site_has_mega_attachment();
	}

	/**
	 * Flag enqueue when a menu with attachments actually renders.
	 *
	 * @param string $nav_menu Rendered menu HTML.
	 * @param object $args     wp_nav_menu() arguments.
	 * @return string
	 */
	public function flag_menu_rendered( string $nav_menu, $args ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( false !== strpos( $nav_menu, 'low-mm-has-panel' ) || false !== strpos( $nav_menu, 'low-mm-panel' ) ) {
			self::$should_enqueue = true;
		}

		return $nav_menu;
	}

	/**
	 * Enqueue compiled public assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->should_enqueue() ) {
			return;
		}

		$css_path   = LOW_MM_PLUGIN_DIR . 'public/build/main.css';
		$js_path    = LOW_MM_PLUGIN_DIR . 'public/build/controller.js';
		$breakpoint = FrontendSettings::mobile_breakpoint();

		if ( file_exists( $css_path ) ) {
			$this->enqueue_public_css( $css_path, $breakpoint );
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'low-mm-public',
				LOW_MM_PLUGIN_URL . 'public/build/controller.js',
				array(),
				LOW_MM_VERSION,
				true
			);

			wp_add_inline_script(
				'low-mm-public',
				'window.lowMmPublicConfig = ' . wp_json_encode( FrontendSettings::public_script_config() ) . ';',
				'before'
			);
		}
	}

	/**
	 * Enqueue public CSS, rewriting media-query breakpoints when customized.
	 *
	 * @param string $css_path   Absolute path to compiled CSS.
	 * @param int    $breakpoint Desktop starts at this width (px).
	 * @return void
	 */
	private function enqueue_public_css( string $css_path, int $breakpoint ): void {
		if ( FrontendSettings::DEFAULT_MOBILE_BREAKPOINT === $breakpoint ) {
			wp_enqueue_style(
				'low-mm-public',
				LOW_MM_PLUGIN_URL . 'public/build/main.css',
				array(),
				LOW_MM_VERSION
			);
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin asset.
		$css = file_get_contents( $css_path );
		if ( false === $css || '' === $css ) {
			wp_enqueue_style(
				'low-mm-public',
				LOW_MM_PLUGIN_URL . 'public/build/main.css',
				array(),
				LOW_MM_VERSION
			);
			return;
		}

		$mobile_max = max( 0, $breakpoint - 1 );
		$css        = str_replace(
			array(
				'max-width: 1023px',
				'min-width: 1024px',
				'--low-mm-breakpoint: 1024px',
			),
			array(
				'max-width: ' . $mobile_max . 'px',
				'min-width: ' . $breakpoint . 'px',
				'--low-mm-breakpoint: ' . $breakpoint . 'px',
			),
			$css
		);

		wp_register_style( 'low-mm-public', false, array(), LOW_MM_VERSION );
		wp_enqueue_style( 'low-mm-public' );
		wp_add_inline_style( 'low-mm-public', $css );
	}

	/**
	 * Whether assets should be enqueued.
	 *
	 * @return bool
	 */
	private function should_enqueue(): bool {
		if ( null !== self::$should_enqueue ) {
			return (bool) self::$should_enqueue;
		}

		self::$should_enqueue = NavMenuContext::site_has_mega_attachment();
		return (bool) self::$should_enqueue;
	}
}
