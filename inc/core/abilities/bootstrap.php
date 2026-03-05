<?php
/**
 * Ability hooks bootstrap.
 *
 * @package ExtraChillAdminTools
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_abilities_api_categories_init', 'extrachill_admin_tools_register_category' );
add_action( 'wp_abilities_api_init', 'extrachill_admin_tools_register_abilities' );
