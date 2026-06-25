<?php
/**
 * REST API for front-end mega menu search.
 *
 * @package LOW_MM
 */

namespace LOW_MM\REST;

use LOW_MM\Utils\FrontendSettings;

defined( 'ABSPATH' ) || exit;

/**
 * Public, nonce-checked search endpoint returning rich post/page results.
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
	 * @return \WP_REST_Response
	 */
	public function get_results( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! FrontendSettings::search_enabled() ) {
			return rest_ensure_response( array( 'results' => array() ) );
		}

		$query = trim( (string) $request['q'] );

		if ( mb_strlen( $query ) < self::MIN_CHARS ) {
			return rest_ensure_response( array( 'results' => array() ) );
		}

		$search = new \WP_Query(
			array(
				's'                      => $query,
				'post_type'              => FrontendSettings::search_post_types(),
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

		return rest_ensure_response( array( 'results' => $results ) );
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
		$excerpt    = wp_strip_all_tags( get_the_excerpt( $post ) );

		return array(
			'id'        => (string) $post->ID,
			'title'     => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES ),
			'url'       => (string) get_permalink( $post ),
			'typeLabel' => (string) $type_label,
			'thumbnail' => $thumbnail,
			'excerpt'   => wp_trim_words( $excerpt, 18, '…' ),
		);
	}
}
