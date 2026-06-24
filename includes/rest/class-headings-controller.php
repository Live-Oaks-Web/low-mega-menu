<?php
/**
 * REST API for heading extraction (Scroll To module builder).
 *
 * @package LOW_MM
 */

namespace LOW_MM\REST;

use LOW_MM\Modules\ScrollTo\HeadingParser;

defined( 'ABSPATH' ) || exit;

/**
 * Returns headings parsed from a post's rendered content.
 */
class HeadingsController {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'low-mm/v1';

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
			'/headings/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_headings' ),
					'permission_callback' => array( $this, 'can_read_headings' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => static function ( $value ) {
								return is_numeric( $value ) && (int) $value > 0;
							},
						),
					),
				),
			)
		);
	}

	/**
	 * GET handler — return headings for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_headings( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error(
				'low_mm_headings_not_found',
				__( 'Post not found.', 'low-mega-menu' ),
				array( 'status' => 404 )
			);
		}

		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return new \WP_Error(
				'low_mm_headings_invalid_type',
				__( 'This post type cannot be used for scroll targets.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		$headings = HeadingParser::extract_from_post( $post_id );

		return rest_ensure_response(
			array(
				'post_id'  => $post_id,
				'title'    => get_the_title( $post ),
				'headings' => $headings,
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool
	 */
	public function can_read_headings(): bool {
		return current_user_can( 'edit_posts' );
	}
}
