<?php
/**
 * Scroll-to module render template.
 *
 * @package LOW_MM
 * @var string $url
 * @var string $title
 * @var string $content
 * @var int    $post_id
 * @var int    $index
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="low-mm-module low-mm-scroll-to">
	<a
		class="low-mm-scroll-to__link"
		href="<?php echo esc_url( $url ); ?>"
		data-low-mm-scroll-post="<?php echo esc_attr( (string) $post_id ); ?>"
		data-low-mm-scroll-index="<?php echo esc_attr( (string) $index ); ?>"
	>
		<span class="low-mm-scroll-to__title"><?php echo esc_html( $title ); ?></span>
		<?php if ( '' !== trim( $content ) ) : ?>
			<span class="low-mm-scroll-to__content"><?php echo esc_html( $content ); ?></span>
		<?php endif; ?>
	</a>
</div>
