<?php
/**
 * REST API for front-end mega menu search.
 *
 * @package LOW_MM
 */

namespace LOW_MM\REST;

use LOW_MM\Utils\Cache;
use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Public search endpoint with rate limiting and short result caching.
 */
class SearchController {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'low-mm/v1';

	/**
	 * Minimum query length honored server-side.
	 */
	public const MIN_CHARS = 2;

	/**
	 * Max search requests per IP per minute.
	 */
	public const RATE_LIMIT = 30;

	/**
	 * How long to cache identical search payloads (seconds).
	 */
	public const RESULT_TTL = 60;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/search',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_results' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'q' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * GET handler — return search results.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_results( \WP_REST_Request $request ) {
		if ( ! FrontendSettings::search_enabled() ) {
			return rest_ensure_response( array( 'results' => array() ) );
		}

		if ( $this->is_rate_limited() ) {
			return new \WP_Error(
				'low_mm_search_rate_limited',
				__( 'Too many search requests. Please try again shortly.', 'low-mega-menu' ),
				array( 'status' => 429 )
			);
		}

		$query = trim( (string) $request['q'] );

		if ( mb_strlen( $query ) < self::MIN_CHARS ) {
			return rest_ensure_response( array( 'results' => array() ) );
		}

		$post_types = FrontendSettings::search_post_types();
		$cache_key  = Cache::SEARCH_PREFIX . md5( strtolower( $query ) . '|' . implode( ',', $post_types ) . '|' . FrontendSettings::search_results_count() );
		$cached     = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return rest_ensure_response( array( 'results' => $cached ) );
		}

		$search = new \WP_Query(
			array(
				's'                      => $query,
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => FrontendSettings::search_results_count(),
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$results = array();

		foreach ( $search->posts as $post ) {
			$results[] = $this->format_result( $post );
		}

		set_transient( $cache_key, $results, self::RESULT_TTL );

		return rest_ensure_response( array( 'results' => $results ) );
	}

	/**
	 * Simple per-IP rate limit via transients.
	 *
	 * @return bool True when limited.
	 */
	private function is_rate_limited(): bool {
		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = (string) wp_unslash( $_SERVER['REMOTE_ADDR'] );
		}

		$key   = Cache::SEARCH_RL_PREFIX . md5( $ip ? $ip : 'unknown' );
		$count = (int) get_transient( $key );

		if ( $count >= self::RATE_LIMIT ) {
			return true;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Shape a single post into a result payload.
	 *
	 * @param \WP_Post $post Post object.
	 * @return array<string, string>
	 */
	private function format_result( \WP_Post $post ): array {
		$type_obj   = get_post_type_object( $post->post_type );
		$type_label = $type_obj instanceof \WP_Post_Type ? $type_obj->labels->singular_name : '';
		$thumbnail  = (string) get_the_post_thumbnail_url( $post, 'thumbnail' );

		return array(
			'id'        => (string) $post->ID,
			'title'     => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES ),
			'url'       => (string) get_permalink( $post ),
			'typeLabel' => (string) $type_label,
			'thumbnail' => $thumbnail,
			'excerpt'   => $this->format_excerpt( $post ),
		);
	}

	/**
	 * Prefer the authored excerpt; otherwise use the first 8 words of content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function format_excerpt( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			$excerpt = wp_strip_all_tags( $post->post_excerpt );
			$excerpt = html_entity_decode( $excerpt, ENT_QUOTES );
			$excerpt = trim( preg_replace( '/\s+/u', ' ', $excerpt ) ?? $excerpt );

			if ( '' !== $excerpt ) {
				return $excerpt;
			}
		}

		// Avoid loading huge posts: trim from a capped substring of content.
		$raw    = (string) $post->post_content;
		$source = wp_strip_all_tags( strlen( $raw ) > 2000 ? substr( $raw, 0, 2000 ) : $raw );
		$source = html_entity_decode( $source, ENT_QUOTES );

		return wp_trim_words( $source, 8, '…' );
	}
}
