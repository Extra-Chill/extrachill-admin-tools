<?php
/**
 * Plugin Name: Extra Chill Admin Tools
 * Description: Centralized admin tools for the Extra Chill platform ecosystem
 * Version: 1.2.2
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Text Domain: extrachill-admin-tools
 * Network: true
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXTRACHILL_ADMIN_TOOLS_VERSION', '1.2.2' );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'extrachill_admin_tools_activate' );

/**
 * Create or update the 404 log table on activation.
 *
 * Adds missing blog_id column/index for existing installs, or creates the table for new installs.
 *
 * @return void
 */
function extrachill_admin_tools_activate() {
	global $wpdb;

	$table_name = $wpdb->base_prefix . '404_log';

	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	if ( $table_name === $table_exists ) {
		$column_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM %i LIKE %s', $table_name, 'blog_id' ) );

		if ( ! $column_exists ) {
			// Add missing blog_id column and index for network-wide tracking.
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN blog_id INT NOT NULL AFTER id', $table_name ) );
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD INDEX blog_id_idx (blog_id)', $table_name ) );
		}

		return;
	}

	$charset_collate = $wpdb->get_charset_collate();

	// url and referrer use varchar(2000) to support long URLs with query parameters, tracking codes, etc.
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

require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/admin/admin-settings.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/tag-migration.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/404-error-logger.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/team-member-management.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-user-relationships.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-ownership-repair.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-forum-repair.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/qr-code-generator.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/ad-free-license-management.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/taxonomy-sync.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/forum-topic-migration.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-access-requests.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/scaled-image-url-fix.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/block-namespace-migration.php';
