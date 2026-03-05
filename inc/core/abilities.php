<?php
/**
 * Abilities API bootstrap.
 *
 * @package ExtraChillAdminTools
 * @since 2.1.0
 */

defined( 'ABSPATH' ) || exit;

require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/bootstrap.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/helpers.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/category.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/registry.php';

require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/handlers/artist-access.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/handlers/memberships.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/handlers/team-members.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/handlers/taxonomies.php';
require_once EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'inc/core/abilities/handlers/qr-code.php';
