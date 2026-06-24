<?php
/**
 * Link list module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\LinkList;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Linked title list with optional descriptions.
 */
class LinkListModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'link_list';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Link List', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'description_plain_text_only' => false,
			'rows'                        => array(),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		if ( ! array_key_exists( 'rows', $settings ) || ! is_array( $settings['rows'] ) ) {
			return new \WP_Error( 'low_mm_link_list_rows', __( 'Link list rows must be an array.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		foreach ( $settings['rows'] as $index => $row ) {
			if ( ! is_array( $row ) ) {
				return new \WP_Error(
					'low_mm_link_list_row',
					sprintf(
						/* translators: %d: link list row index */
						__( 'Link list row %d must be an object.', 'low-mega-menu' ),
						$index
					),
					array( 'status' => 400 )
				);
			}

			$label = isset( $row['label'] ) ? trim( (string) $row['label'] ) : '';
			$url   = isset( $row['url'] ) ? trim( (string) $row['url'] ) : '';

			if ( '' === $label && '' === $url ) {
				continue;
			}

			if ( '' === $label || '' === $url ) {
				return new \WP_Error(
					'low_mm_link_list_incomplete',
					sprintf(
						/* translators: %d: link list row index */
						__( 'Link list row %d requires both a label and URL.', 'low-mega-menu' ),
						$index
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$plain_text = ! empty( $settings['description_plain_text_only'] );
		$rows       = is_array( $settings['rows'] ?? null ) ? $settings['rows'] : array();

		return self::render_template(
			array(
				'rows'       => $rows,
				'plain_text' => $plain_text,
			)
		);
	}
}

ModuleRegistry::register( LinkListModule::type(), LinkListModule::class );
