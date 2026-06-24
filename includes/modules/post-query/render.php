<?php
/**
 * Post query render template.
 *
 * @package LOW_MM
 * @var array  $items
 * @var string $view_all_label
 * @var string $view_all_url
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="low-mm-module low-mm-post-query">
	<ul class="low-mm-post-query__list">
		<?php foreach ( $items as $item ) : ?>
			<?php $post = $item['post']; ?>
			<li class="low-mm-post-query__item">
				<a class="low-mm-post-query__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
					<?php if ( $item['image_html'] ) : ?>
						<div class="low-mm-post-query__media"><?php echo $item['image_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
					<div class="low-mm-post-query__content">
						<h4 class="low-mm-post-query__title"><?php echo esc_html( get_the_title( $post ) ); ?></h4>
						<?php if ( $item['category_label'] ) : ?>
							<span class="low-mm-post-query__label"><?php echo esc_html( $item['category_label'] ); ?></span>
						<?php endif; ?>
						<?php if ( $item['date'] ) : ?>
							<time class="low-mm-post-query__date" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( $item['date'] ); ?></time>
						<?php endif; ?>
					</div>
				</a>
				<?php if ( $item['excerpt'] ) : ?>
					<p class="low-mm-post-query__excerpt"><?php echo esc_html( $item['excerpt'] ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $view_all_label && $view_all_url ) : ?>
		<p class="low-mm-post-query__view-all">
			<a href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html( $view_all_label ); ?></a>
		</p>
	<?php endif; ?>
</div>
