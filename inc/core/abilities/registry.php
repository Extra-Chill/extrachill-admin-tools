<?php
/**
 * Ability registration.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register all admin tools abilities.
 */
function extrachill_admin_tools_register_abilities() {
	// Artist access abilities (list, approve, reject) are registered
	// by extrachill-users — they own the user meta business logic.
	// Admin tools just consumes them via wp_get_ability()->execute().

	wp_register_ability(
		'extrachill/grant-lifetime-membership',
		array(
			'label'               => __( 'Grant Lifetime Membership', 'extrachill-admin-tools' ),
			'description'         => __( 'Grant lifetime ad-free membership to a user by username or email.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'user_identifier' => array(
						'type'        => 'string',
						'description' => __( 'Username or email address.', 'extrachill-admin-tools' ),
					),
				),
				'required'   => array( 'user_identifier' ),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Grant confirmation with user details.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_grant_membership',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		)
	);

	wp_register_ability(
		'extrachill/revoke-lifetime-membership',
		array(
			'label'               => __( 'Revoke Lifetime Membership', 'extrachill-admin-tools' ),
			'description'         => __( 'Revoke lifetime membership from a user.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to revoke membership from.', 'extrachill-admin-tools' ),
					),
				),
				'required'   => array( 'user_id' ),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Revoke confirmation.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_revoke_membership',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		)
	);

	wp_register_ability(
		'extrachill/sync-team-members',
		array(
			'label'               => __( 'Sync Team Members', 'extrachill-admin-tools' ),
			'description'         => __( 'Sync team member status for all network users based on main site accounts.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Sync report with counts of updated, skipped users.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_sync_team',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		)
	);

	wp_register_ability(
		'extrachill/manage-team-member',
		array(
			'label'               => __( 'Manage Team Member', 'extrachill-admin-tools' ),
			'description'         => __( 'Force add, force remove, or reset a user\'s team member status.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to manage.', 'extrachill-admin-tools' ),
					),
					'action'  => array(
						'type'        => 'string',
						'description' => __( 'Action to take.', 'extrachill-admin-tools' ),
						'enum'        => array( 'force_add', 'force_remove', 'reset_auto' ),
					),
				),
				'required'   => array( 'user_id', 'action' ),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Updated team member status.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_manage_team_member',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		)
	);

	wp_register_ability(
		'extrachill/sync-taxonomies',
		array(
			'label'               => __( 'Sync Taxonomies', 'extrachill-admin-tools' ),
			'description'         => __( 'Replicate taxonomy terms from the main site to other network sites.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'taxonomies'   => array(
						'type'        => 'array',
						'description' => __( 'Taxonomies to sync.', 'extrachill-admin-tools' ),
						'items'       => array(
							'type' => 'string',
							'enum' => array( 'location', 'festival', 'artist', 'venue' ),
						),
					),
					'target_sites' => array(
						'type'        => 'array',
						'description' => __( 'Target site slugs to sync to.', 'extrachill-admin-tools' ),
						'items'       => array( 'type' => 'string' ),
					),
				),
				'required'   => array( 'taxonomies', 'target_sites' ),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Sync report with created/skipped/failed counts per site and taxonomy.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_sync_taxonomies',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		)
	);

	wp_register_ability(
		'extrachill/generate-qr-code',
		array(
			'label'               => __( 'Generate QR Code', 'extrachill-admin-tools' ),
			'description'         => __( 'Generate a print-ready QR code PNG for a URL.', 'extrachill-admin-tools' ),
			'category'            => 'extrachill-admin-tools',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'url' => array(
						'type'        => 'string',
						'description' => __( 'URL to encode in the QR code.', 'extrachill-admin-tools' ),
						'format'      => 'uri',
					),
					'size' => array(
						'type'        => 'integer',
						'description' => __( 'QR code size in pixels (default: 1000, max: 2000).', 'extrachill-admin-tools' ),
					),
				),
				'required'   => array( 'url' ),
			),
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'QR code image data (base64 PNG).', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_generate_qr',
			'permission_callback' => 'extrachill_admin_tools_ability_permission',
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => true,
					'idempotent'  => true,
					'destructive' => false,
				),
			),
		)
	);
}
