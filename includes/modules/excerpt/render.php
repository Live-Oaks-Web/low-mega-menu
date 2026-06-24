<?php
/**
 * Excerpt module render template.
 *
 * @package LOW_MM
 * @var \WP_Post $post
 * @var string   $image_html
 * @var string   $excerpt
 * @var bool     $rich
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="low-mm-module low-mm-excerpt">
	<a class="low-mm-excerpt__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<?php if ( $image_html ) : ?>
			<div class="low-mm-excerpt__media"><?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<?php endif; ?>
		<h3 class="low-mm-excerpt__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
	</a>
	<?php if ( $excerpt ) : ?>
		<div class="low-mm-excerpt__body">
			<?php
			if ( $rich ) {
				echo wp_kses_post( $excerpt );
			} else {
				echo esc_html( $excerpt );
			}
			?>
		</div>
	<?php endif; ?>
</article>
