<?php
/**
 * Plugin Name: Extra Chill Admin Tools
 * Description: Centralized admin tools for the Extra Chill platform ecosystem
 * Version: 1.0.0
 * Author: Chris Huber
 * Author URI: https://chubes.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Text Domain: extrachill-admin-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EXTRACHILL_ADMIN_TOOLS_VERSION', '1.0.0');
define('EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'extrachill_admin_tools_activate');

function extrachill_admin_tools_activate() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . '404_log';

    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    if ($table_exists) {
        // Table exists - check if blog_id column exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'blog_id'");

        if (!$column_exists) {
            // Add missing blog_id column and index for network-wide tracking
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN blog_id INT NOT NULL AFTER id");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX blog_id_idx (blog_id)");
        }
    } else {
        // Table doesn't exist - create with full schema
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            blog_id INT NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            url varchar(255) NOT NULL,
            referrer varchar(255) DEFAULT '' NOT NULL,
            user_agent text NOT NULL,
            ip_address varchar(100) NOT NULL,
            PRIMARY KEY (id),
            INDEX blog_id_idx (blog_id),
            INDEX time_idx (time),
            INDEX url_idx (url(50))
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/admin/admin-settings.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/tag-migration.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/404-error-logger.php';

require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/session-token-cleanup.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/image-votes-cleanup.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/team-member-management.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-user-relationships.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/scaled-image-url-fix.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-platform-migration.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/artist-ownership-repair.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/tools/migrate-avatars.php';