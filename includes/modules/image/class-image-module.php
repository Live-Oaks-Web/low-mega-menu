<?php
/**
 * Image module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\Image;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Single image module.
 */
class ImageModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'image';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Image', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'attachment_id'  => 0,
			'alt_text'       => '',
			'link_url'       => '',
			'open_in_new_tab' => false,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$attachment_id = (int) ( $settings['attachment_id'] ?? 0 );
		if ( $attachment_id <= 0 ) {
			return true;
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return new \WP_Error( 'low_mm_image_attachment', __( 'Image module requires a valid attachment.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$attachment_id = (int) ( $settings['attachment_id'] ?? 0 );
		$src           = wp_get_attachment_image_url( $attachment_id, 'large' );
		if ( ! $src ) {
			return '';
		}

		$alt = (string) ( $settings['alt_text'] ?? '' );
		if ( '' === $alt ) {
			$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		}

		return self::render_template(
			array(
				'src'         => $src,
				'alt'         => $alt,
				'link_url'    => (string) ( $settings['link_url'] ?? '' ),
				'open_in_tab' => ! empty( $settings['open_in_new_tab'] ),
			)
		);
	}
}

ModuleRegistry::register( ImageModule::type(), ImageModule::class );
