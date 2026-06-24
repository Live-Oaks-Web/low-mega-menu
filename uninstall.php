<?php
/**
 * Uninstall LOW Mega Menu.
 *
 * Removes plugin options only. mega_menu posts and their meta are left intact.
 *
 * @package LOW_MM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'low_mm_allow_shortcode_execution' );
