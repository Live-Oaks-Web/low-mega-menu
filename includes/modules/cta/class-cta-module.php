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
			'background_color'        => '#f5f5f5',
			'background_image_id'     => 0,
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

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$container_style = '';
		if ( 'image' === ( $settings['background_mode'] ?? 'color' ) ) {
			$image_id = (int) ( $settings['background_image_id'] ?? 0 );
			$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
			if ( $image ) {
				$container_style .= 'background-image:url(' . esc_url( $image ) . ');';
			}
		} else {
			$color = self::sanitize_color( (string) ( $settings['background_color'] ?? '#f5f5f5' ) );
			if ( '' !== $color ) {
				$container_style .= 'background-color:' . $color . ';';
			}
		}

		$text_color = self::sanitize_color( (string) ( $settings['text_color'] ?? '' ) );
		if ( '' !== $text_color ) {
			$container_style .= 'color:' . $text_color . ';';
		}

		$button_style       = '';
		$button_text_color  = self::sanitize_color( (string) ( $settings['button_text_color'] ?? '' ) );
		$button_background   = self::sanitize_color( (string) ( $settings['button_background_color'] ?? '' ) );
		if ( '' !== $button_text_color ) {
			$button_style .= 'color:' . $button_text_color . ';';
		}
		if ( '' !== $button_background ) {
			$button_style .= 'background-color:' . $button_background . ';';
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
