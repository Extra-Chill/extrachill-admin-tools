<?php
/**
 * Team member ability handlers.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

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

	return extrachill_admin_tools_ability_rest_result( $response );
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

	return extrachill_admin_tools_ability_rest_result( $response );
}
