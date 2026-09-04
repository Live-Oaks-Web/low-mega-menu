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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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
	 * Color picker assets on the settings screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'mega_menu_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			"jQuery(function($){ $('.low-mm-color-field').wpColorPicker(); });"
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

		register_setting(
			self::OPTION_GROUP,
			FrontendSettings::OPTION_SEARCH_ENABLED,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'default'           => true,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			FrontendSettings::OPTION_MOBILE_BREAKPOINT,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( FrontendSettings::class, 'sanitize_mobile_breakpoint' ),
				'default'           => FrontendSettings::DEFAULT_MOBILE_BREAKPOINT,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			FrontendSettings::OPTION_STYLE_COLORS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( FrontendSettings::class, 'sanitize_style_colors' ),
				'default'           => array(),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			FrontendSettings::OPTION_CUSTOM_CSS,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( FrontendSettings::class, 'sanitize_custom_css' ),
				'default'           => '',
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

		$general_sections = array(
			'low_mm_layout' => array(
				'title'  => __( 'Layout', 'low-mega-menu' ),
				'fields' => array(
					FrontendSettings::OPTION_MOBILE_BREAKPOINT => array(
						'label'       => __( 'Mobile breakpoint (px)', 'low-mega-menu' ),
						'description' => sprintf(
							/* translators: 1: default breakpoint, 2: minimum, 3: maximum */
							__( 'Viewport width at which the desktop mega menu starts. Below this width the mobile drawer is used. Default: %1$d. Allowed range: %2$d–%3$d.', 'low-mega-menu' ),
							FrontendSettings::DEFAULT_MOBILE_BREAKPOINT,
							FrontendSettings::MIN_MOBILE_BREAKPOINT,
							FrontendSettings::MAX_MOBILE_BREAKPOINT
						),
						'type'        => 'number',
						'min'         => FrontendSettings::MIN_MOBILE_BREAKPOINT,
						'max'         => FrontendSettings::MAX_MOBILE_BREAKPOINT,
					),
				),
			),
			'low_mm_search' => array(
				'title'  => __( 'Search', 'low-mega-menu' ),
				'fields' => array(
					FrontendSettings::OPTION_SEARCH_ENABLED => array(
						'label'       => __( 'Enable mega menu search', 'low-mega-menu' ),
						'description' => __( 'Adds an AJAX search bar to the navigation. Results (posts and pages) appear in a mega menu panel on desktop and inside the drawer on mobile. On by default.', 'low-mega-menu' ),
						'type'        => 'checkbox',
					),
				),
			),
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
			$general_sections = array_merge(
				array(
					'low_mm_theme_compat' => array(
						'title'  => __( 'Theme compatibility', 'low-mega-menu' ),
						'fields' => $theme_compat_fields,
					),
				),
				$general_sections
			);
		}

		$style_fields = array();
		foreach ( FrontendSettings::style_color_fields() as $key => $field ) {
			$style_fields[ 'color_' . $key ] = array(
				'label'       => $field['label'],
				'description' => $field['description'],
				'type'        => 'color',
				'color_key'   => $key,
				'default'     => $field['default'],
			);
		}

		$style_fields[ FrontendSettings::OPTION_CUSTOM_CSS ] = array(
			'label'       => __( 'Custom CSS', 'low-mega-menu' ),
			'description' => sprintf(
				/* translators: %s: comma-separated CSS class list */
				__( 'Optional CSS appended on the front end. Useful class names: %s', 'low-mega-menu' ),
				FrontendSettings::custom_css_class_help()
			),
			'type'        => 'textarea',
			'rows'        => 12,
		);

		$styling_sections = array(
			'low_mm_styling_palette' => array(
				'title'  => __( 'Color palette', 'low-mega-menu' ),
				'fields' => $style_fields,
			),
		);

		$general_sections = apply_filters( 'low_mm_settings_sections', $general_sections );
		$styling_sections = apply_filters( 'low_mm_styling_settings_sections', $styling_sections );
		$active_tab       = $this->get_active_tab();
		$sections_to_show = ( 'styling' === $active_tab ) ? $styling_sections : $general_sections;
		$page_slug        = $this->settings_page_for_tab( $active_tab );

		foreach ( $sections_to_show as $section_id => $section ) {
			add_settings_section(
				$section_id,
				$section['title'],
				'__return_false',
				$page_slug
			);

			foreach ( $section['fields'] as $field_id => $field ) {
				add_settings_field(
					$field_id,
					$field['label'],
					array( $this, 'render_field' ),
					$page_slug,
					$section_id,
					array(
						'field_id'    => $field_id,
						'type'        => $field['type'] ?? 'text',
						'description' => $field['description'] ?? '',
						'min'         => $field['min'] ?? null,
						'max'         => $field['max'] ?? null,
						'color_key'   => $field['color_key'] ?? '',
						'default'     => $field['default'] ?? '',
						'rows'        => $field['rows'] ?? 8,
					)
				);
			}
		}
	}

	/**
	 * @return string general|styling
	 */
	private function get_active_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $tab, array( 'general', 'styling' ), true ) ? $tab : 'general';
	}

	/**
	 * @param string $tab Tab id.
	 * @return string
	 */
	private function settings_page_for_tab( string $tab ): string {
		return self::PAGE_SLUG . '-' . $tab;
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
			} elseif ( FrontendSettings::OPTION_SEARCH_ENABLED === $field_id ) {
				$value = FrontendSettings::search_enabled();
			} else {
				$value = (bool) get_option( $field_id, false );
			}
			printf(
				'<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
				esc_attr( $field_id ),
				checked( $value, true, false ),
				! empty( $args['description'] ) ? esc_html( (string) $args['description'] ) : ''
			);
			return;
		}

		if ( 'number' === $type ) {
			$value = FrontendSettings::OPTION_MOBILE_BREAKPOINT === $field_id
				? FrontendSettings::mobile_breakpoint()
				: (int) get_option( $field_id, 0 );

			printf(
				'<input type="number" class="small-text" name="%1$s" id="%1$s" value="%2$d" min="%3$d" max="%4$d" step="1" />',
				esc_attr( $field_id ),
				(int) $value,
				isset( $args['min'] ) ? (int) $args['min'] : 0,
				isset( $args['max'] ) ? (int) $args['max'] : 9999
			);

			if ( ! empty( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', esc_html( (string) $args['description'] ) );
			}
			return;
		}

		if ( 'color' === $type ) {
			$color_key = (string) ( $args['color_key'] ?? '' );
			$colors    = FrontendSettings::style_colors();
			$value     = $colors[ $color_key ] ?? '';
			$default   = (string) ( $args['default'] ?? '' );
			$name      = FrontendSettings::OPTION_STYLE_COLORS . '[' . $color_key . ']';

			printf(
				'<input type="text" class="low-mm-color-field" name="%1$s" id="%2$s" value="%3$s" data-default-color="%4$s" />',
				esc_attr( $name ),
				esc_attr( 'low-mm-color-' . $color_key ),
				esc_attr( $value ),
				esc_attr( $default )
			);

			if ( ! empty( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', esc_html( (string) $args['description'] ) );
			}
			return;
		}

		if ( 'textarea' === $type ) {
			$value = FrontendSettings::OPTION_CUSTOM_CSS === $field_id
				? FrontendSettings::custom_css()
				: (string) get_option( $field_id, '' );

			printf(
				'<textarea class="large-text code" name="%1$s" id="%1$s" rows="%2$d">%3$s</textarea>',
				esc_attr( $field_id ),
				isset( $args['rows'] ) ? (int) $args['rows'] : 8,
				esc_textarea( $value )
			);

			if ( ! empty( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', esc_html( (string) $args['description'] ) );
			}
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

		$active_tab = $this->get_active_tab();
		$base_url   = admin_url( 'edit.php?post_type=' . MegaMenuCPT::POST_TYPE . '&page=' . self::PAGE_SLUG );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Mega Menu Settings', 'low-mega-menu' ); ?></h1>
			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Settings tabs', 'low-mega-menu' ); ?>">
				<a class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'general', $base_url ) ); ?>"><?php esc_html_e( 'General', 'low-mega-menu' ); ?></a>
				<a class="nav-tab <?php echo 'styling' === $active_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'styling', $base_url ) ); ?>"><?php esc_html_e( 'Styling', 'low-mega-menu' ); ?></a>
			</nav>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( $this->settings_page_for_tab( $active_tab ) );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
