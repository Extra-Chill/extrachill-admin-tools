<?php
/**
 * Plugin Name: Extra Chill Admin Tools
 * Description: Centralized network admin tools for the Extra Chill platform ecosystem
 * Version: 2.0.1
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Text Domain: extrachill-admin-tools
 * Network: true
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXTRACHILL_ADMIN_TOOLS_VERSION', '2.0.1' );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'extrachill_admin_tools_activate' );

/**
 * Create or update the 404 log table on activation.
 */
function extrachill_admin_tools_activate() {
	global $wpdb;

	$table_name = $wpdb->base_prefix . '404_log';

	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	if ( $table_name === $table_exists ) {
		$column_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'blog_id' ) );

		if ( ! $column_exists ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN blog_id INT NOT NULL AFTER id', $table_name ) );
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD INDEX blog_id_idx (blog_id)', $table_name ) );
		}

		return;
	}

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		blog_id INT NOT NULL,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		url varchar(2000) NOT NULL,
		referrer varchar(2000) DEFAULT '' NOT NULL,
		user_agent text NOT NULL,
		ip_address varchar(100) NOT NULL,
		PRIMARY KEY (id),
		INDEX blog_id_idx (blog_id),
		INDEX time_idx (time),
		INDEX url_idx (url(50))
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

// Admin settings (React mount point).
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/admin/admin-settings.php';

// 404 Error Logger backend hooks (logging and cron only, UI is React).
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/404-error-logger.php';
