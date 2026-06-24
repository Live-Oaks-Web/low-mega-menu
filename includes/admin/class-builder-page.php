<?php
/**
 * Mega menu builder admin page.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Admin;

use LOW_MM\Modules\ModuleRegistry;
use LOW_MM\PostTypes\MegaMenuCPT;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the React builder screen.
 */
class BuilderPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'low-mm-builder';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'get_edit_post_link', array( $this, 'filter_edit_post_link' ), 10, 2 );
		add_filter( 'redirect_post_location', array( $this, 'redirect_new_post_to_builder' ), 10, 2 );
	}

	/**
	 * Register builder submenu under Mega Menus.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . MegaMenuCPT::POST_TYPE,
			__( 'Mega Menu Builder', 'low-mega-menu' ),
			__( 'Builder', 'low-mega-menu' ),
			'edit_posts',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the builder mount point.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $post_id <= 0 || ! $this->is_valid_mega_menu( $post_id ) ) {
			$this->render_landing_page( $post_id );
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to edit this mega menu.', 'low-mega-menu' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html( get_the_title( $post_id ) ) . '</h1>';
		echo '<div id="low-mm-builder-root" data-post-id="' . esc_attr( (string) $post_id ) . '"></div></div>';
	}

	/**
	 * Landing screen when no mega menu is selected (avoids late redirects).
	 *
	 * @param int $post_id Requested post ID (may be invalid).
	 * @return void
	 */
	private function render_landing_page( int $post_id ): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Mega Menu Builder', 'low-mega-menu' ) . '</h1>';

		if ( $post_id > 0 ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'That mega menu could not be found.', 'low-mega-menu' ) . '</p></div>';
		} else {
			echo '<p class="description">' . esc_html__( 'Choose a mega menu to edit, or create a new one.', 'low-mega-menu' ) . '</p>';
		}

		$menus = get_posts(
			array(
				'post_type'      => MegaMenuCPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( empty( $menus ) ) {
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=' . MegaMenuCPT::POST_TYPE ) ) . '">';
			echo esc_html__( 'Create your first mega menu', 'low-mega-menu' ) . '</a></p>';
			echo '</div>';
			return;
		}

		echo '<ul class="low-mm-builder-landing-list">';
		foreach ( $menus as $menu ) {
			if ( ! current_user_can( 'edit_post', $menu->ID ) ) {
				continue;
			}

			$url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&post_id=' . $menu->ID );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $menu ) ) . '</a></li>';
		}
		echo '</ul>';

		echo '<p><a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=' . MegaMenuCPT::POST_TYPE ) ) . '">';
		echo esc_html__( 'Add new mega menu', 'low-mega-menu' ) . '</a></p>';
		echo '</div>';
	}

	/**
	 * Enqueue builder assets on the builder page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'mega_menu_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $post_id <= 0 || ! $this->is_valid_mega_menu( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$asset_file = LOW_MM_PLUGIN_DIR . 'admin-app/build/index.asset.php';
		$script_url = LOW_MM_PLUGIN_URL . 'admin-app/build/index.js';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_media();

		wp_enqueue_script(
			'low-mm-builder',
			$script_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		$style_path = LOW_MM_PLUGIN_DIR . 'admin-app/build/index.css';
		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'low-mm-builder',
				LOW_MM_PLUGIN_URL . 'admin-app/build/index.css',
				array( 'wp-components' ),
				$asset['version']
			);
		}

		wp_add_inline_script(
			'low-mm-builder',
			'window.lowMmBuilderData = ' . wp_json_encode(
				array(
					'postId'       => $post_id,
					'restNonce'    => wp_create_nonce( 'wp_rest' ),
					'restUrl'      => esc_url_raw( rest_url() ),
					'moduleTypes'  => ModuleRegistry::get_builder_definitions(),
					'defaultLayout' => \LOW_MM\Schema\LayoutSchema::default_layout(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Point mega menu edit links to the builder.
	 *
	 * @param string $link    Edit link.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function filter_edit_post_link( string $link, int $post_id ): string {
		if ( MegaMenuCPT::POST_TYPE === get_post_type( $post_id ) ) {
			return admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&post_id=' . $post_id );
		}

		return $link;
	}

	/**
	 * Send newly created mega menus to the builder after save.
	 *
	 * @param string $location Redirect URL.
	 * @param int    $post_id  Post ID.
	 * @return string
	 */
	public function redirect_new_post_to_builder( string $location, int $post_id ): string {
		if ( MegaMenuCPT::POST_TYPE !== get_post_type( $post_id ) ) {
			return $location;
		}

		return admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&post_id=' . $post_id );
	}

	/**
	 * Whether a post is an editable mega menu.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_valid_mega_menu( int $post_id ): bool {
		$post = get_post( $post_id );

		return $post instanceof \WP_Post && MegaMenuCPT::POST_TYPE === $post->post_type;
	}
}
