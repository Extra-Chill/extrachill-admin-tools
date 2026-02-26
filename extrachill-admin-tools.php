<?php
/**
 * Plugin Name: Extra Chill Admin Tools
 * Description: Centralized network admin tools for the Extra Chill platform ecosystem
 * Version: 2.0.5
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

define( 'EXTRACHILL_ADMIN_TOOLS_VERSION', '2.0.5' );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Admin settings (React mount point).
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/admin/admin-settings.php';

// Abilities API integration.
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities.php';
