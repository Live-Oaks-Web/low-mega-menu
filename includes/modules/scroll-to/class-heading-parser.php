<?php
/**
 * Extracts headings from post content for scroll-to targets.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\ScrollTo;

defined( 'ABSPATH' ) || exit;

/**
 * Parses rendered post HTML for h1–h6 headings in document order.
 */
class HeadingParser {

	/**
	 * Build a stable anchor id for a heading on a post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $index   Zero-based heading index within post content.
	 * @return string
	 */
	public static function build_anchor_id( int $post_id, int $index ): string {
		return 'low-mm-heading-' . $post_id . '-' . max( 0, $index );
	}

	/**
	 * Extract headings from a published post's rendered content.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function extract_from_post( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			return array();
		}

		$content = apply_filters( 'the_content', $post->post_content );

		return self::extract_from_html( $content, $post_id );
	}

	/**
	 * Get heading text at a specific index, if it exists.
	 *
	 * @param int $post_id Post ID.
	 * @param int $index   Heading index.
	 * @return string
	 */
	public static function get_heading_text( int $post_id, int $index ): string {
		$headings = self::extract_from_post( $post_id );

		return isset( $headings[ $index ] ) ? (string) $headings[ $index ]['text'] : '';
	}

	/**
	 * Parse heading elements from HTML fragment.
	 *
	 * @param string   $html    Rendered HTML.
	 * @param int|null $post_id Post ID for anchor id generation.
	 * @return array<int, array<string, mixed>>
	 */
	public static function extract_from_html( string $html, ?int $post_id = null ): array {
		$html = trim( $html );

		if ( '' === $html ) {
			return array();
		}

		$previous = libxml_use_internal_errors( true );

		$document = new \DOMDocument();
		$document->loadHTML(
			'<?xml encoding="utf-8" ?><div id="low-mm-heading-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$xpath = new \DOMXPath( $document );
		$nodes = $xpath->query( '//*[@id="low-mm-heading-root"]//h1|//*[@id="low-mm-heading-root"]//h2|//*[@id="low-mm-heading-root"]//h3|//*[@id="low-mm-heading-root"]//h4|//*[@id="low-mm-heading-root"]//h5|//*[@id="low-mm-heading-root"]//h6' );

		if ( false === $nodes ) {
			return array();
		}

		$headings = array();
		$index    = 0;

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof \DOMElement ) {
				continue;
			}

			$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $node->textContent ) ) );

			if ( '' === $text ) {
				continue;
			}

			$entry = array(
				'index'       => $index,
				'tag'         => strtolower( $node->tagName ),
				'text'        => $text,
				'existing_id' => sanitize_html_class( (string) $node->getAttribute( 'id' ) ),
			);

			if ( null !== $post_id && $post_id > 0 ) {
				$entry['anchor_id'] = self::build_anchor_id( $post_id, $index );
			}

			$headings[] = $entry;
			++$index;
		}

		return $headings;
	}
}
