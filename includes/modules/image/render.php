<?php
/**
 * Image module render template.
 *
 * @package LOW_MM
 * @var string $src
 * @var string $alt
 * @var string $link_url
 * @var bool   $open_in_tab
 */

defined( 'ABSPATH' ) || exit;

$image = sprintf(
	'<img class="low-mm-image__img" src="%1$s" alt="%2$s" />',
	esc_url( $src ),
	esc_attr( $alt )
);
?>
<figure class="low-mm-module low-mm-image">
	<?php if ( $link_url ) : ?>
		<a class="low-mm-image__link" href="<?php echo esc_url( $link_url ); ?>"<?php echo $open_in_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php else : ?>
		<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</figure>
