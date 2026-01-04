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
	if ( $hook !== 'extra-chill-multisite_page_extrachill-admin-tools' ) {
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
		array(),
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
	if ( ! current_user_can( 'manage_network_options' ) ) {
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
