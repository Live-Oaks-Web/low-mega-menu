<?php
/**
 * Scroll-to heading module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\ScrollTo;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Links to a heading within a page or post.
 */
class ScrollToModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'scroll_to';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Scroll To', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'post_type'      => 'page',
			'source_post_id' => 0,
			'heading_index'  => -1,
			'title'          => '',
			'content'        => '',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$post_id = (int) ( $settings['source_post_id'] ?? 0 );
		$index   = (int) ( $settings['heading_index'] ?? -1 );

		if ( $post_id <= 0 ) {
			return true;
		}

		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			return new \WP_Error( 'low_mm_scroll_to_invalid_post', __( 'Scroll To module source post is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		if ( $index < 0 ) {
			return true;
		}

		$headings = HeadingParser::extract_from_post( $post_id );
		if ( ! isset( $headings[ $index ] ) ) {
			return new \WP_Error( 'low_mm_scroll_to_invalid_heading', __( 'Scroll To module heading selection is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
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

		$post_id = (int) ( $settings['source_post_id'] ?? 0 );
		$index   = (int) ( $settings['heading_index'] ?? -1 );

		if ( $post_id <= 0 || $index < 0 ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}

		$permalink = get_permalink( $post );
		if ( ! $permalink ) {
			return '';
		}

		$anchor_id = HeadingParser::build_anchor_id( $post_id, $index );

		$current_id = is_singular() ? (int) get_queried_object_id() : 0;
		if ( $current_id === $post_id ) {
			$url = '#' . $anchor_id;
		} else {
			$url = $permalink . '#' . $anchor_id;
		}

		$title = trim( (string) ( $settings['title'] ?? '' ) );
		if ( '' === $title ) {
			$title = HeadingParser::get_heading_text( $post_id, $index );
		}

		if ( '' === $title ) {
			return '';
		}

		return self::render_template(
			array(
				'url'     => $url,
				'title'   => $title,
				'content' => (string) ( $settings['content'] ?? '' ),
				'post_id' => $post_id,
				'index'   => $index,
			)
		);
	}
}

ModuleRegistry::register( ScrollToModule::type(), ScrollToModule::class );
