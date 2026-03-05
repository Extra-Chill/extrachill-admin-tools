<?php
/**
 * Membership ability handlers.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

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

	return extrachill_admin_tools_ability_rest_result( $response );
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

	return extrachill_admin_tools_ability_rest_result( $response );
}
