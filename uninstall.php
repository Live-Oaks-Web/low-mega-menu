<?php
/**
 * Uninstall LOW Mega Menu.
 *
 * Removes plugin options. mega_menu posts and their meta are left intact.
 *
 * @package LOW_MM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$options = array(
	'low_mm_allow_shortcode_execution',
	'low_mm_use_aria_expanded',
	'low_mm_override_divi_header',
	'low_mm_search_enabled',
	'low_mm_mobile_breakpoint',
	'low_mm_style_colors',
	'low_mm_custom_css',
	'low_mm_panel_max_width',
	'low_mm_button_text_align',
	'low_mm_style_font_sizes',
	'low_mm_pq_gen',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

delete_transient( 'low_mm_github_release' );
