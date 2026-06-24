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
			'heading'              => '',
			'body'                 => '',
			'body_plain_text_only' => false,
			'button_label'         => '',
			'button_url'           => '',
			'background_mode'      => 'color',
			'background_color'     => '#f5f5f5',
			'background_image_id'  => 0,
			'alignment'            => 'left',
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
		$background_style = '';
		if ( 'image' === ( $settings['background_mode'] ?? 'color' ) ) {
			$image_id = (int) ( $settings['background_image_id'] ?? 0 );
			$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
			if ( $image ) {
				$background_style = 'background-image:url(' . esc_url( $image ) . ');';
			}
		} else {
			$color = (string) ( $settings['background_color'] ?? '#f5f5f5' );
			if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
				$background_style = 'background-color:' . $color . ';';
			}
		}

		return self::render_template(
			array(
				'heading'          => (string) ( $settings['heading'] ?? '' ),
				'body'             => (string) ( $settings['body'] ?? '' ),
				'plain_text'       => ! empty( $settings['body_plain_text_only'] ),
				'button_label'     => (string) ( $settings['button_label'] ?? '' ),
				'button_url'       => (string) ( $settings['button_url'] ?? '' ),
				'alignment'        => (string) ( $settings['alignment'] ?? 'left' ),
				'background_style' => $background_style,
			)
		);
	}
}

ModuleRegistry::register( CtaModule::type(), CtaModule::class );
