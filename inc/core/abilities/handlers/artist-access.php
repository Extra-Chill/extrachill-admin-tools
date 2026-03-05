<?php
/**
 * Artist access ability handlers.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

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

	return extrachill_admin_tools_ability_rest_result( $response );
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

	return extrachill_admin_tools_ability_rest_result( $response );
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

	return extrachill_admin_tools_ability_rest_result( $response );
}
