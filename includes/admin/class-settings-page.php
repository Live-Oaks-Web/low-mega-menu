<?php
/**
 * Plugin settings screen.
 *
 * @package LOW_MM
 */

namespace LOW_MM\Admin;

use LOW_MM\Nav\NavEnvironment;
use LOW_MM\PostTypes\MegaMenuCPT;
use LOW_MM\Utils\FrontendSettings;
use LOW_MM\Utils\ShortcodeGate;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin settings via the Settings API.
 */
class SettingsPage {

	/**
	 * Settings page slug.
	 */
	public const PAGE_SLUG = 'low-mm-settings';

	/**
	 * Option group name.
	 */
	public const OPTION_GROUP = 'low_mm_settings';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . MegaMenuCPT::POST_TYPE,
			__( 'Mega Menu Settings', 'low-mega-menu' ),
			__( 'Settings', 'low-mega-menu' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			ShortcodeGate::OPTION_KEY,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => false,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			FrontendSettings::OPTION_ARIA_EXPANDED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => false,
			)
		);

		$divi_header_description = NavEnvironment::is_divi()
			? __( 'Replace Divi\'s #et-top-navigation with the plugin mega menu. Divi logo and top bar are unchanged. Enabled by default on Divi until you change this setting.', 'low-mega-menu' )
			: __( 'Only applies when the Divi theme is active.', 'low-mega-menu' );

		$theme_compat_fields = array();

		if ( NavEnvironment::is_divi() ) {
			register_setting(
				self::OPTION_GROUP,
				FrontendSettings::OPTION_OVERRIDE_DIVI_HEADER,
				array(
					'type'              => 'boolean',
					'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
					'default'           => false,
				)
			);

			$theme_compat_fields[ FrontendSettings::OPTION_OVERRIDE_DIVI_HEADER ] = array(
				'label'       => __( 'Replace Divi primary navigation', 'low-mega-menu' ),
				'description' => $divi_header_description,
				'type'        => 'checkbox',
			);
		}

		$sections = array(
			'low_mm_accessibility' => array(
				'title'  => __( 'Accessibility', 'low-mega-menu' ),
				'fields' => array(
					FrontendSettings::OPTION_ARIA_EXPANDED => array(
						'label'       => __( 'Use aria-expanded on mega menu links', 'low-mega-menu' ),
						'description' => __( 'When enabled, top-level mega menu links include aria-expanded (and aria-controls) while a panel is open. Off by default to avoid conflicting with theme menu accessibility patterns.', 'low-mega-menu' ),
						'type'        => 'checkbox',
					),
				),
			),
			'low_mm_safety' => array(
				'title'  => __( 'Safety', 'low-mega-menu' ),
				'fields' => array(
					ShortcodeGate::OPTION_KEY => array(
						'label'       => __( 'Allow shortcode execution in Code modules', 'low-mega-menu' ),
						'description' => __( 'When disabled, Code module content is shown as plain text. Per-module overrides still apply.', 'low-mega-menu' ),
						'type'        => 'checkbox',
					),
				),
			),
		);

		if ( ! empty( $theme_compat_fields ) ) {
			$sections = array_merge(
				array(
					'low_mm_theme_compat' => array(
						'title'  => __( 'Theme compatibility', 'low-mega-menu' ),
						'fields' => $theme_compat_fields,
					),
				),
				$sections
			);
		}

		$sections = apply_filters( 'low_mm_settings_sections', $sections );

		foreach ( $sections as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'],
				'__return_false',
				self::PAGE_SLUG
			);

			foreach ( $section['fields'] as $field_id => $field ) {
				add_settings_field(
					$field_id,
					$field['label'],
					array( $this, 'render_field' ),
					self::PAGE_SLUG,
					$section_id,
					array(
						'field_id'    => $field_id,
						'type'        => $field['type'] ?? 'text',
						'description' => $field['description'] ?? '',
					)
				);
			}
		}
	}

	/**
	 * Sanitize checkbox values.
	 *
	 * @param mixed $value Submitted value.
	 * @return bool
	 */
	public function sanitize_checkbox( $value ): bool {
		return ! empty( $value );
	}

	/**
	 * Render a settings field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_field( array $args ): void {
		$field_id = $args['field_id'];
		$type     = $args['type'];

		if ( 'checkbox' === $type ) {
			if ( FrontendSettings::OPTION_OVERRIDE_DIVI_HEADER === $field_id ) {
				$value = FrontendSettings::override_divi_header();
			} else {
				$value = (bool) get_option( $field_id, false );
			}
			printf(
				'<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
				esc_attr( $field_id ),
				checked( $value, true, false ),
				! empty( $args['description'] ) ? esc_html( (string) $args['description'] ) : ''
			);
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mega Menu Settings', 'low-mega-menu' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
