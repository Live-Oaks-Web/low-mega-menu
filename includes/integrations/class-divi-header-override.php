<?php
/**
 * Replaces Divi's #et-top-navigation with plugin navigation output.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Integrations;

use LOW_MM\Nav\NavEnvironment;
use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps only the #et-top-navigation block inside Divi's main header.
 */
class DiviHeaderOverride {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( ! NavEnvironment::is_divi() || ! function_exists( 'et_get_option' ) ) {
			return;
		}

		add_filter( 'et_html_main_header', array( $this, 'filter_main_header' ), 20 );
		add_action( 'wp', array( $this, 'disable_divi_mobile_nav' ), 1 );
	}

	/**
	 * Stop Divi from printing #et_mobile_nav_menu inside the header.
	 *
	 * @return void
	 */
	public function disable_divi_mobile_nav(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		remove_action( 'et_header_top', 'et_add_mobile_navigation' );
	}

	/**
	 * Whether the override should run on this request.
	 *
	 * @return bool
	 */
	private function is_active(): bool {
		if ( is_admin() || ! FrontendSettings::override_divi_header() ) {
			return false;
		}

		if ( function_exists( 'et_builder_is_product_tour_enabled' ) && et_builder_is_product_tour_enabled() ) {
			return false;
		}

		if ( is_page_template( 'page-template-blank.php' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Replace Divi's #et-top-navigation with plugin navigation markup.
	 *
	 * @param string $main_header Divi main header HTML.
	 * @return string
	 */
	public function filter_main_header( string $main_header ): string {
		if ( ! $this->is_active() || '' === $main_header ) {
			return $main_header;
		}

		$navigation = $this->render_navigation_html();
		if ( '' === $navigation ) {
			return $main_header;
		}

		$replaced = $this->replace_element_by_id( $main_header, 'et-top-navigation', $navigation );

		if ( '' === $replaced ) {
			return $main_header;
		}

		return $this->strip_divi_mobile_nav_markup( $replaced );
	}

	/**
	 * Remove any leftover Divi mobile nav markup from the header HTML.
	 *
	 * @param string $html Header HTML.
	 * @return string
	 */
	private function strip_divi_mobile_nav_markup( string $html ): string {
		$stripped = $this->replace_element_by_id( $html, 'et_mobile_nav_menu', '' );

		return '' !== $stripped ? $stripped : $html;
	}

	/**
	 * Render the plugin primary navigation block.
	 *
	 * @return string
	 */
	private function render_navigation_html(): string {
		$menu_height  = absint( et_get_option( 'menu_height', '66' ) );
		$fixed_height = absint( et_get_option( 'minimized_menu_height', '40' ) );
		$menu_class   = 'nav low-mm-nav';

		if ( 'on' === et_get_option( 'divi_disable_toptier' ) ) {
			$menu_class .= ' et_disable_top_tier';
		}

		ob_start();
		wp_nav_menu(
			array(
				'theme_location' => NavEnvironment::get_header_nav_location(),
				'container'      => 'nav',
				'container_id'   => 'top-menu-nav',
				'container_class'=> 'low-mm-divi-menu-nav low-mm-nav-container low-mega-menu',
				'menu_class'     => $menu_class,
				'menu_id'        => 'top-menu',
				'fallback_cb'    => false,
				'depth'          => 0,
			)
		);
		$menu_html = trim( (string) ob_get_clean() );

		if ( '' === $menu_html ) {
			return '';
		}

		/**
		 * Navigation HTML that replaces Divi's #et-top-navigation.
		 *
		 * @param string $menu_html Menu markup.
		 */
		$menu_html = (string) apply_filters( 'low_mm_divi_header_menu_html', $menu_html );

		return sprintf(
			'<div id="et-top-navigation" class="low-mm-header-navigation low-mm-divi-navigation low-mm-nav-container" data-height="%1$s" data-fixed-height="%2$s" style="--low-mm-divi-nav-height:%1$spx">%3$s</div>',
			esc_attr( (string) $menu_height ),
			esc_attr( (string) $fixed_height ),
			$menu_html
		);
	}

	/**
	 * Replace the first element with a given id attribute.
	 *
	 * @param string $html        Source HTML.
	 * @param string $element_id  Element id without hash.
	 * @param string $replacement Replacement HTML.
	 * @return string
	 */
	private function replace_element_by_id( string $html, string $element_id, string $replacement ): string {
		$patterns = array(
			'id="' . $element_id . '"',
			"id='" . $element_id . "'",
		);

		$start = false;
		foreach ( $patterns as $pattern ) {
			$attr_pos = strpos( $html, $pattern );
			if ( false === $attr_pos ) {
				continue;
			}

			$start = strrpos( substr( $html, 0, $attr_pos ), '<div' );
			if ( false !== $start ) {
				break;
			}
		}

		if ( false === $start ) {
			return '';
		}

		$depth = 0;
		$len   = strlen( $html );

		for ( $i = $start; $i < $len; $i++ ) {
			if ( '<div' === substr( $html, $i, 4 ) ) {
				$depth++;
				continue;
			}

			if ( '</div>' === substr( $html, $i, 6 ) ) {
				$depth--;
				if ( 0 === $depth ) {
					$end = $i + 6;
					return substr( $html, 0, $start ) . $replacement . substr( $html, $end );
				}
			}
		}

		return '';
	}
}
