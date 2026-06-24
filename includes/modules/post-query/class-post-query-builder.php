<?php
/**
 * Compiles post query module settings into WP_Query args.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\PostQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Builds sanitized query arguments from discrete settings fields.
 */
class PostQueryBuilder {

	/**
	 * Compile settings into WP_Query arguments.
	 *
	 * @param array<string, mixed> $settings Module settings.
	 * @return array<string, mixed>
	 */
	public static function build_args( array $settings ): array {
		$post_type = sanitize_key( (string) ( $settings['post_type'] ?? 'post' ) );
		if ( ! post_type_exists( $post_type ) ) {
			$post_type = 'post';
		}

		$count  = max( 1, min( 20, (int) ( $settings['count'] ?? 5 ) ) );
		$offset = max( 0, (int) ( $settings['offset'] ?? 0 ) );

		$args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $count,
			'offset'                 => $offset,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
		);

		$sort = (string) ( $settings['sort'] ?? 'newest' );
		switch ( $sort ) {
			case 'oldest':
				$args['orderby'] = 'date';
				$args['order']   = 'ASC';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'sticky_first':
				$args['orderby'] = array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				);
				break;
			case 'newest':
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
		}

		$taxonomy = sanitize_key( (string) ( $settings['taxonomy'] ?? '' ) );
		$term_id  = (int) ( $settings['term_id'] ?? 0 );

		if ( $taxonomy && $term_id > 0 && self::tax_filter_applies_to_post_type( $taxonomy, $term_id, $post_type ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Optional term filter; results capped at 20 posts.
			$args['tax_query'] = array(
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'term_id',
					'terms'            => array( $term_id ),
					'include_children' => true,
				),
			);
		}

		return $args;
	}

	/**
	 * Whether a taxonomy term filter should be applied for the given post type.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param int    $term_id   Term ID.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	private static function tax_filter_applies_to_post_type( string $taxonomy, int $term_id, string $post_type ): bool {
		if ( ! taxonomy_exists( $taxonomy ) || $term_id <= 0 || ! term_exists( $term_id, $taxonomy ) ) {
			return false;
		}

		$tax_object = get_taxonomy( $taxonomy );
		if ( ! $tax_object || ! in_array( $post_type, (array) $tax_object->object_type, true ) ) {
			return false;
		}

		return true;
	}
}
