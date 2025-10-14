<?php
/**
 * Avatar Migration Tool
 *
 * Converts site-specific custom_avatar_id meta to network-wide user options.
 * Registered via extrachill_admin_tools filter for tabbed admin interface.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Avatar Migration tool in admin interface
 */
add_filter( 'extrachill_admin_tools', 'extrachill_admin_tools_migrate_avatars', 10 );

function extrachill_admin_tools_migrate_avatars( $tools ) {
	$tools[] = array(
		'id'          => 'migrate-avatars',
		'title'       => 'Migrate Avatars to Network-Wide',
		'description' => 'Migrate user avatars from community.extrachill.com site-specific storage to network-wide user options. Makes avatars visible across all Extra Chill sites.',
		'callback'    => 'extrachill_admin_tools_render_migrate_avatars'
	);
	return $tools;
}

/**
 * Render Avatar Migration tool interface
 */
function extrachill_admin_tools_render_migrate_avatars() {
	?>
	<div class="extrachill-admin-tool">
		<h3>Migrate User Avatars to Network-Wide Storage</h3>

		<div class="tool-description">
			<p>This tool migrates existing site-specific avatar data to network-wide user options.</p>
			<p><strong>What it does:</strong></p>
			<ul>
				<li>Finds all users with custom_avatar_id on community.extrachill.com</li>
				<li>Copies the avatar attachment ID to network-wide user option</li>
				<li>Makes avatars visible across all Extra Chill sites</li>
				<li>Preserves existing site-specific data (no deletion)</li>
				<li>Skips users already migrated to prevent duplicates</li>
			</ul>
		</div>

		<div class="tool-actions">
			<button type="button" id="migrate-avatars-btn" class="button button-primary">
				Migrate Avatars to Network-Wide Storage
			</button>
			<span class="spinner"></span>
		</div>

		<div id="migrate-avatars-results" class="tool-results" style="display:none; margin-top: 20px;">
			<h4>Migration Results</h4>
			<div class="results-content"></div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('#migrate-avatars-btn').on('click', function() {
			if (!confirm('This will migrate all user avatars from community.extrachill.com to network-wide storage. Continue?')) {
				return;
			}

			var $button = $(this);
			var $spinner = $button.next('.spinner');
			var $results = $('#migrate-avatars-results');

			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$results.hide();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'extrachill_migrate_avatars',
					nonce: '<?php echo wp_create_nonce( 'migrate_avatars_nonce' ); ?>'
				},
				success: function(response) {
					if (response.success) {
						$results.find('.results-content').html(
							'<div class="notice notice-success"><p>' + response.data.message + '</p></div>' +
							'<p><strong>Migrated:</strong> ' + response.data.migrated + ' avatars</p>' +
							'<p><strong>Skipped:</strong> ' + response.data.skipped + ' (already migrated or invalid)</p>'
						);
					} else {
						$results.find('.results-content').html(
							'<div class="notice notice-error"><p>' + (response.data.message || 'Migration failed') + '</p></div>'
						);
					}
					$results.show();
				},
				error: function() {
					$results.find('.results-content').html(
						'<div class="notice notice-error"><p>AJAX request failed</p></div>'
					);
					$results.show();
				},
				complete: function() {
					$button.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		});
	});
	</script>
	<?php
}

/**
 * AJAX handler for avatar migration
 */
add_action( 'wp_ajax_extrachill_migrate_avatars', 'extrachill_admin_tools_ajax_migrate_avatars' );

function extrachill_admin_tools_ajax_migrate_avatars() {
	// Security checks
	check_ajax_referer( 'migrate_avatars_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$community_blog_id = get_blog_id_from_url( 'community.extrachill.com', '/' );

	if ( ! $community_blog_id ) {
		wp_send_json_error( array( 'message' => 'Error: Could not find community.extrachill.com blog ID' ) );
	}

	// Switch to community site
	switch_to_blog( $community_blog_id );

	try {
		// Get all users with custom_avatar_id on community site
		$users_with_avatars = get_users(
			array(
				'meta_key' => 'custom_avatar_id',
				'fields'   => array( 'ID' ),
			)
		);

		$migrated_count = 0;
		$skipped_count  = 0;

		foreach ( $users_with_avatars as $user ) {
			$avatar_id = get_user_meta( $user->ID, 'custom_avatar_id', true );

			if ( ! $avatar_id || ! wp_attachment_is_image( $avatar_id ) ) {
				$skipped_count++;
				continue;
			}

			// Check if already migrated (network-wide option exists)
			$existing_network_avatar = get_user_option( 'custom_avatar_id', $user->ID );
			if ( $existing_network_avatar ) {
				$skipped_count++;
				continue;
			}

			// Migrate to network-wide storage
			update_user_option( $user->ID, 'custom_avatar_id', $avatar_id, true );
			$migrated_count++;
		}

		$message = sprintf(
			'Migration complete: %d avatars migrated, %d skipped',
			$migrated_count,
			$skipped_count
		);

		wp_send_json_success(
			array(
				'message'  => $message,
				'migrated' => $migrated_count,
				'skipped'  => $skipped_count,
			)
		);
	} finally {
		restore_current_blog();
	}
}
