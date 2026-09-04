<?php
/**
 * GitHub-based plugin update checker.
 *
 * Checks public GitHub Releases and offers WordPress admin updates when a newer
 * version is published. Prefers an attached .zip release asset (recommended so
 * built CSS/JS ship with the update); falls back to the tag source archive.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Update;

use LOW_MM\Admin\SettingsPage;
use LOW_MM\PostTypes\MegaMenuCPT;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin into WordPress's update API using GitHub Releases.
 */
class GitHubUpdater {

	/**
	 * GitHub owner/repo.
	 */
	public const REPO = 'Live-Oaks-Web/low-mega-menu';

	/**
	 * Plugin folder / slug.
	 */
	public const SLUG = 'low-mega-menu';

	/**
	 * Transient key for cached release data.
	 */
	private const CACHE_KEY = 'low_mm_github_release';

	/**
	 * How long to cache the GitHub API response (seconds).
	 */
	private const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Plugin basename (low-mega-menu/low-mega-menu.php).
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Register update hooks.
	 */
	public function __construct() {
		$this->plugin_basename = plugin_basename( LOW_MM_PLUGIN_FILE );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );
		add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_post_low_mm_check_update', array( $this, 'handle_check_update' ) );
		add_action( 'admin_notices', array( $this, 'render_check_update_notice' ) );
	}

	/**
	 * Add Settings and Check for update links on plugins.php.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function plugin_action_links( array $links ): array {
		$settings_url = admin_url( 'edit.php?post_type=' . MegaMenuCPT::POST_TYPE . '&page=' . SettingsPage::PAGE_SLUG );
		$check_url    = wp_nonce_url(
			admin_url( 'admin-post.php?action=low_mm_check_update' ),
			'low_mm_check_update'
		);

		$extra = array(
			'settings'     => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Settings', 'low-mega-menu' )
			),
			'check_update' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $check_url ),
				esc_html__( 'Check for update', 'low-mega-menu' )
			),
		);

		return array_merge( $extra, $links );
	}

	/**
	 * Clear caches and force a GitHub update check, then return to plugins.php.
	 *
	 * @return void
	 */
	public function handle_check_update(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to update plugins for this site.', 'low-mega-menu' ) );
		}

		check_admin_referer( 'low_mm_check_update' );

		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		$release = $this->get_latest_release();
		$status  = 'unavailable';

		if ( is_array( $release ) && ! empty( $release['version'] ) ) {
			if ( version_compare( $release['version'], LOW_MM_VERSION, '>' ) ) {
				$status = 'available';
			} else {
				$status = 'current';
			}
		}

		$redirect = add_query_arg(
			array(
				'low_mm_update_check' => $status,
				'low_mm_remote'       => is_array( $release ) ? rawurlencode( (string) ( $release['version'] ?? '' ) ) : '',
			),
			admin_url( 'plugins.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Admin notice after a manual update check from plugins.php.
	 *
	 * @return void
	 */
	public function render_check_update_notice(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}

		$status = isset( $_GET['low_mm_update_check'] ) ? sanitize_key( wp_unslash( (string) $_GET['low_mm_update_check'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $status ) {
			return;
		}

		$remote = isset( $_GET['low_mm_remote'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['low_mm_remote'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'available' === $status ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: installed version, 2: available version */
						__( 'LOW Mega Menu %1$s — update %2$s is available. Use the update link below the plugin name.', 'low-mega-menu' ),
						LOW_MM_VERSION,
						$remote ? $remote : __( 'a newer version', 'low-mega-menu' )
					)
				)
			);
			return;
		}

		if ( 'current' === $status ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: installed version */
						__( 'LOW Mega Menu %s is up to date.', 'low-mega-menu' ),
						LOW_MM_VERSION
					)
				)
			);
			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Could not check GitHub for LOW Mega Menu updates. Try again later.', 'low-mega-menu' )
		);
	}

	/**
	 * Inject update data when GitHub has a newer release.
	 *
	 * @param object|null $transient Update transient.
	 * @return object|null
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		$remote_version = $release['version'];
		if ( ! $remote_version || ! version_compare( $remote_version, LOW_MM_VERSION, '>' ) ) {
			return $transient;
		}

		$transient->response[ $this->plugin_basename ] = (object) array(
			'slug'        => self::SLUG,
			'plugin'      => $this->plugin_basename,
			'new_version' => $remote_version,
			'url'         => $release['html_url'],
			'package'     => $release['download_url'],
			'icons'       => array(),
			'banners'     => array(),
			'tested'      => '',
			'requires'    => '6.0',
			'requires_php'=> '7.4',
		);

		return $transient;
	}

	/**
	 * Provide plugin information for the “View version details” modal.
	 *
	 * @param false|object|array $result  Result object or false.
	 * @param string             $action  API action.
	 * @param object             $args    Request arguments.
	 * @return false|object|array
	 */
	public function plugins_api( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! is_object( $args ) || self::SLUG !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$changelog = ! empty( $release['body'] )
			? wp_kses_post( wpautop( $release['body'] ) )
			: '<p>' . esc_html__( 'See the GitHub release notes for details.', 'low-mega-menu' ) . '</p>';

		return (object) array(
			'name'           => 'LOW Mega Menu',
			'slug'           => self::SLUG,
			'version'        => $release['version'],
			'author'         => '<a href="https://github.com/Live-Oaks-Web">Live Oaks Web</a>',
			'homepage'       => 'https://github.com/' . self::REPO,
			'requires'       => '6.0',
			'requires_php'   => '7.4',
			'download_link'  => $release['download_url'],
			'trunk'          => $release['download_url'],
			'last_updated'   => $release['published_at'],
			'sections'       => array(
				'description' => '<p>' . esc_html__( 'Build multi-column mega menu panels and attach them to WordPress nav menu items.', 'low-mega-menu' ) . '</p>',
				'changelog'   => $changelog,
			),
		);
	}

	/**
	 * Point the upgrader at the folder that contains low-mega-menu.php and ensure
	 * it is named `low-mega-menu` (GitHub zips often use a tag suffix or nest).
	 *
	 * @param string       $source        Source directory with trailing slash.
	 * @param string       $remote_source Remote upgrade directory.
	 * @param \WP_Upgrader $upgrader      Upgrader instance.
	 * @param array        $hook_extra    Extra hook data.
	 * @return string|\WP_Error
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wp_filesystem;

		if ( empty( $source ) || empty( $remote_source ) || ! is_object( $wp_filesystem ) ) {
			return $source;
		}

		$explicit = $this->is_our_upgrade( $hook_extra );
		$found    = $this->find_plugin_root( (string) $source );

		// Another plugin's package (or unrelated upgrader run).
		if ( ! $explicit && ! $found ) {
			return $source;
		}

		if ( ! $found ) {
			return new \WP_Error(
				'low_mm_upgrade_missing_main_file',
				__( 'The update package did not contain low-mega-menu.php.', 'low-mega-menu' )
			);
		}

		$found      = trailingslashit( $found );
		$desired    = trailingslashit( $remote_source ) . self::SLUG . '/';
		$found_base = basename( untrailingslashit( $found ) );

		if ( self::SLUG === $found_base && untrailingslashit( $found ) === untrailingslashit( $desired ) ) {
			return $found;
		}

		// If something already occupies the desired path, remove it first.
		if ( $wp_filesystem->exists( $desired ) && untrailingslashit( $found ) !== untrailingslashit( $desired ) ) {
			$wp_filesystem->delete( $desired, true );
		}

		if ( untrailingslashit( $found ) === untrailingslashit( $desired ) ) {
			return $desired;
		}

		if ( $wp_filesystem->move( $found, $desired ) ) {
			return $desired;
		}

		// Fallback: copy then delete (some hosts block move across directories).
		if ( copy_dir( $found, $desired ) ) {
			$wp_filesystem->delete( $found, true );
			return $desired;
		}

		return new \WP_Error(
			'low_mm_upgrade_rename_failed',
			__( 'Could not move the update package into the low-mega-menu plugin directory.', 'low-mega-menu' )
		);
	}

	/**
	 * Whether this upgrader run is for LOW Mega Menu.
	 *
	 * @param array $hook_extra Extra hook data.
	 * @return bool
	 */
	private function is_our_upgrade( array $hook_extra ): bool {
		$plugin = isset( $hook_extra['plugin'] ) ? (string) $hook_extra['plugin'] : '';
		if ( $plugin ) {
			return $plugin === $this->plugin_basename;
		}

		if ( isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
			return in_array( $this->plugin_basename, $hook_extra['plugins'], true );
		}

		// When WordPress omits plugin identifiers, only claim packages that look like ours.
		return false;
	}

	/**
	 * Locate the directory that contains the main plugin file.
	 *
	 * @param string $source Extracted source path.
	 * @return string|null Absolute path without requiring a trailing slash.
	 */
	private function find_plugin_root( string $source ): ?string {
		global $wp_filesystem;

		$source = untrailingslashit( $source );
		$main   = 'low-mega-menu.php';

		if ( $wp_filesystem->exists( $source . '/' . $main ) ) {
			return $source;
		}

		$dirlist = $wp_filesystem->dirlist( $source );
		if ( ! is_array( $dirlist ) ) {
			return null;
		}

		foreach ( $dirlist as $name => $entry ) {
			if ( empty( $entry['type'] ) || 'd' !== $entry['type'] ) {
				continue;
			}

			$candidate = $source . '/' . $name;
			if ( $wp_filesystem->exists( $candidate . '/' . $main ) ) {
				return $candidate;
			}

			// One more level for oddly nested packages.
			$nested = $wp_filesystem->dirlist( $candidate );
			if ( ! is_array( $nested ) ) {
				continue;
			}

			foreach ( $nested as $nested_name => $nested_entry ) {
				if ( empty( $nested_entry['type'] ) || 'd' !== $nested_entry['type'] ) {
					continue;
				}

				$deep = $candidate . '/' . $nested_name;
				if ( $wp_filesystem->exists( $deep . '/' . $main ) ) {
					return $deep;
				}
			}
		}

		return null;
	}

	/**
	 * Clear the release cache after a successful upgrade.
	 *
	 * @param \WP_Upgrader $upgrader Upgrader instance.
	 * @param array        $options  Upgrade options.
	 * @return void
	 */
	public function clear_cache( $upgrader, $options ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		if ( empty( $options['type'] ) || 'plugin' !== $options['type'] ) {
			return;
		}

		$plugins = array();
		if ( ! empty( $options['plugins'] ) && is_array( $options['plugins'] ) ) {
			$plugins = $options['plugins'];
		} elseif ( ! empty( $options['plugin'] ) ) {
			$plugins = array( $options['plugin'] );
		}

		if ( $plugins && ! in_array( $this->plugin_basename, $plugins, true ) ) {
			return;
		}

		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Fetch and normalize the latest non-prerelease GitHub release.
	 *
	 * @return array{version:string,download_url:string,html_url:string,body:string,published_at:string}|null
	 */
	private function get_latest_release(): ?array {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_array( $cached ) && ! empty( $cached['version'] ) ? $cached : null;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::CACHE_KEY, 'unavailable', HOUR_IN_SECONDS );
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! empty( $data['prerelease'] ) || ! empty( $data['draft'] ) ) {
			set_transient( self::CACHE_KEY, 'unavailable', HOUR_IN_SECONDS );
			return null;
		}

		$version = $this->normalize_version( (string) ( $data['tag_name'] ?? '' ) );
		if ( '' === $version ) {
			set_transient( self::CACHE_KEY, 'unavailable', HOUR_IN_SECONDS );
			return null;
		}

		$download_url = $this->pick_download_url( $data );
		if ( '' === $download_url ) {
			set_transient( self::CACHE_KEY, 'unavailable', HOUR_IN_SECONDS );
			return null;
		}

		$release = array(
			'version'      => $version,
			'download_url' => $download_url,
			'html_url'     => (string) ( $data['html_url'] ?? ( 'https://github.com/' . self::REPO . '/releases' ) ),
			'body'         => (string) ( $data['body'] ?? '' ),
			'published_at' => (string) ( $data['published_at'] ?? '' ),
		);

		set_transient( self::CACHE_KEY, $release, self::CACHE_TTL );

		return $release;
	}

	/**
	 * Prefer an attached .zip asset; otherwise use the tag zipball.
	 *
	 * @param array<string, mixed> $data GitHub release payload.
	 * @return string
	 */
	private function pick_download_url( array $data ): string {
		$assets = isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array();

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) ) {
				continue;
			}

			$name = (string) ( $asset['name'] ?? '' );
			$url  = (string) ( $asset['browser_download_url'] ?? '' );

			if ( '' === $url || ! preg_match( '/\.zip$/i', $name ) ) {
				continue;
			}

			// Prefer a package clearly named for this plugin.
			if ( preg_match( '/low-?mega-?menu/i', $name ) ) {
				return $url;
			}
		}

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) ) {
				continue;
			}

			$name = (string) ( $asset['name'] ?? '' );
			$url  = (string) ( $asset['browser_download_url'] ?? '' );

			if ( $url && preg_match( '/\.zip$/i', $name ) ) {
				return $url;
			}
		}

		$tag = (string) ( $data['tag_name'] ?? '' );
		if ( '' === $tag ) {
			return '';
		}

		return 'https://github.com/' . self::REPO . '/archive/refs/tags/' . rawurlencode( $tag ) . '.zip';
	}

	/**
	 * Strip a leading "v" from tag names (v1.5.0 → 1.5.0).
	 *
	 * @param string $tag Tag name.
	 * @return string
	 */
	private function normalize_version( string $tag ): string {
		$tag = trim( $tag );
		if ( '' === $tag ) {
			return '';
		}

		if ( 0 === stripos( $tag, 'v' ) && isset( $tag[1] ) && is_numeric( $tag[1] ) ) {
			$tag = substr( $tag, 1 );
		}

		return $tag;
	}
}
