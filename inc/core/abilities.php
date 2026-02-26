<?php
/**
 * Abilities API Integration
 *
 * Registers admin tool operations via the WordPress Abilities API,
 * making them discoverable by Data Machine chat agent and pipelines.
 *
 * @package ExtraChillAdminTools
 * @since 2.1.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_abilities_api_categories_init', 'extrachill_admin_tools_register_category' );
add_action( 'wp_abilities_api_init', 'extrachill_admin_tools_register_abilities' );

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

/**
 * Permission callback for admin tools abilities.
 *
 * @return bool
 */
function extrachill_admin_tools_ability_permission() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	if ( class_exists( 'ActionScheduler' ) && did_action( 'action_scheduler_before_execute' ) ) {
		return true;
	}

	return current_user_can( 'manage_network_options' );
}

/**
 * Register all admin tools abilities.
 */
function extrachill_admin_tools_register_abilities() {

	// --- Artist Access Requests ---

	wp_register_ability(
		'extrachill/list-artist-access-requests',
		array(
			'label'       => __( 'List Artist Access Requests', 'extrachill-admin-tools' ),
			'description' => __( 'Get all pending artist platform access requests.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'output_schema' => array(
				'type'        => 'object',
				'description' => __( 'List of pending requests with user details.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_list_access_requests',
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

	wp_register_ability(
		'extrachill/approve-artist-access',
		array(
			'label'       => __( 'Approve Artist Access', 'extrachill-admin-tools' ),
			'description' => __( 'Approve a pending artist platform access request.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to approve.', 'extrachill-admin-tools' ),
					),
					'type' => array(
						'type'        => 'string',
						'description' => __( 'Access type: artist or professional.', 'extrachill-admin-tools' ),
						'enum'        => array( 'artist', 'professional' ),
					),
				),
				'required' => array( 'user_id', 'type' ),
			),
			'output_schema' => array(
				'type'        => 'object',
				'description' => __( 'Result of the approval operation.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_approve_access',
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
		'extrachill/reject-artist-access',
		array(
			'label'       => __( 'Reject Artist Access', 'extrachill-admin-tools' ),
			'description' => __( 'Reject a pending artist platform access request.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to reject.', 'extrachill-admin-tools' ),
					),
				),
				'required' => array( 'user_id' ),
			),
			'output_schema' => array(
				'type'        => 'object',
				'description' => __( 'Result of the rejection operation.', 'extrachill-admin-tools' ),
			),
			'execute_callback'    => 'extrachill_admin_tools_ability_reject_access',
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

	// --- Lifetime Memberships ---

	wp_register_ability(
		'extrachill/grant-lifetime-membership',
		array(
			'label'       => __( 'Grant Lifetime Membership', 'extrachill-admin-tools' ),
			'description' => __( 'Grant lifetime ad-free membership to a user by username or email.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'user_identifier' => array(
						'type'        => 'string',
						'description' => __( 'Username or email address.', 'extrachill-admin-tools' ),
					),
				),
				'required' => array( 'user_identifier' ),
			),
			'output_schema' => array(
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
			'label'       => __( 'Revoke Lifetime Membership', 'extrachill-admin-tools' ),
			'description' => __( 'Revoke lifetime membership from a user.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to revoke membership from.', 'extrachill-admin-tools' ),
					),
				),
				'required' => array( 'user_id' ),
			),
			'output_schema' => array(
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

	// --- Team Member Management ---

	wp_register_ability(
		'extrachill/sync-team-members',
		array(
			'label'       => __( 'Sync Team Members', 'extrachill-admin-tools' ),
			'description' => __( 'Sync team member status for all network users based on main site accounts.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'output_schema' => array(
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
			'label'       => __( 'Manage Team Member', 'extrachill-admin-tools' ),
			'description' => __( 'Force add, force remove, or reset a user\'s team member status.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'user_id' => array(
						'type'        => 'integer',
						'description' => __( 'User ID to manage.', 'extrachill-admin-tools' ),
					),
					'action' => array(
						'type'        => 'string',
						'description' => __( 'Action to take.', 'extrachill-admin-tools' ),
						'enum'        => array( 'force_add', 'force_remove', 'reset_auto' ),
					),
				),
				'required' => array( 'user_id', 'action' ),
			),
			'output_schema' => array(
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

	// --- Taxonomy Sync ---

	wp_register_ability(
		'extrachill/sync-taxonomies',
		array(
			'label'       => __( 'Sync Taxonomies', 'extrachill-admin-tools' ),
			'description' => __( 'Replicate taxonomy terms from the main site to other network sites.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'taxonomies' => array(
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
				'required' => array( 'taxonomies', 'target_sites' ),
			),
			'output_schema' => array(
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

	// --- QR Code Generator ---

	wp_register_ability(
		'extrachill/generate-qr-code',
		array(
			'label'       => __( 'Generate QR Code', 'extrachill-admin-tools' ),
			'description' => __( 'Generate a print-ready QR code PNG for a URL.', 'extrachill-admin-tools' ),
			'category'    => 'extrachill-admin-tools',
			'input_schema' => array(
				'type'       => 'object',
				'properties' => array(
					'url' => array(
						'type'        => 'string',
						'description' => __( 'URL to encode in the QR code.', 'extrachill-admin-tools' ),
						'format'      => 'uri',
					),
				),
				'required' => array( 'url' ),
			),
			'output_schema' => array(
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

// --- Execute callbacks ---
// These delegate to the existing REST endpoint functions in extrachill-api.
// When those functions aren't available (e.g., API plugin not active),
// the abilities return a WP_Error.

/**
 * List pending artist access requests.
 *
 * @param array $input Input parameters (unused).
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_list_access_requests( $input ) {
	if ( ! function_exists( 'extrachill_api_get_artist_access_requests' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request  = new WP_REST_Request( 'GET', '/extrachill/v1/admin/artist-access' );
	$response = extrachill_api_get_artist_access_requests( $request );

	return rest_is_error( $response ) ? $response : $response->get_data();
}

/**
 * Approve an artist access request.
 *
 * @param array $input Input with user_id and type.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_approve_access( $input ) {
	if ( ! function_exists( 'extrachill_api_artist_access_approve' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'POST', '/extrachill/v1/admin/artist-access/' . $input['user_id'] . '/approve' );
	$request->set_param( 'user_id', $input['user_id'] );
	$request->set_param( 'type', $input['type'] );

	$response = extrachill_api_artist_access_approve( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Reject an artist access request.
 *
 * @param array $input Input with user_id.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_reject_access( $input ) {
	if ( ! function_exists( 'extrachill_api_artist_access_reject' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'POST', '/extrachill/v1/admin/artist-access/' . $input['user_id'] . '/reject' );
	$request->set_param( 'user_id', $input['user_id'] );

	$response = extrachill_api_artist_access_reject( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Grant lifetime membership.
 *
 * @param array $input Input with user_identifier.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_grant_membership( $input ) {
	if ( ! function_exists( 'extrachill_api_grant_lifetime_membership' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'POST', '/extrachill/v1/admin/lifetime-membership/grant' );
	$request->set_param( 'user_identifier', $input['user_identifier'] );

	$response = extrachill_api_grant_lifetime_membership( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Revoke lifetime membership.
 *
 * @param array $input Input with user_id.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_revoke_membership( $input ) {
	if ( ! function_exists( 'extrachill_api_revoke_lifetime_membership' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'DELETE', '/extrachill/v1/admin/lifetime-membership/' . $input['user_id'] );
	$request->set_param( 'user_id', $input['user_id'] );

	$response = extrachill_api_revoke_lifetime_membership( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Sync team members.
 *
 * @param array $input Input parameters (unused).
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_sync_team( $input ) {
	if ( ! function_exists( 'extrachill_api_sync_team_members' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request  = new WP_REST_Request( 'POST', '/extrachill/v1/admin/team-members/sync' );
	$response = extrachill_api_sync_team_members( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Manage a single team member.
 *
 * @param array $input Input with user_id and action.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_manage_team_member( $input ) {
	if ( ! function_exists( 'extrachill_api_manage_team_member' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'PUT', '/extrachill/v1/admin/team-members/' . $input['user_id'] );
	$request->set_param( 'user_id', $input['user_id'] );
	$request->set_param( 'action', $input['action'] );

	$response = extrachill_api_manage_team_member( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Sync taxonomies.
 *
 * @param array $input Input with taxonomies and target_sites.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_sync_taxonomies( $input ) {
	if ( ! function_exists( 'extrachill_api_taxonomy_sync' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-api plugin is required.' );
	}

	$request = new WP_REST_Request( 'POST', '/extrachill/v1/admin/taxonomies/sync' );
	$request->set_param( 'taxonomies', $input['taxonomies'] );
	$request->set_param( 'target_sites', $input['target_sites'] );

	$response = extrachill_api_taxonomy_sync( $request );

	return is_wp_error( $response ) ? $response : $response->get_data();
}

/**
 * Generate QR code.
 *
 * @param array $input Input with url.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_generate_qr( $input ) {
	$autoloader = EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'vendor/autoload.php';
	if ( ! file_exists( $autoloader ) ) {
		return new WP_Error( 'dependency_missing', 'QR code vendor library not installed.' );
	}

	require_once $autoloader;

	if ( ! class_exists( '\Endroid\QrCode\QrCode' ) ) {
		return new WP_Error( 'dependency_missing', 'Endroid QR Code library not available.' );
	}

	$qr_code = \Endroid\QrCode\QrCode::create( $input['url'] )
		->setSize( 1000 )
		->setMargin( 10 );

	$writer = new \Endroid\QrCode\Writer\PngWriter();
	$result = $writer->write( $qr_code );

	return array(
		'image'     => base64_encode( $result->getString() ),
		'mime_type' => $result->getMimeType(),
		'url'       => $input['url'],
	);
}
