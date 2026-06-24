<?php
/**
 * Code / shortcode module.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Modules\Code;

use LOW_MM\Modules\Module;
use LOW_MM\Modules\ModuleRegistry;

use LOW_MM\Utils\ShortcodeGate;

defined( 'ABSPATH' ) || exit;

/**
 * Raw HTML/shortcode module with execution gating.
 */
class CodeModule extends Module {

	/**
	 * {@inheritDoc}
	 */
	public static function type(): string {
		return 'code';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function label(): string {
		return __( 'Code / Shortcode', 'low-mega-menu' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function default_settings(): array {
		return array(
			'content'             => '',
			'shortcode_execution' => 'inherit',
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function validate_settings( array $settings ) {
		$modes = array( 'inherit', 'on', 'off' );
		if ( ! in_array( (string) ( $settings['shortcode_execution'] ?? 'inherit' ), $modes, true ) ) {
			return new \WP_Error( 'low_mm_code_execution_mode', __( 'Code module shortcode execution mode is invalid.', 'low-mega-menu' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function render( array $settings ): string {
		$content = (string) ( $settings['content'] ?? '' );
		if ( '' === $content ) {
			return '';
		}

		$mode = (string) ( $settings['shortcode_execution'] ?? 'inherit' );

		if ( ShortcodeGate::is_allowed( $mode ) ) {
			return (string) do_shortcode( $content );
		}

		return self::render_template(
			array(
				'content' => esc_html( $content ),
			)
		);
	}
}

ModuleRegistry::register( CodeModule::type(), CodeModule::class );
