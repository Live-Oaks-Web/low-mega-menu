<?php
/**
 * Link list render template.
 *
 * @package LOW_MM
 * @var array  $rows
 * @var bool   $plain_text
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $rows ) ) {
	return;
}
?>
<ul class="low-mm-module low-mm-link-list">
	<?php foreach ( $rows as $row ) : ?>
		<?php
		$label = isset( $row['label'] ) ? (string) $row['label'] : '';
		$url   = isset( $row['url'] ) ? (string) $row['url'] : '';
		if ( '' === $label || '' === $url ) {
			continue;
		}
		$description = isset( $row['description'] ) ? (string) $row['description'] : '';
		$new_tab     = ! empty( $row['open_in_new_tab'] );
		?>
		<li class="low-mm-link-list__item">
			<a class="low-mm-link-list__link" href="<?php echo esc_url( $url ); ?>"<?php echo $new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
				<?php echo esc_html( $label ); ?>
			</a>
			<?php if ( '' !== $description ) : ?>
				<div class="low-mm-link-list__description">
					<?php
					if ( $plain_text ) {
						echo esc_html( wp_strip_all_tags( $description ) );
					} else {
						echo wp_kses_post( $description );
					}
					?>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
