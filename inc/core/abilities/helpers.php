<?php
/**
 * Shared helpers for abilities.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

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
 * Normalize REST callback responses for ability handlers.
 *
 * @param mixed $response REST callback response.
 * @return array|WP_Error
 */
function extrachill_admin_tools_ability_rest_result( $response ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( $response instanceof WP_REST_Response ) {
		return $response->get_data();
	}

	return $response;
}
