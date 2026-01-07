<?php
/**
 * One-time migration: Copy data from wp_ec_events to wp_extrachill_analytics_events
 *
 * This file can be removed after the migration has run in production.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'extrachill_migrate_analytics_events_table' );

/**
 * Migrate analytics events from old table to new table.
 *
 * Copies all data from wp_ec_events to wp_extrachill_analytics_events,
 * then drops the old table. Only runs once per network.
 */
function extrachill_migrate_analytics_events_table() {
	if ( ! is_super_admin() ) {
		return;
	}

	if ( get_site_option( 'ec_analytics_events_migrated' ) ) {
		return;
	}

	global $wpdb;

	$old_table = $wpdb->base_prefix . 'ec_events';
	$new_table = $wpdb->base_prefix . 'extrachill_analytics_events';

	$old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) );

	if ( $old_table !== $old_exists ) {
		update_site_option( 'ec_analytics_events_migrated', true );
		return;
	}

	$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );

	if ( $new_table !== $new_exists ) {
		return;
	}

	$old_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$old_table}" );

	if ( $old_count > 0 ) {
		$wpdb->query( "INSERT INTO {$new_table} SELECT * FROM {$old_table}" );
	}

	$wpdb->query( "DROP TABLE IF EXISTS {$old_table}" );

	update_site_option( 'ec_analytics_events_migrated', true );

	error_log( "Analytics events table migration complete. Copied {$old_count} rows from {$old_table} to {$new_table}." );
}
