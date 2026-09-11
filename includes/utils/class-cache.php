<?php
/**
 * Transient / request caches for front-end and admin hot paths.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Shared cache keys and invalidation for expensive work.
 */
class Cache {

	/**
	 * Prefix for post-query HTML transients.
	 */
	public const POST_QUERY_PREFIX = 'low_mm_pq_';

	/**
	 * Prefix for heading extraction transients.
	 */
	public const HEADINGS_PREFIX = 'low_mm_hd_';

	/**
	 * Prefix for rewritten public CSS.
	 */
	public const CSS_PREFIX = 'low_mm_css_';

	/**
	 * Prefix for search result payloads.
	 */
	public const SEARCH_PREFIX = 'low_mm_sr_';

	/**
	 * Prefix for search rate-limit counters.
	 */
	public const SEARCH_RL_PREFIX = 'low_mm_srl_';

	/**
	 * Request-local layout cache (post ID => layout|null|false sentinel).
	 *
	 * @var array<int, array<string, mixed>|null|false>
	 */
	private static $layouts = array();

	/**
	 * Remember a front-end layout for this request.
	 *
	 * @param int                          $post_id Mega menu ID.
	 * @param array<string, mixed>|null    $layout  Layout or null.
	 * @return void
	 */
	public static function set_layout( int $post_id, ?array $layout ): void {
		self::$layouts[ $post_id ] = $layout;
	}

	/**
	 * Fetch a request-cached layout. Returns false when not yet cached.
	 *
	 * @param int $post_id Mega menu ID.
	 * @return array<string, mixed>|null|false
	 */
	public static function get_layout( int $post_id ) {
		return array_key_exists( $post_id, self::$layouts ) ? self::$layouts[ $post_id ] : false;
	}

	/**
	 * Drop request-local layout cache (after save).
	 *
	 * @param int|null $post_id Specific ID or all.
	 * @return void
	 */
	public static function clear_layouts( ?int $post_id = null ): void {
		if ( null === $post_id ) {
			self::$layouts = array();
			return;
		}

		unset( self::$layouts[ $post_id ] );
	}

	/**
	 * Invalidate post-query HTML caches (version bump via site option).
	 *
	 * @return void
	 */
	public static function bump_post_query_generation(): void {
		update_option( 'low_mm_pq_gen', time(), false );
	}

	/**
	 * Current post-query cache generation.
	 *
	 * @return int
	 */
	public static function post_query_generation(): int {
		return (int) get_option( 'low_mm_pq_gen', 1 );
	}

	/**
	 * Register invalidation hooks.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_action( 'save_post', array( self::class, 'on_content_change' ), 20, 1 );
		add_action( 'deleted_post', array( self::class, 'on_content_change' ), 20, 1 );
		add_action( 'edited_term', array( self::class, 'bump_post_query_generation' ) );
		add_action( 'created_term', array( self::class, 'bump_post_query_generation' ) );
		add_action( 'delete_term', array( self::class, 'bump_post_query_generation' ) );
		add_action( 'update_option_' . FrontendSettings::OPTION_MOBILE_BREAKPOINT, array( self::class, 'clear_css_transients' ) );
	}

	/**
	 * Content changed — bump post-query generation and clear heading cache for that post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_content_change( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( 'revision' === $post->post_type ) {
			return;
		}

		self::bump_post_query_generation();
		delete_transient( self::HEADINGS_PREFIX . $post_id );
		self::clear_layouts( $post_id );
	}

	/**
	 * Drop rewritten CSS transients (best-effort; generation is also versioned).
	 *
	 * @return void
	 */
	public static function clear_css_transients(): void {
		// Keys include version + breakpoint; next enqueue rebuilds. No global scan needed.
	}
}
