<?php
/**
 * Universal wp_nav_menu() integration for all themes.
 *
 * Enhances menu item markup via core WordPress filters so custom theme walkers
 * (Divi, Astra, etc.) and default walkers all receive mega menu panels.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Theme-agnostic front-end nav menu hooks.
 */
class NavMenuIntegration {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'nav_menu_css_class', array( $this, 'filter_menu_item_classes' ), 10, 4 );
		add_filter( 'nav_menu_link_attributes', array( $this, 'filter_menu_link_attributes' ), 10, 4 );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'enhance_menu_item' ), 10, 4 );
		add_filter( 'wp_nav_menu_args', array( $this, 'mark_menu_args' ), 100 );
		add_filter( 'wp_nav_menu', array( $this, 'wrap_enhanced_menu' ), 15, 2 );
	}

	/**
	 * Add plugin classes to the list item element.
	 *
	 * walker_nav_menu_start_el does not include the opening <li>, so classes must
	 * be applied here for themes that use custom walkers (Divi, etc.).
	 *
	 * @param string[] $classes Menu item CSS classes.
	 * @param \WP_Post $item    Menu item.
	 * @param object   $args    wp_nav_menu() arguments.
	 * @param int      $depth   Depth.
	 * @return string[]
	 */
	public function filter_menu_item_classes( array $classes, \WP_Post $item, $args, int $depth ): array {
		if ( is_admin() ) {
			return $classes;
		}

		$attached_id = NavMenuItemMeta::get_attached_id( (int) $item->ID );
		if ( ! \LOW_MM\Schema\LayoutSchema::has_renderable_layout( $attached_id ) ) {
			return $classes;
		}

		$classes[] = 'low-mm-has-panel';
		$classes[] = 'low-mm-nav-item--has-panel';

		return $classes;
	}

	/**
	 * Add data attributes to the menu item link for front-end binding.
	 *
	 * @param array<string, string> $atts  Link attributes.
	 * @param \WP_Post              $item  Menu item.
	 * @param object                $args  wp_nav_menu() arguments.
	 * @param int                   $depth Depth.
	 * @return array<string, string>
	 */
	public function filter_menu_link_attributes( array $atts, \WP_Post $item, $args, int $depth ): array {
		if ( is_admin() ) {
			return $atts;
		}

		$attached_id = NavMenuItemMeta::get_attached_id( (int) $item->ID );
		if ( ! \LOW_MM\Schema\LayoutSchema::has_renderable_layout( $attached_id ) ) {
			return $atts;
		}

		$atts['data-low-mega-menu'] = (string) $attached_id;

		if ( FrontendSettings::use_aria_expanded() ) {
			$atts['aria-haspopup'] = 'true';

			if ( ! isset( $atts['aria-expanded'] ) ) {
				$atts['aria-expanded'] = 'false';
			}
		}

		return $atts;
	}

	/**
	 * Enhance every menu item rendered through Walker_Nav_Menu.
	 *
	 * @param string   $item_output Menu item HTML.
	 * @param \WP_Post $item        Menu item.
	 * @param int      $depth       Depth.
	 * @param object   $args        wp_nav_menu() args.
	 * @return string
	 */
	public function enhance_menu_item( string $item_output, \WP_Post $item, int $depth, $args ): string {
		if ( is_admin() ) {
			return $item_output;
		}

		/**
		 * Whether to enhance a menu item with mega menu markup.
		 *
		 * @param bool     $enhance     Enhance this item.
		 * @param \WP_Post $item        Menu item.
		 * @param int      $depth       Menu depth.
		 * @param object   $args        wp_nav_menu() arguments.
		 */
		if ( ! apply_filters( 'low_mm_enhance_nav_menu_item', true, $item, $depth, $args ) ) {
			return $item_output;
		}

		return NavMenuItemEnhancer::enhance_start_el_output( $item_output, $item, $depth );
	}

	/**
	 * Add plugin container/classes so front-end JS can bind to any theme menu.
	 *
	 * @param array<string, mixed> $args wp_nav_menu() arguments.
	 * @return array<string, mixed>
	 */
	public function mark_menu_args( array $args ): array {
		if ( ! $this->should_enhance_menu( (object) $args ) ) {
			return $args;
		}

		$args = $this->apply_marker_classes( $args );

		/**
		 * wp_nav_menu() arguments after LOW Mega Menu markers are applied.
		 *
		 * @param array<string, mixed> $args Menu arguments.
		 */
		return apply_filters( 'low_mm_nav_menu_args', $args );
	}

	/**
	 * Wrap menus that gained panel markup but have no plugin container.
	 *
	 * @param string $nav_menu Rendered menu HTML.
	 * @param object $args     wp_nav_menu() arguments.
	 * @return string
	 */
	public function wrap_enhanced_menu( string $nav_menu, $args ): string {
		if ( is_admin() || '' === $nav_menu ) {
			return $nav_menu;
		}

		if ( false === strpos( $nav_menu, 'data-low-mega-menu' ) && false === strpos( $nav_menu, 'low-mm-panel' ) ) {
			return $nav_menu;
		}

		if ( false !== strpos( $nav_menu, 'low-mm-nav-container' ) ) {
			return $nav_menu;
		}

		return '<div class="low-mega-menu low-mm-nav-container">' . $nav_menu . '</div>';
	}

	/**
	 * Whether this menu render should receive plugin integration.
	 *
	 * @param object $args wp_nav_menu() arguments.
	 * @return bool
	 */
	private function should_enhance_menu( $args ): bool {
		if ( ! is_object( $args ) ) {
			return false;
		}

		$menu_id = NavMenuContext::resolve_menu_term_id( $args );

		if ( $menu_id > 0 ) {
			return NavMenuContext::menu_has_mega_attachment( $menu_id );
		}

		/**
		 * Whether to enhance a menu when its term ID cannot be resolved up front.
		 *
		 * Page builders often pass menus by ID in module output; default true so
		 * per-item enhancement still runs when attachments exist.
		 *
		 * @param bool   $enhance Enhance without a resolved menu ID.
		 * @param object $args    wp_nav_menu() arguments.
		 */
		return (bool) apply_filters( 'low_mm_enhance_unresolved_nav_menu', NavMenuContext::site_has_mega_attachment(), $args );
	}

	/**
	 * Append plugin marker classes to menu arguments.
	 *
	 * @param array<string, mixed> $args Menu arguments.
	 * @return array<string, mixed>
	 */
	private function apply_marker_classes( array $args ): array {
		if ( false === $args['container'] ) {
			$args['container']       = 'div';
			$args['container_class'] = 'low-mega-menu low-mm-nav-container';
		} elseif ( empty( $args['container_class'] ) ) {
			$args['container_class'] = 'low-mega-menu low-mm-nav-container';
		} elseif ( is_string( $args['container_class'] ) && false === strpos( $args['container_class'], 'low-mm-nav-container' ) ) {
			$args['container_class'] = 'low-mega-menu ' . $args['container_class'] . ' low-mm-nav-container';
		} elseif ( is_string( $args['container_class'] ) && false === strpos( $args['container_class'], 'low-mega-menu' ) ) {
			$args['container_class'] = 'low-mega-menu ' . $args['container_class'];
		}

		return $args;
	}
}
