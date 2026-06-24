<?php
/**
 * Assembles mega menu panel HTML from layout JSON.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Render;

use LOW_MM\Modules\ModuleRegistry;
use LOW_MM\Schema\LayoutSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Renders panel inner markup for the front end.
 */
class PanelRenderer {

	/**
	 * Render panel inner HTML for a mega menu post.
	 *
	 * @param int $mega_menu_post_id Mega menu post ID.
	 * @return string
	 */
	public static function render( int $mega_menu_post_id ): string {
		$layout = LayoutSchema::get_layout_for_post( $mega_menu_post_id );

		if ( null === $layout ) {
			return '';
		}

		$panel_settings = is_array( $layout['panel_settings'] ?? null ) ? $layout['panel_settings'] : array();
		$columns        = is_array( $layout['columns'] ?? null ) ? $layout['columns'] : array();
		$mobile_order   = is_array( $layout['mobile_order'] ?? null ) ? $layout['mobile_order'] : array();
		$layout_preset  = (string) ( $layout['layout_preset'] ?? '2-col' );

		$animation      = in_array( (string) ( $panel_settings['animation'] ?? '' ), LayoutSchema::recognized_animations(), true )
			? (string) $panel_settings['animation']
			: 'fade';
		$speed_ms       = max( 0, (int) ( $panel_settings['animation_speed_ms'] ?? 200 ) );
		$background     = (string) ( $panel_settings['background'] ?? '#ffffff' );
		$max_width      = (string) ( $panel_settings['max_width'] ?? 'default' );

		if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $background ) ) {
			$background = '#ffffff';
		}

		$style_attrs = sprintf(
			'--low-mm-animation-speed:%dms;--low-mm-panel-bg:%s;',
			$speed_ms,
			esc_attr( $background )
		);

		$grid_template = self::build_grid_template( $columns );
		$order_map     = self::build_mobile_order_map( $columns, $mobile_order );

		ob_start();
		?>
		<div
			class="low-mm-panel__inner low-mm-panel__inner--<?php echo esc_attr( $max_width ); ?>"
			data-low-mm-animation="<?php echo esc_attr( $animation ); ?>"
			data-low-mm-layout-preset="<?php echo esc_attr( $layout_preset ); ?>"
			style="<?php echo esc_attr( $style_attrs ); ?>"
		>
			<div class="low-mm-panel__columns" style="<?php echo esc_attr( 'grid-template-columns:' . $grid_template . ';' ); ?>">
				<?php foreach ( $columns as $column ) : ?>
					<?php
					if ( ! is_array( $column ) ) {
						continue;
					}
					$column_id    = (string) ( $column['id'] ?? '' );
					$column_label = (string) ( $column['label'] ?? '' );
					$modules      = is_array( $column['modules'] ?? null ) ? $column['modules'] : array();
					$mobile_index = $order_map[ $column_id ] ?? 0;

					$column_classes = array( 'low-mm-column' );
					if ( ! empty( $column['border_left'] ) ) {
						$column_classes[] = 'low-mm-column--border-left';
					}
					if ( ! empty( $column['border_right'] ) ) {
						$column_classes[] = 'low-mm-column--border-right';
					}
					?>
					<div
						class="<?php echo esc_attr( implode( ' ', $column_classes ) ); ?>"
						data-low-mm-col-id="<?php echo esc_attr( $column_id ); ?>"
						data-low-mm-mobile-order="<?php echo esc_attr( (string) $mobile_index ); ?>"
						style="<?php echo esc_attr( 'order:' . (int) $mobile_index . ';' ); ?>"
					>
						<?php if ( '' !== $column_label ) : ?>
							<div class="low-mm-column__label"><?php echo esc_html( $column_label ); ?></div>
						<?php endif; ?>
						<div class="low-mm-column__modules">
							<?php
							foreach ( $modules as $module ) {
								if ( ! is_array( $module ) ) {
									continue;
								}
								$type     = (string) ( $module['type'] ?? '' );
								$settings = is_array( $module['settings'] ?? null ) ? $module['settings'] : array();
								echo ModuleRegistry::render( $type, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build CSS grid-template-columns from column width fractions.
	 *
	 * @param array<int, array<string, mixed>> $columns Layout columns.
	 * @return string
	 */
	private static function build_grid_template( array $columns ): string {
		$fractions = array();

		foreach ( $columns as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}
			$fraction    = (float) ( $column['width_fraction'] ?? 1 );
			$fractions[] = max( 0.1, $fraction ) . 'fr';
		}

		if ( empty( $fractions ) ) {
			return '1fr';
		}

		return implode( ' ', $fractions );
	}

	/**
	 * Map column IDs to mobile stack order indices.
	 *
	 * @param array<int, array<string, mixed>> $columns      Layout columns.
	 * @param string[]                         $mobile_order Ordered column IDs.
	 * @return array<string, int>
	 */
	private static function build_mobile_order_map( array $columns, array $mobile_order ): array {
		$map = array();
		$pos = 0;

		foreach ( $mobile_order as $column_id ) {
			if ( is_string( $column_id ) && '' !== $column_id ) {
				$map[ $column_id ] = $pos;
				++$pos;
			}
		}

		foreach ( $columns as $column ) {
			if ( ! is_array( $column ) ) {
				continue;
			}
			$column_id = (string) ( $column['id'] ?? '' );
			if ( '' !== $column_id && ! array_key_exists( $column_id, $map ) ) {
				$map[ $column_id ] = $pos;
				++$pos;
			}
		}

		return $map;
	}
}
