<?php
/**
 * Restores classic menu admin access for block themes.
 *
 * Block themes (Twenty Twenty-Four, Twenty Twenty-Five, etc.) hide Appearance → Menus
 * because they use the Site Editor Navigation block instead. LOW Mega Menu attachments
 * are stored on classic nav menu items, so we re-enable the classic menus screen.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Nav;

use LOW_MM\PostTypes\MegaMenuCPT;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures classic nav menus remain available in wp-admin.
 */
class ClassicMenusSupport {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'enable_menu_theme_support' ), 100 );
		add_action( 'admin_menu', array( $this, 'restore_menus_submenu' ), 100 );
		add_action( 'admin_menu', array( $this, 'register_plugin_menus_link' ), 20 );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	/**
	 * Allow the classic nav-menus.php screen and register a theme menu location when needed.
	 *
	 * Block themes often register no menu locations. Classic themes already register
	 * their own locations on after_setup_theme — we sniff those and do not add ours.
	 *
	 * @return void
	 */
	public function enable_menu_theme_support(): void {
		if ( ! NavEnvironment::should_register_primary_location() ) {
			return;
		}

		add_theme_support( 'menus' );

		/**
		 * Whether LOW Mega Menu should register a default primary nav menu location.
		 *
		 * Only runs when the theme registered no menu locations (typical block themes).
		 *
		 * @param bool $register Register the primary location.
		 */
		if ( ! apply_filters( 'low_mm_register_primary_nav_menu', true ) ) {
			return;
		}

		register_nav_menus(
			array(
				'primary' => __( 'Primary Navigation', 'low-mega-menu' ),
			)
		);
	}

	/**
	 * Whether the active theme (or another plugin) already registered nav menu locations.
	 *
	 * @return bool
	 */
	public static function theme_has_classic_nav_locations(): bool {
		return NavEnvironment::has_registered_menu_locations();
	}

	/**
	 * Nav menu location slug used for header output and admin guidance.
	 *
	 * @return string
	 */
	public static function get_header_nav_location(): string {
		return NavEnvironment::get_header_nav_location();
	}

	/**
	 * Human-readable label for the header nav menu location.
	 *
	 * @return string
	 */
	public static function get_header_nav_location_label(): string {
		return NavEnvironment::get_header_nav_location_label();
	}

	/**
	 * Re-add Appearance → Menus if a block theme removed it.
	 *
	 * @return void
	 */
	public function restore_menus_submenu(): void {
		global $submenu;

		if ( ! NavEnvironment::should_restore_menus_admin() ) {
			return;
		}

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		if ( isset( $submenu['themes.php'] ) ) {
			foreach ( $submenu['themes.php'] as $item ) {
				if ( isset( $item[2] ) && 'nav-menus.php' === $item[2] ) {
					return;
				}
			}
		}

		add_submenu_page(
			'themes.php',
			__( 'Menus', 'low-mega-menu' ),
			__( 'Menus', 'low-mega-menu' ),
			'edit_theme_options',
			'nav-menus.php',
			''
		);
	}

	/**
	 * Add a shortcut under Mega Menus for attaching panels to menu items.
	 *
	 * @return void
	 */
	public function register_plugin_menus_link(): void {
		add_submenu_page(
			'edit.php?post_type=' . MegaMenuCPT::POST_TYPE,
			__( 'Site Navigation Menus', 'low-mega-menu' ),
			__( 'Site Navigation Menus', 'low-mega-menu' ),
			'edit_theme_options',
			'low-mm-nav-menus',
			array( $this, 'render_nav_menus_landing' )
		);
	}

	/**
	 * Landing page that explains the two menu types and links to classic menus.
	 *
	 * @return void
	 */
	public function render_nav_menus_landing(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage menus.', 'low-mega-menu' ) );
		}

		$menus = wp_get_nav_menus();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Site Navigation Menus', 'low-mega-menu' ); ?></h1>
			<p>
				<?php esc_html_e( 'This is not the same as Mega Menus (panel content). Site navigation menus are the lists of links on your site — Home, About, Contact, etc. You attach a mega menu panel to an item in a site navigation menu.', 'low-mega-menu' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">
					<?php esc_html_e( 'Open classic menu editor', 'low-mega-menu' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php?action=edit&menu=0' ) ); ?>">
					<?php esc_html_e( 'Create a new site menu', 'low-mega-menu' ); ?>
				</a>
			</p>
			<?php if ( ! empty( $menus ) ) : ?>
				<h2><?php esc_html_e( 'Your site navigation menus', 'low-mega-menu' ); ?></h2>
				<ul>
					<?php foreach ( $menus as $menu ) : ?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ) ); ?>">
								<?php echo esc_html( $menu->name ); ?>
							</a>
							(<?php echo esc_html( sprintf(
								/* translators: %d: number of menu items */
								_n( '%d item', '%d items', (int) $menu->count, 'low-mega-menu' ),
								(int) $menu->count
							) ); ?>)
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="notice notice-warning inline">
					<p><?php esc_html_e( 'No site navigation menus exist yet. Click “Create a new site menu” to add one.', 'low-mega-menu' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render contextual admin notices.
	 *
	 * @return void
	 */
	public function render_admin_notices(): void {
		if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}

		if ( 'nav-menus' === $screen->id ) {
			$this->render_nav_menus_screen_notice();
			return;
		}

		if ( in_array( $screen->id, array( 'edit-mega_menu', 'mega_menu' ), true ) && NavEnvironment::is_block_theme() ) {
			printf(
				'<div class="notice notice-info"><p>%s</p></div>',
				esc_html__(
					'Mega Menus here are panel content only (columns and modules). To attach them to site links, go to Mega Menus → Site Navigation Menus.',
					'low-mega-menu'
				)
			);
		}
	}

	/**
	 * Help users find or create classic navigation menus on nav-menus.php.
	 *
	 * @return void
	 */
	private function render_nav_menus_screen_notice(): void {
		$menus       = wp_get_nav_menus();
		$active_menu = isset( $_GET['menu'] ) ? (int) $_GET['menu'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( NavEnvironment::is_block_theme() ) {
			echo '<div class="notice notice-info"><p>';
			printf(
				/* translators: %s: theme menu location label, e.g. Primary Navigation */
				esc_html__( 'Block themes normally use Site Editor navigation. LOW Mega Menu replaces the header Navigation block with your classic menu when you assign it to the “%s” location below and save.', 'low-mega-menu' ),
				esc_html( self::get_header_nav_location_label() )
			);
			echo '</p></div>';
		} elseif ( NavEnvironment::likely_uses_module_menu_picker() ) {
			echo '<div class="notice notice-info"><p>';
			esc_html_e( 'Many themes and page builders assign menus in a header or Menu module — not only via theme locations below. If your header uses a module menu picker, select this WordPress menu there after saving.', 'low-mega-menu' );
			echo '</p></div>';
		}

		if ( $active_menu > 0 ) {
			$locations      = get_nav_menu_locations();
			$location_slug  = self::get_header_nav_location();
			$header_menu    = isset( $locations[ $location_slug ] ) ? (int) $locations[ $location_slug ] : 0;
			$is_header_menu = $header_menu === $active_menu;
			$has_menu_items = wp_get_nav_menu_items( $active_menu );

			if ( ! $is_header_menu && ! NavEnvironment::likely_uses_module_menu_picker() ) {
				echo '<div class="notice notice-warning"><p>';
				printf(
					/* translators: %s: theme menu location label */
					esc_html__( 'This menu is not assigned to “%s”. Check that location under Menu Settings at the bottom of this page, then click Save Menu — otherwise it will not appear in the header.', 'low-mega-menu' ),
					esc_html( self::get_header_nav_location_label() )
				);
				echo '</p></div>';
			}

			if ( empty( $has_menu_items ) ) {
				echo '<div class="notice notice-warning"><p>';
				esc_html_e( 'This menu has no items yet. Add pages or custom links from the left column, then click Save Menu.', 'low-mega-menu' );
				echo '</p></div>';
			}
		}

		if ( empty( $menus ) ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'No site navigation menu exists yet. Enter a menu name in the left panel (e.g. “Main Menu”), click “Create Menu”, then add pages/links from the left column.', 'low-mega-menu' );
			echo '</p></div>';
			return;
		}

		if ( $active_menu <= 0 ) {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'Select which menu to edit: use the menu tabs at the top of this page, or choose one below.', 'low-mega-menu' );
			echo '</p><ul style="list-style:disc;margin-left:1.5em;">';
			foreach ( $menus as $menu ) {
				printf(
					'<li><a href="%1$s">%2$s</a></li>',
					esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ) ),
					esc_html( $menu->name )
				);
			}
			echo '</ul></div>';
		}
	}
}
