<?php
/**
 * Excerpt module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\Excerpt;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Single post/page teaser module.
 */
class ExcerptModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'excerpt';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Page / Post Excerpt', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'source_post_id'     => 0,
			'show_image'         => true,
			'show_excerpt'       => true,
			'custom_excerpt'     => '',
			'excerpt_length'     => 0,
			'rich_text_override' => false,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$post_id = (int) ( $settings['source_post_id'] ?? 0 );
		if ( $post_id <= 0 ) {
			return true;
		}

		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			return new \WP_Error( 'low_mm_excerpt_invalid_post', __( 'Excerpt module source post is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$post_id = (int) ( $settings['source_post_id'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$excerpt_length = (int) ( $settings['excerpt_length'] ?? 0 );
		$custom_excerpt = trim( (string) ( $settings['custom_excerpt'] ?? '' ) );
		$excerpt        = '';
		$use_rich       = false;

		if ( ! empty( $settings['show_excerpt'] ) ) {
			if ( '' !== $custom_excerpt ) {
				// Author-provided text wins over the post's excerpt/content.
				$excerpt = $custom_excerpt;
			} elseif ( ! empty( $settings['rich_text_override'] ) ) {
				$excerpt  = apply_filters( 'the_content', $post->post_content );
				$use_rich = true;
			} else {
				$excerpt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), $excerpt_length > 0 ? $excerpt_length : 55 );
			}
		}

		$image_html = '';
		if ( ! empty( $settings['show_image'] ) && has_post_thumbnail( $post ) ) {
			$image_html = get_the_post_thumbnail( $post, 'medium', array( 'class' => 'low-mm-excerpt__image' ) );
		}

		return self::render_template(
			array(
				'post'       => $post,
				'image_html' => $image_html,
				'excerpt'    => $excerpt,
				'rich'       => $use_rich,
			)
		);
	}
}

ModuleRegistry::register( ExcerptModule::type(), ExcerptModule::class );
