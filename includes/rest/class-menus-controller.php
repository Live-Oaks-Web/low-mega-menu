<?php
/**
 * REST API controller for mega menu layout data.
 *
 * @package LOW_MM
 */

namespace LOW_MM\REST;

use LOW_MM\PostTypes\MegaMenuCPT;
use LOW_MM\Schema\LayoutSchema;
use LOW_MM\Schema\LayoutValidator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles GET/PATCH for /low-mm/v1/menus/{id}.
 */
class MenusController {

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
			'/menus/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_menu' ),
					'permission_callback' => array( $this, 'can_edit_menu' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => array( $this, 'validate_menu_id' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_menu' ),
					'permission_callback' => array( $this, 'can_edit_menu' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => array( $this, 'validate_menu_id' ),
						),
					),
				),
			)
		);
	}

	/**
	 * GET handler — return layout JSON for a mega_menu post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_menu( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$layout  = LayoutSchema::get_layout_or_default( $post_id );

		return rest_ensure_response( $layout );
	}

	/**
	 * PATCH/PUT handler — validate and save layout JSON.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_menu( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$layout  = $request->get_json_params();

		if ( ! is_array( $layout ) ) {
			$raw_body = $request->get_body();
			if ( is_string( $raw_body ) && '' !== $raw_body ) {
				$decoded = json_decode( $raw_body, true );
				if ( is_array( $decoded ) ) {
					$layout = $decoded;
				}
			}
		}

		if ( ! is_array( $layout ) ) {
			return new \WP_Error(
				'low_mm_invalid_body',
				__( 'Request body must be a JSON object representing the full layout.', 'low-mega-menu' ),
				array( 'status' => 400 )
			);
		}

		$validation = LayoutValidator::validate( $layout );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		update_post_meta( $post_id, MegaMenuCPT::LAYOUT_META_KEY, $layout );

		return rest_ensure_response( $layout );
	}

	/**
	 * Permission callback — user must be able to edit the specific mega_menu post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function can_edit_menu( \WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		if ( ! $this->is_mega_menu_post( $post_id ) ) {
			return new \WP_Error(
				'low_mm_invalid_menu',
				__( 'Invalid mega menu ID.', 'low-mega-menu' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to edit this mega menu.', 'low-mega-menu' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Validate that the route id refers to a mega_menu post.
	 *
	 * @param mixed            $value   Route parameter value.
	 * @param \WP_REST_Request $request Request object.
	 * @param string           $param   Parameter name.
	 * @return bool
	 */
	public function validate_menu_id( $value, \WP_REST_Request $request, string $param ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $this->is_mega_menu_post( (int) $value );
	}

	/**
	 * Check whether a post ID is a mega_menu post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_mega_menu_post( int $post_id ): bool {
		$post = get_post( $post_id );

		return $post instanceof \WP_Post && MegaMenuCPT::POST_TYPE === $post->post_type;
	}
}
