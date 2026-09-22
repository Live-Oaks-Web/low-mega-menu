<?php
/**
 * CTA module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\Cta;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Call-to-action card module.
 */
class CtaModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'cta';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Call to Action', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'heading'                 => '',
			'body'                    => '',
			'body_plain_text_only'    => false,
			'text_color'              => '',
			'button_label'            => '',
			'button_url'              => '',
			'button_text_color'       => '',
			'button_background_color' => '',
			'background_mode'         => 'color',
			'background_color'        => '',
			'background_image_id'     => 0,
			'padding_top'             => 24,
			'padding_right'           => 24,
			'padding_bottom'          => 24,
			'padding_left'            => 24,
			'alignment'               => 'left',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$alignments = array( 'left', 'center', 'right' );
		if ( ! in_array( (string) ( $settings['alignment'] ?? '' ), $alignments, true ) ) {
			return new \WP_Error( 'low_mm_cta_alignment', __( 'CTA alignment value is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		$modes = array( 'color', 'image' );
		if ( ! in_array( (string) ( $settings['background_mode'] ?? 'color' ), $modes, true ) ) {
			return new \WP_Error( 'low_mm_cta_background_mode', __( 'CTA background mode is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		foreach ( array( 'padding_top', 'padding_right', 'padding_bottom', 'padding_left', 'padding_x', 'padding_y' ) as $pad_key ) {
			if ( ! array_key_exists( $pad_key, $settings ) ) {
				continue;
			}
			if ( ! is_int( $settings[ $pad_key ] ) && ! is_numeric( $settings[ $pad_key ] ) ) {
				return new \WP_Error( 'low_mm_cta_padding', __( 'CTA padding must be a number.', 'low-mega-menu' ), array( 'status' => 400 ) );
			}
			$pad = (int) $settings[ $pad_key ];
			if ( $pad < 0 || $pad > 200 ) {
				return new \WP_Error( 'low_mm_cta_padding_range', __( 'CTA padding must be between 0 and 200 pixels.', 'low-mega-menu' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$container_style = '';
		$has_bg_image    = false;
		if ( 'image' === ( $settings['background_mode'] ?? 'color' ) ) {
			$image_id = (int) ( $settings['background_image_id'] ?? 0 );
			$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
			if ( $image ) {
				$container_style .= 'background-image:url(' . esc_url( $image ) . ');';
				$has_bg_image     = true;
			}
		} else {
			// Empty / legacy default gray → Settings accent via --low-mm-color-accent.
			$color = self::sanitize_color( (string) ( $settings['background_color'] ?? '' ) );
			if ( '' !== $color && '#f5f5f5' !== strtolower( $color ) ) {
				$container_style .= 'background-color:' . $color . ';';
			}
		}

		$text_color = self::sanitize_color( (string) ( $settings['text_color'] ?? '' ) );
		if ( '' !== $text_color ) {
			// CSS var so .low-mm-module__body / title rules (plugin palette) still defer to CTA override.
			$container_style .= 'color:' . $text_color . ';--low-mm-cta-text:' . $text_color . ';';
		}

		$pad = \LOW_MM\Schema\LayoutSchema::resolve_padding_box(
			$settings,
			array(
				'top'    => 24,
				'right'  => 24,
				'bottom' => 24,
				'left'   => 24,
			)
		);
		$container_style .= sprintf(
			'--low-mm-cta-padding-top:%dpx;--low-mm-cta-padding-right:%dpx;--low-mm-cta-padding-bottom:%dpx;--low-mm-cta-padding-left:%dpx;',
			$pad['top'],
			$pad['right'],
			$pad['bottom'],
			$pad['left']
		);

		// CSS variables so theme-vars can apply !important and still honor per-CTA colors
		// when Divi's .et-fixed-header #top-menu a { color:…!important } is active.
		$button_style      = '';
		$button_text_color = self::sanitize_color( (string) ( $settings['button_text_color'] ?? '' ) );
		$button_background = self::sanitize_color( (string) ( $settings['button_background_color'] ?? '' ) );
		if ( '' !== $button_text_color ) {
			$button_style .= '--low-mm-cta-button-text:' . $button_text_color . ';';
		}
		if ( '' !== $button_background ) {
			$button_style .= '--low-mm-cta-button-bg:' . $button_background . ';';
		}

		return self::render_template(
			array(
				'heading'          => (string) ( $settings['heading'] ?? '' ),
				'body'             => (string) ( $settings['body'] ?? '' ),
				'plain_text'       => ! empty( $settings['body_plain_text_only'] ),
				'button_label'     => (string) ( $settings['button_label'] ?? '' ),
				'button_url'       => (string) ( $settings['button_url'] ?? '' ),
				'alignment'        => (string) ( $settings['alignment'] ?? 'left' ),
				'background_style' => $container_style,
				'button_style'     => $button_style,
				'has_bg_image'     => $has_bg_image,
			)
		);
	}

	/**
	 * Validate and normalize a CSS color value (hex 3/6/8 digits).
	 *
	 * @param string $color Raw color value.
	 * @return string Sanitized color, or empty string when invalid.
	 */
	private static function sanitize_color( string $color ): string {
		$color = trim( $color );
		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color ) ) {
			return $color;
		}

		return '';
	}
}

ModuleRegistry::register( CtaModule::type(), CtaModule::class );
