<?php
/**
 * Team member ability handlers.
 *
 * Writes the extra_chill_team WP role directly via the primitives
 * exposed by extrachill-users. The role IS the source of truth —
 * there is no auxiliary meta layer, no derivation step, no auto/
 * manual distinction.
 *
 * This file replaces the earlier circular call chain
 * (sync-team-members → extrachill_api_sync_team_members →
 *  wp_get_ability(sync-team-members)→execute → back here)
 * that resolved Extra-Chill/extrachill-admin-tools#8 by construction
 * — both abilities now have real implementations.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sync team role to every site for every current team member.
 *
 * Idempotent. Use after adding a new subsite to the network to ensure
 * existing team members get the role assignment on the new site, or
 * after any manual role state changes that should be propagated.
 *
 * The implementation walks every user who already has the
 * extra_chill_team role anywhere in the network and re-grants the
 * role on every site. Already-assigned sites are no-ops via
 * ec_users_grant_team_role's internal duplicate check.
 *
 * @param array $input Input parameters (unused; ability signature requirement).
 * @return array|WP_Error
 */
// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- ability execute_callback signature requires the $input parameter.
function extrachill_admin_tools_ability_sync_team( $input ) {
	if ( ! function_exists( 'ec_users_grant_team_role' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-users plugin is required.', array( 'status' => 500 ) );
	}

	$team_user_ids = extrachill_admin_tools_get_current_team_user_ids();

	$sites_processed = 0;
	foreach ( $team_user_ids as $user_id ) {
		$added            = ec_users_grant_team_role( $user_id );
		$sites_processed += count( $added );
	}

	return array(
		'total_team_users' => count( $team_user_ids ),
		'sites_processed'  => $sites_processed,
	);
}

/**
 * Grant or revoke the team role for a single user.
 *
 * @param array $input Input with user_id and action.
 *                     - user_id: int
 *                     - action: 'force_add' or 'force_remove'
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_manage_team_member( $input ) {
	if ( ! function_exists( 'ec_users_grant_team_role' ) || ! function_exists( 'ec_users_revoke_team_role' ) ) {
		return new WP_Error( 'dependency_missing', 'extrachill-users plugin is required.', array( 'status' => 500 ) );
	}

	$user_id = isset( $input['user_id'] ) ? (int) $input['user_id'] : 0;
	$action  = isset( $input['action'] ) ? (string) $input['action'] : '';

	if ( $user_id <= 0 ) {
		return new WP_Error( 'invalid_user_id', 'A positive user_id is required.', array( 'status' => 400 ) );
	}

	if ( ! get_user_by( 'id', $user_id ) ) {
		return new WP_Error( 'user_not_found', sprintf( 'User %d not found.', $user_id ), array( 'status' => 404 ) );
	}

	switch ( $action ) {
		case 'force_add':
			$sites = ec_users_grant_team_role( $user_id );
			return array(
				'message'        => sprintf( 'Team role granted on %d site(s).', count( $sites ) ),
				'user_id'        => $user_id,
				'is_team_member' => true,
				'sites_added'    => $sites,
			);

		case 'force_remove':
			$sites = ec_users_revoke_team_role( $user_id );
			return array(
				'message'        => sprintf( 'Team role revoked on %d site(s).', count( $sites ) ),
				'user_id'        => $user_id,
				'is_team_member' => false,
				'sites_removed'  => $sites,
			);

		default:
			return new WP_Error(
				'invalid_action',
				sprintf( 'Unknown action: %s. Expected force_add or force_remove.', $action ),
				array( 'status' => 400 )
			);
	}
}

/**
 * Resolve the set of user IDs currently holding the extra_chill_team
 * role on ANY site in the network.
 *
 * Used by the sync ability to find users whose role assignments may
 * be out of sync (e.g. after a new subsite is added).
 *
 * @return int[]
 */
function extrachill_admin_tools_get_current_team_user_ids() {
	global $wpdb;

	$ids = array();

	if ( function_exists( 'ec_users_get_network_site_ids' ) ) {
		$site_ids = ec_users_get_network_site_ids();
	} else {
		$site_ids = array_map( 'intval', get_sites( array( 'fields' => 'ids' ) ) );
	}

	foreach ( $site_ids as $blog_id ) {
		$blog_id  = (int) $blog_id;
		$caps_key = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';

		$user_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $caps_key sanitized by $wpdb->get_blog_prefix().
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
				$caps_key,
				'%' . $wpdb->esc_like( 'extra_chill_team' ) . '%'
			)
		);

		foreach ( (array) $user_ids as $id ) {
			$ids[ (int) $id ] = true;
		}
	}

	return array_map( 'intval', array_keys( $ids ) );
}
