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
	 * Enqueue public CSS, rewriting media-query breakpoints to the setting.
	 *
	 * @param string $css_path   Absolute path to compiled CSS.
	 * @param int    $breakpoint Desktop starts at this width (px).
	 * @return void
	 */
	private function enqueue_public_css( string $css_path, int $breakpoint ): void {
		$version = LOW_MM_VERSION . '-' . $breakpoint;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin asset.
		$css = file_get_contents( $css_path );
		if ( false === $css || '' === $css ) {
			wp_enqueue_style(
				'low-mm-public',
				LOW_MM_PLUGIN_URL . 'public/build/main.css',
				array(),
				$version
			);
			$this->print_breakpoint_bridge( $breakpoint );
			return;
		}

		$mobile_max = max( 0, $breakpoint - 1 );

		// Rewrite every mobile/desktop media query (tolerant of whitespace).
		$css = preg_replace(
			'/@media\s*\(\s*max-width:\s*1023px\s*\)/',
			'@media (max-width: ' . $mobile_max . 'px)',
			$css
		);
		$css = preg_replace(
			'/@media\s*\(\s*min-width:\s*1024px\s*\)/',
			'@media (min-width: ' . $breakpoint . 'px)',
			$css
		);
		$css = str_replace(
			'--low-mm-breakpoint: 1024px',
			'--low-mm-breakpoint: ' . $breakpoint . 'px',
			(string) $css
		);

		wp_register_style( 'low-mm-public', false, array(), $version );
		wp_enqueue_style( 'low-mm-public' );
		wp_add_inline_style( 'low-mm-public', (string) $css );
		$this->print_breakpoint_bridge( $breakpoint );
	}

	/**
	 * Publish the breakpoint to CSS vars and set an early html class so layout
	 * matches JS even before the main bundle runs.
	 *
	 * @param int $breakpoint Desktop starts at this width (px).
	 * @return void
	 */
	private function print_breakpoint_bridge( int $breakpoint ): void {
		wp_add_inline_style(
			'low-mm-public',
			sprintf(
				':root{--low-mm-breakpoint:%1$dpx;}' .
				'html.low-mm-is-mobile .low-mm-nav-container>.low-mm-search,html.low-mm-is-mobile #et-top-navigation>.low-mm-search,html.low-mm-is-mobile #top-menu-nav>.low-mm-search,html.low-mm-is-mobile .low-mm-header-navigation>.low-mm-search{display:none!important;}' .
				'html.low-mm-is-mobile .low-mm-menu-toggle,html.low-mm-is-mobile body.low-mm-divi-header-active #main-header .low-mm-menu-toggle--divi-slot{display:inline-flex!important;visibility:visible!important;opacity:1!important;}' .
				'html.low-mm-is-desktop .low-mm-menu-toggle,html.low-mm-is-desktop .low-mm-drawer-close,html.low-mm-is-desktop .low-mm-mobile-drawer__backdrop{display:none!important;}',
				$breakpoint
			)
		);

		$script = sprintf(
			'(function(){var bp=%1$d;var mobile=window.matchMedia("(max-width:"+(bp-1)+"px)").matches;var r=document.documentElement;r.classList.toggle("low-mm-is-mobile",mobile);r.classList.toggle("low-mm-is-desktop",!mobile);r.style.setProperty("--low-mm-breakpoint",bp+"px");})();',
			$breakpoint
		);

		// Inline before the main bundle so the first paint matches the setting.
		wp_register_script( 'low-mm-breakpoint', false, array(), LOW_MM_VERSION, false );
		wp_enqueue_script( 'low-mm-breakpoint' );
		wp_add_inline_script( 'low-mm-breakpoint', $script );
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
