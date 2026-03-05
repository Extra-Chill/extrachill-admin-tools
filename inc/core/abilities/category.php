<?php
/**
 * Ability category registration.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register admin tools ability category.
 */
function extrachill_admin_tools_register_category() {
	wp_register_ability_category(
		'extrachill-admin-tools',
		array(
			'label'       => __( 'Admin Tools', 'extrachill-admin-tools' ),
			'description' => __( 'Network administration operations for Extra Chill.', 'extrachill-admin-tools' ),
		)
	);
}
