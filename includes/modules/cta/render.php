<?php
/**
 * CTA module render template.
 *
 * @package LOW_MM
 * @var string $heading
 * @var string $body
 * @var bool   $plain_text
 * @var string $button_label
 * @var string $button_url
 * @var string $alignment
 * @var string $background_style
 * @var string $button_style
 * @var bool   $has_bg_image
 */

defined( 'ABSPATH' ) || exit;

$cta_classes = array(
	'low-mm-module',
	'low-mm-cta',
	'low-mm-cta--align-' . $alignment,
);
if ( ! empty( $has_bg_image ) ) {
	$cta_classes[] = 'low-mm-has-bg-image';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $cta_classes ) ); ?>"<?php echo $background_style ? ' style="' . esc_attr( $background_style ) . '"' : ''; ?>>
	<?php if ( $heading ) : ?>
		<h3 class="low-mm-cta__heading"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>
	<?php if ( $body ) : ?>
		<div class="low-mm-cta__body">
			<?php
			if ( $plain_text ) {
				echo esc_html( wp_strip_all_tags( $body ) );
			} else {
				echo wp_kses_post( $body );
			}
			?>
		</div>
	<?php endif; ?>
	<?php if ( $button_label && $button_url ) : ?>
		<p class="low-mm-cta__button-wrap">
			<a class="low-mm-cta__button" href="<?php echo esc_url( $button_url ); ?>"<?php echo $button_style ? ' style="' . esc_attr( $button_style ) . '"' : ''; ?>><?php echo esc_html( $button_label ); ?></a>
		</p>
	<?php endif; ?>
</div>
