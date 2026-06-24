<?php
/**
 * Post query module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\PostQuery;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Latest posts query module.
 */
class PostQueryModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'post_query';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Post Query', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'post_type'           => 'post',
			'taxonomy'            => '',
			'term_id'             => 0,
			'sort'                => 'newest',
			'count'               => 5,
			'offset'              => 0,
			'show_image'          => true,
			'show_date'           => true,
			'show_category_label' => true,
			'show_excerpt'        => false,
			'view_all_label'      => '',
			'view_all_url'        => '',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$post_type = sanitize_key( (string) ( $settings['post_type'] ?? '' ) );
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return new \WP_Error( 'low_mm_post_query_type', __( 'Post query module requires a valid post type.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		$sort_options = array( 'newest', 'oldest', 'sticky_first', 'title' );
		if ( ! in_array( (string) ( $settings['sort'] ?? '' ), $sort_options, true ) ) {
			return new \WP_Error( 'low_mm_post_query_sort', __( 'Post query module sort value is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		$taxonomy = sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) );
		$term_id  = (int) ( $settings['term_id'] ?? 0 );
		if ( $taxonomy && $term_id > 0 && ( ! taxonomy_exists( $taxonomy ) || ! term_exists( $term_id, $taxonomy ) ) ) {
			return new \WP_Error( 'low_mm_post_query_term', __( 'Post query taxonomy filter is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Merge saved settings with module defaults.
	 *
	 * @param array<string, mixed> $settings Module settings.
	 * @return array<string, mixed>
	 */
	public static function normalize_settings( array $settings ): array {
		return array_merge( self::default_settings(), $settings );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$settings = self::normalize_settings( $settings );
		$args     = PostQueryBuilder::build_args( $settings );
		$query    = new \WP_Query( $args );

		$items = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$post = get_post();
			if ( ! $post ) {
				continue;
			}

			$category_label = '';
			if ( ! empty( $settings['show_category_label'] ) ) {
				$taxonomy = sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) );
				if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
					$terms = get_the_terms( $post, $taxonomy );
					if ( is_array( $terms ) && ! empty( $terms ) ) {
						$category_label = $terms[0]->name;
					}
				} else {
					$categories = get_the_category( $post->ID );
					if ( ! empty( $categories ) ) {
						$category_label = $categories[0]->name;
					}
				}
			}

			$items[] = array(
				'post'           => $post,
				'image_html'     => ! empty( $settings['show_image'] ) && has_post_thumbnail( $post ) ? get_the_post_thumbnail( $post, 'thumbnail', array( 'class' => 'low-mm-post-query__image' ) ) : '',
				'date'           => ! empty( $settings['show_date'] ) ? get_the_date( '', $post ) : '',
				'category_label' => $category_label,
				'excerpt'        => ! empty( $settings['show_excerpt'] ) ? esc_html( get_the_excerpt( $post ) ) : '',
			);
		}
		wp_reset_postdata();

		if ( empty( $items ) ) {
			if ( current_user_can( 'edit_theme_options' ) ) {
				return '<div class="low-mm-module low-mm-post-query low-mm-post-query--empty">' .
					esc_html__( 'No posts match this query.', 'low-mega-menu' ) . '</div>';
			}
			return '';
		}

		return self::render_template(
			array(
				'items'          => $items,
				'view_all_label' => (string) ( $settings['view_all_label'] ?? '' ),
				'view_all_url'   => (string) ( $settings['view_all_url'] ?? '' ),
			)
		);
	}
}

ModuleRegistry::register( PostQueryModule::type(), PostQueryModule::class );
