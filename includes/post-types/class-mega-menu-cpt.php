<?php
/**
 * Registers the mega_menu custom post type.
 *
 * @package LOW_MM
 */

namespace LOW_MM\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Mega menu CPT registration and related hooks.
 */
class MegaMenuCPT {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'mega_menu';

	/**
	 * Layout meta key.
	 */
	public const LAYOUT_META_KEY = '_low_mm_layout';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'exclude_from_sitemaps' ) );
		add_filter( 'wp_robots', array( $this, 'noindex_singular' ) );
	}

	/**
	 * Register the mega_menu post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Mega Menus', 'low-mega-menu' ),
					'singular_name'      => __( 'Mega Menu', 'low-mega-menu' ),
					'add_new'            => __( 'Add New', 'low-mega-menu' ),
					'add_new_item'       => __( 'Add New Mega Menu', 'low-mega-menu' ),
					'edit_item'          => __( 'Edit Mega Menu', 'low-mega-menu' ),
					'new_item'           => __( 'New Mega Menu', 'low-mega-menu' ),
					'view_item'          => __( 'View Mega Menu', 'low-mega-menu' ),
					'search_items'       => __( 'Search Mega Menus', 'low-mega-menu' ),
					'not_found'          => __( 'No mega menus found.', 'low-mega-menu' ),
					'not_found_in_trash' => __( 'No mega menus found in Trash.', 'low-mega-menu' ),
					'all_items'          => __( 'All Mega Menus', 'low-mega-menu' ),
					'menu_name'          => __( 'Mega Menus', 'low-mega-menu' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 25,
				'menu_icon'           => 'dashicons-menu',
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * Remove mega_menu from sitemap post types.
	 *
	 * @param array<string, \WP_Post_Type> $post_types Registered sitemap post types.
	 * @return array<string, \WP_Post_Type>
	 */
	public function exclude_from_sitemaps( array $post_types ): array {
		unset( $post_types[ self::POST_TYPE ] );

		return $post_types;
	}

	/**
	 * Add noindex robots directive on singular mega_menu views.
	 *
	 * @param array<string, bool|string> $robots Robots directives.
	 * @return array<string, bool|string>
	 */
	public function noindex_singular( array $robots ): array {
		if ( is_singular( self::POST_TYPE ) ) {
			$robots['noindex'] = true;
		}

		return $robots;
	}
}
