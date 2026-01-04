<?php
/**
 * Admin interface setup for Extra Chill Admin Tools.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'network_admin_menu', 'extrachill_admin_tools_menu' );
add_action( 'admin_enqueue_scripts', 'extrachill_admin_tools_enqueue_assets' );

/**
 * Register Admin Tools menu item.
 *
 * @return void
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
 * Enqueue assets for the Admin Tools page.
 *
 * @param string $hook Current admin page hook.
 *
 * @return void
 */
function extrachill_admin_tools_enqueue_assets( $hook ) {
	if ( 'extra-chill-multisite_page_extrachill-admin-tools' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'extrachill-admin-tools',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/admin-tools.css',
		array(),
		filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/admin-tools.css' )
	);

	wp_enqueue_script(
		'extrachill-admin-tools',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/admin-tabs.js',
		array( 'jquery' ),
		filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/admin-tabs.js' ),
		true
	);

	wp_localize_script(
		'extrachill-admin-tools',
		'ecAdminTools',
		array(
			'restUrl'      => rest_url( 'extrachill/v1/' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'targetBlogId' => isset( $_GET['target_blog_id'] ) ? intval( $_GET['target_blog_id'] ) : get_current_blog_id(),
		)
	);
}

/**
 * Render the Admin Tools page with tabbed tools.
 *
 * @return void
 */
function extrachill_admin_tools_page() {
	if ( ! current_user_can( 'manage_network_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'extrachill-admin-tools' ) );
	}

	$target_blog_id = isset( $_GET['target_blog_id'] ) ? intval( $_GET['target_blog_id'] ) : get_current_blog_id();
	$tools          = apply_filters( 'extrachill_admin_tools', array() );
	$sites          = get_sites( array( 'number' => 100 ) );

	switch_to_blog( $target_blog_id );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Extra Chill Admin Tools', 'extrachill-admin-tools' ) . '</h1>';

	// Site Selector.
	echo '<div class="ec-admin-tools-header" style="margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">';
	echo '<form method="get" action="">';
	echo '<input type="hidden" name="page" value="extrachill-admin-tools">';
	echo '<label for="target_blog_id" style="font-weight: 600; margin-right: 10px;">' . esc_html__( 'Select Site to Act Upon:', 'extrachill-admin-tools' ) . '</label>';
	echo '<select name="target_blog_id" id="target_blog_id" onchange="this.form.submit()">';
	foreach ( $sites as $site ) {
		$selected = ( (int) $site->blog_id === $target_blog_id ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $site->blog_id ) . '" ' . $selected . '>' . esc_html( $site->domain . $site->path ) . ' (ID: ' . $site->blog_id . ')</option>';
	}
	echo '</select>';
	echo ' <span class="description" style="margin-left: 10px;">' . esc_html__( 'Tools below will operate in the context of the selected site.', 'extrachill-admin-tools' ) . '</span>';
	echo '</form>';
	echo '</div>';

	if ( empty( $tools ) ) {
		echo '<p>' . esc_html__( 'No admin tools are currently registered.', 'extrachill-admin-tools' ) . '</p>';
	} else {
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tools as $tool ) {
			if ( isset( $tool['title'], $tool['id'], $tool['callback'] ) && function_exists( $tool['callback'] ) ) {
				$tab_id   = esc_attr( $tool['id'] );
				$tab_name = esc_html( $tool['title'] );
				echo '<a href="#' . $tab_id . '" class="nav-tab" data-tab="' . $tab_id . '">' . $tab_name . '</a>';
			}
		}
		echo '</h2>';

		foreach ( $tools as $tool ) {
			if ( isset( $tool['title'], $tool['id'], $tool['callback'] ) && function_exists( $tool['callback'] ) ) {
				$tab_id = esc_attr( $tool['id'] );
				echo '<div class="tool-tab-content" data-tab="' . $tab_id . '">';
				echo '<div class="ec-tool-card">';
				if ( isset( $tool['description'] ) ) {
					echo '<p class="ec-tool-description">' . esc_html( $tool['description'] ) . '</p>';
				}
				call_user_func( $tool['callback'] );
				echo '</div>';
				echo '</div>';
			}
		}
	}

	echo '</div>';

	restore_current_blog();
}
