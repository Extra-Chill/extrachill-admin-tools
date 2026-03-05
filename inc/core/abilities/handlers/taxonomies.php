<?php
/**
 * Taxonomy ability handlers.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

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

	return extrachill_admin_tools_ability_rest_result( $response );
}
