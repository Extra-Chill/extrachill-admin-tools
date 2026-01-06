<?php
/**
 * Admin Tools - Network Admin Page
 *
 * Renders mount point for React app and localizes configuration.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'network_admin_menu', 'extrachill_admin_tools_menu' );
add_action( 'admin_enqueue_scripts', 'extrachill_admin_tools_enqueue_assets' );

/**
 * Register Admin Tools menu item under Extra Chill Multisite.
 */
function extrachill_admin_tools_menu() {
	add_submenu_page(
		'extrachill-multisite',
		'Admin Tools',
		'Admin Tools',
		'manage_network_options',
		'extrachill-admin-tools',
		'extrachill_admin_tools_page'
	);
}

/**
 * Enqueue React app assets for the Admin Tools page.
 *
 * @param string $hook Current admin page hook.
 */
function extrachill_admin_tools_enqueue_assets( $hook ) {
	if ( 'extra-chill-multisite_page_extrachill-admin-tools' !== $hook ) {
		return;
	}

	$asset_file = EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'build/admin-tools.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_enqueue_script(
		'extrachill-admin-tools',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'build/admin-tools.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_enqueue_style(
		'extrachill-admin-tools',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'build/admin-tools.css',
		array( 'wp-components' ),
		$asset['version']
	);

	$sites = array_map(
		function ( $site ) {
			return array(
				'id'     => (int) $site->blog_id,
				'domain' => $site->domain,
				'path'   => $site->path,
				'name'   => get_blog_details( $site->blog_id )->blogname,
			);
		},
		get_sites( array( 'number' => 100 ) )
	);

	$tools_config = extrachill_admin_tools_get_tools_config();

	wp_localize_script(
		'extrachill-admin-tools',
		'ecAdminToolsConfig',
		array(
			'restUrl'       => rest_url( 'extrachill/v1/' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'sites'         => $sites,
			'tools'         => $tools_config,
			'defaultSiteId' => get_main_site_id(),
		)
	);
}

/**
 * Render the Admin Tools page with React mount point.
 */
function extrachill_admin_tools_page() {
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'extrachill-admin-tools' ) );
	}

	echo '<div class="wrap">';
	echo '<div id="extrachill-admin-tools-root"></div>';
	echo '</div>';
}

/**
 * Returns tool definitions with site availability.
 *
 * @return array Tool configuration array.
 */
function extrachill_admin_tools_get_tools_config() {
	$artist_blog_id    = function_exists( 'ec_get_blog_id' ) ? ec_get_blog_id( 'artist' ) : null;
	$community_blog_id = function_exists( 'ec_get_blog_id' ) ? ec_get_blog_id( 'community' ) : null;
	$main_blog_id      = get_main_site_id();

	return array(
		array(
			'id'          => 'error-logger',
			'title'       => '404 Error Logger',
			'description' => 'Toggle 404 logging and view today\'s error count.',
			'sites'       => 'all',
		),
		array(
			'id'          => 'artist-access-requests',
			'title'       => 'Artist Access Requests',
			'description' => 'Review and approve artist platform access requests.',
			'sites'       => 'all',
		),
		array(
			'id'          => 'artist-user-relationships',
			'title'       => 'Artist-User Relationships',
			'description' => 'Manage relationships between users and artist profiles.',
			'sites'       => $artist_blog_id ? array( $artist_blog_id ) : array(),
		),
		array(
			'id'          => 'forum-topic-migration',
			'title'       => 'Forum Topic Migration',
			'description' => 'Bulk move topics between forums.',
			'sites'       => $community_blog_id ? array( $community_blog_id ) : array(),
		),
		array(
			'id'          => 'qr-code-generator',
			'title'       => 'QR Code Generator',
			'description' => 'Generate print-ready QR codes for any URL.',
			'sites'       => 'all',
		),
		array(
			'id'          => 'tag-migration',
			'title'       => 'Tag Migration',
			'description' => 'Migrate tags to custom taxonomies.',
			'sites'       => array( $main_blog_id ),
		),
		array(
			'id'          => 'taxonomy-sync',
			'title'       => 'Taxonomy Sync',
			'description' => 'Sync taxonomies from main site to other network sites.',
			'sites'       => array( $main_blog_id ),
		),
		array(
			'id'          => 'lifetime-memberships',
			'title'       => 'Lifetime Memberships',
			'description' => 'Manage lifetime memberships and grant ad-free access.',
			'sites'       => 'all',
		),
		array(
			'id'          => 'team-member-management',
			'title'       => 'Team Member Management',
			'description' => 'Sync team members and manage overrides.',
			'sites'       => 'all',
		),
	);
}
