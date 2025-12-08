/**
 * Admin interface setup for Extra Chill Admin Tools.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'extrachill_admin_tools_menu' );
add_action( 'admin_enqueue_scripts', 'extrachill_admin_tools_enqueue_assets' );

/**
 * Register Admin Tools menu item.
 *
 * @return void
 */
function extrachill_admin_tools_menu() {
	add_management_page(
		'Admin Tools',
		'Admin Tools',
		'manage_options',
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
	if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
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
			'restUrl' => rest_url( 'extrachill/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}

/**
 * Render the Admin Tools page with tabbed tools.
 *
 * @return void
 */
function extrachill_admin_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'extrachill-admin-tools' ) );
	}

	$tools = apply_filters( 'extrachill_admin_tools', array() );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Extra Chill Admin Tools', 'extrachill-admin-tools' ) . '</h1>';

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
				echo '<div class="tool-tab-content" data-tab="' . $tab_id . '" style="display:none; margin-top:20px;">';
				echo '<div style="padding: 1em; background: #fff; border: 1px solid var(--border-color); box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
				if ( isset( $tool['description'] ) ) {
					echo '<p style="margin-top:0;">' . esc_html( $tool['description'] ) . '</p>';
					echo '<hr style="margin: 1em 0;">';
				}
				call_user_func( $tool['callback'] );
				echo '</div>';
				echo '</div>';
			}
		}
	}

	echo '</div>';
}


add_action( 'admin_menu', 'extrachill_admin_tools_menu' );
add_action( 'admin_enqueue_scripts', 'extrachill_admin_tools_enqueue_assets' );

function extrachill_admin_tools_menu() {
	add_management_page(
		'Admin Tools',
		'Admin Tools',
		'manage_options',
		'extrachill-admin-tools',
		'extrachill_admin_tools_page'
	);
}

function extrachill_admin_tools_enqueue_assets( $hook ) {
	if ( $hook !== 'tools_page_extrachill-admin-tools' ) {
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
			'restUrl' => rest_url( 'extrachill/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}

function extrachill_admin_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	$tools = apply_filters( 'extrachill_admin_tools', array() );

	echo '<div class="wrap">';
	echo '<h1>Extra Chill Admin Tools</h1>';

	if ( empty( $tools ) ) {
		echo '<p>No admin tools are currently registered.</p>';
	} else {
		// Tab Navigation
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tools as $tool ) {
			if ( isset( $tool['title'] ) && isset( $tool['id'] ) && isset( $tool['callback'] ) && function_exists( $tool['callback'] ) ) {
				$tab_id = esc_attr( $tool['id'] );
				echo '<a href="#' . $tab_id . '" class="nav-tab" data-tab="' . $tab_id . '">' . esc_html( $tool['title'] ) . '</a>';
			}
		}
		echo '</h2>';

		foreach ( $tools as $tool ) {
			if ( isset( $tool['title'] ) && isset( $tool['id'] ) && isset( $tool['callback'] ) && function_exists( $tool['callback'] ) ) {
				$tab_id = esc_attr( $tool['id'] );
				echo '<div class="tool-tab-content" data-tab="' . $tab_id . '" style="display:none; margin-top:20px;">';
				echo '<div style="padding: 1em; background: #fff; border: 1px solid var(--border-color); box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
				if ( isset( $tool['description'] ) ) {
					echo '<p style="margin-top:0;">' . esc_html( $tool['description'] ) . '</p>';
					echo '<hr style="margin: 1em 0;">';
				}
				call_user_func( $tool['callback'] );
				echo '</div>';
				echo '</div>';
			}
		}
	}

	echo '</div>';
}
