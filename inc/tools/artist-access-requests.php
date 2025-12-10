<?php
/**
 * Artist Access Requests Admin Tool
 *
 * Displays pending artist platform access requests and allows admin to approve/reject.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'tools_page_extrachill-admin-tools' ) {
		return;
	}

	wp_enqueue_style(
		'ec-artist-access-requests',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/css/artist-access-requests.css',
		array( 'extrachill-admin-tools' ),
		filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/css/artist-access-requests.css' )
	);

	wp_enqueue_script(
		'ec-artist-access-requests',
		EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/artist-access-requests.js',
		array( 'extrachill-admin-tools' ),
		filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/artist-access-requests.js' ),
		true
	);
});

add_filter( 'extrachill_admin_tools', function ( $tools ) {
	$pending_count = ec_get_pending_artist_access_count();
	$title         = 'Artist Access Requests';
	if ( $pending_count > 0 ) {
		$title .= ' (' . $pending_count . ')';
	}

	$tools[] = array(
		'id'          => 'artist-access-requests',
		'title'       => $title,
		'description' => 'Review and approve artist platform access requests from users.',
		'callback'    => 'ec_artist_access_requests_page',
	);
	return $tools;
}, 15 );

/**
 * Get count of pending artist access requests
 *
 * @return int Number of pending requests.
 */
function ec_get_pending_artist_access_count() {
	$users = get_users(
		array(
			'meta_key' => 'artist_access_request',
			'fields'   => 'ID',
		)
	);
	return count( $users );
}

/**
 * Render the Artist Access Requests admin page
 */
function ec_artist_access_requests_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized access' );
	}

	$users = get_users(
		array(
			'meta_key' => 'artist_access_request',
			'orderby'  => 'registered',
			'order'    => 'DESC',
		)
	);

	?>
	<div class="ec-artist-access-wrap">
		<?php if ( empty( $users ) ) : ?>
			<div class="ec-empty-state">
				<p>No pending artist access requests.</p>
			</div>
		<?php else : ?>
			<table class="ec-tool-table">
				<thead>
					<tr>
						<th>Username</th>
						<th>Email</th>
						<th>Request Type</th>
						<th>Requested</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $users as $user ) :
						$request = get_user_meta( $user->ID, 'artist_access_request', true );
						if ( empty( $request ) || ! is_array( $request ) ) {
							continue;
						}

						$type_label = isset( $request['type'] ) && $request['type'] === 'artist'
							? 'Musician'
							: 'Industry Professional';

						$requested_date = isset( $request['requested_at'] )
							? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $request['requested_at'] )
							: 'Unknown';
					?>
					<tr data-user-id="<?php echo esc_attr( $user->ID ); ?>">
						<td><?php echo esc_html( $user->user_login ); ?></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td>
							<span class="ec-badge ec-badge-info">
								<?php echo esc_html( $type_label ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $requested_date ); ?></td>
						<td class="ec-actions">
							<button type="button" class="button button-primary ec-approve-request" data-user-id="<?php echo esc_attr( $user->ID ); ?>" data-type="<?php echo esc_attr( $request['type'] ); ?>">
								Approve
							</button>
							<button type="button" class="button ec-reject-request" data-user-id="<?php echo esc_attr( $user->ID ); ?>">
								Reject
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * AJAX handler for approving artist access request
 */
add_action( 'wp_ajax_ec_approve_artist_access', 'ec_ajax_approve_artist_access' );
function ec_ajax_approve_artist_access() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized access' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'ec_artist_access_requests_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

	if ( ! $user_id || ! in_array( $type, array( 'artist', 'professional' ), true ) ) {
		wp_send_json_error( 'Missing required parameters' );
	}

	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		wp_send_json_error( 'User not found' );
	}

	// Set the appropriate meta
	$meta_key = $type === 'artist' ? 'user_is_artist' : 'user_is_professional';
	update_user_meta( $user_id, $meta_key, '1' );

	// Delete the request
	delete_user_meta( $user_id, 'artist_access_request' );

	// Send approval email
	ec_send_artist_access_approval_email( $user );

	wp_send_json_success( 'User approved successfully' );
}

/**
 * AJAX handler for rejecting artist access request
 */
add_action( 'wp_ajax_ec_reject_artist_access', 'ec_ajax_reject_artist_access' );
function ec_ajax_reject_artist_access() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized access' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'ec_artist_access_requests_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

	if ( ! $user_id ) {
		wp_send_json_error( 'Missing user ID' );
	}

	// Silently delete the request
	delete_user_meta( $user_id, 'artist_access_request' );

	wp_send_json_success( 'Request rejected' );
}

/**
 * Handle email link approval (one-click approve from admin email)
 */
add_action( 'wp_ajax_ec_approve_artist_access_email', 'ec_ajax_approve_artist_access_email' );
function ec_ajax_approve_artist_access_email() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized access' );
	}

	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
	$nonce   = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'ec_approve_artist_access_' . $user_id ) ) {
		wp_die( 'Invalid or expired approval link' );
	}

	if ( ! $user_id || ! in_array( $type, array( 'artist', 'professional' ), true ) ) {
		wp_die( 'Invalid request parameters' );
	}

	$user = get_user_by( 'ID', $user_id );
	if ( ! $user ) {
		wp_die( 'User not found' );
	}

	// Check if already has access
	$has_artist       = get_user_meta( $user_id, 'user_is_artist', true ) === '1';
	$has_professional = get_user_meta( $user_id, 'user_is_professional', true ) === '1';

	if ( $has_artist || $has_professional ) {
		wp_safe_redirect( admin_url( 'tools.php?page=extrachill-admin-tools#artist-access-requests&already_approved=1' ) );
		exit;
	}

	// Set the appropriate meta
	$meta_key = $type === 'artist' ? 'user_is_artist' : 'user_is_professional';
	update_user_meta( $user_id, $meta_key, '1' );

	// Delete the request
	delete_user_meta( $user_id, 'artist_access_request' );

	// Send approval email
	ec_send_artist_access_approval_email( $user );

	// Redirect to admin tools with success message
	wp_safe_redirect( admin_url( 'tools.php?page=extrachill-admin-tools#artist-access-requests&approved=1' ) );
	exit;
}

/**
 * Send approval notification email to user
 *
 * @param WP_User $user The approved user.
 */
function ec_send_artist_access_approval_email( $user ) {
	$login_url = ec_get_site_url( 'artist' ) . '/login/?access_approved=true';

	$subject = __( 'Your Artist Platform Access is Approved!', 'extrachill-admin-tools' );

	$message = sprintf(
		/* translators: 1: display name, 2: login URL */
		__(
			"Hey %1\$s,\n\n" .
			"Great news! Your request to access the Extra Chill Artist Platform has been approved.\n\n" .
			"You can now create artist profiles and link pages on extrachill.link.\n\n" .
			"Get started here:\n%2\$s\n\n" .
			"- Extra Chill",
			'extrachill-admin-tools'
		),
		$user->display_name,
		$login_url
	);

	wp_mail( $user->user_email, $subject, $message );
}
