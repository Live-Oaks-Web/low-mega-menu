<?php
/**
 * Capability helpers — plugin admin is Administrator-only (manage_options).
 *
 * @package LOW_MM
 */

namespace LOW_MM\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Central capability checks for mega menu management.
 */
class Capabilities {

	/**
	 * Capability required to manage mega menus, builder, settings, and attachments.
	 */
	public const MANAGE = 'manage_options';

	/**
	 * Whether the current user may manage LOW Mega Menu (Administrator by default).
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( self::MANAGE );
	}

	/**
	 * CPT capability map — every mega_menu operation requires manage_options.
	 *
	 * @return array<string, string>
	 */
	public static function mega_menu_capabilities(): array {
		return array(
			'edit_post'          => self::MANAGE,
			'read_post'          => self::MANAGE,
			'delete_post'        => self::MANAGE,
			'edit_posts'         => self::MANAGE,
			'edit_others_posts'  => self::MANAGE,
			'delete_posts'       => self::MANAGE,
			'publish_posts'      => self::MANAGE,
			'read_private_posts' => self::MANAGE,
			'create_posts'       => self::MANAGE,
		);
	}
}
