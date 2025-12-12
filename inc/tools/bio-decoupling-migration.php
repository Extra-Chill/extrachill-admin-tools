<?php
/**
 * Bio Decoupling Migration Tool
 *
 * One-time migration tool to decouple artist profile bios from link page bios.
 * Copies the bio from the artist profile to the link page meta (_link_page_bio_text)
 * if the link page bio is currently empty.
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Bio Decoupling Migration tool.
 *
 * @param array  Array of registered admin tools.
 * @return array
 */
function ec_register_bio_decoupling_tool( $tools ) {
	$tools[] = array(
		'id'          => 'bio-decoupling',
		'title'       => 'Bio Decoupling',
		'description' => 'Migrate artist bios to link page meta for decoupling.',
		'callback'    => 'ec_render_bio_decoupling_page',
	);
	return $tools;
}
add_filter( 'extrachill_admin_tools', 'ec_register_bio_decoupling_tool' );

/**
 * Render the Bio Decoupling Migration page.
 */
function ec_render_bio_decoupling_page() {
	$report = array();

	// Handle form submission
	if ( isset( $_POST['ec_run_bio_migration'] ) && check_admin_referer( 'ec_bio_migration_action', 'ec_bio_migration_nonce' ) ) {
		$report = ec_perform_bio_migration();
	}

	// Output Notices
	if ( ! empty( $report ) ) {
		if ( isset( $report['error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $report['error'] ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success">';
			echo '<p><strong>Migration Complete!</strong></p>';
			echo '<ul>';
			echo '<li>Updated: ' . intval( $report['updated'] ) . '</li>';
			echo '<li>Skipped (Already Set): ' . intval( $report['skipped'] ) . '</li>';
			echo '<li>Skipped (No Artist): ' . intval( $report['no_artist'] ) . '</li>';
			echo '</ul>';
			echo '</div>';
		}
	}
	?>
	<div class="wrap">
		<h2>Bio Decoupling Migration</h2>
		<p>This tool performs a one-time migration to decouple Artist Profile bios from Link Page bios.</p>
		<p>It iterates through all <strong>Artist Link Pages</strong> and checks if they have a dedicated bio saved in <code>_link_page_bio_text</code>.</p>
		<ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px;">
			<li>If the link page bio is <strong>empty</strong>, it copies the content from the associated <strong>Artist Profile</strong>.</li>
			<li>If the link page bio is <strong>already set</strong>, it skips it (preserving custom data).</li>
		</ul>
		<p><em>Run this BEFORE deploying the code changes that decouple the data fields.</em></p>

		<form method="post" action="">
			<?php wp_nonce_field( 'ec_bio_migration_action', 'ec_bio_migration_nonce' ); ?>
			<p class="submit">
				<input type="submit" name="ec_run_bio_migration" id="submit" class="button button-primary" value="Run Migration">
			</p>
		</form>
	</div>
	<?php
}

/**
 * Perform the bio migration logic.
 *
 * @return array Report of the migration results.
 */
function ec_perform_bio_migration() {
	// 1. Get all Artist Link Pages
	$link_pages = get_posts( array(
		'post_type'      => 'artist_link_page',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	) );

	$stats = array(
		'updated'   => 0,
		'skipped'   => 0,
		'no_artist' => 0,
	);

	foreach ( $link_pages as $link_page ) {
		$link_page_id = $link_page->ID;

		// Check if bio meta already exists and is not empty
		$existing_bio = get_post_meta( $link_page_id, '_link_page_bio_text', true );

		if ( ! empty( $existing_bio ) ) {
			$stats['skipped']++;
			continue;
		}

		// Get associated artist ID
		// Try meta first, then fallback to filter if available (though filter might rely on this plugin context)
		$artist_id = get_post_meta( $link_page_id, '_associated_artist_profile_id', true );

		if ( ! $artist_id ) {
			// Fallback: Check if the filter is available from the other plugin
			if ( has_filter( 'ec_get_artist_id' ) ) {
				$artist_id = apply_filters( 'ec_get_artist_id', $link_page_id );
			}
		}

		if ( ! $artist_id ) {
			$stats['no_artist']++;
			continue;
		}

		// Get Artist Bio
		$artist_post = get_post( $artist_id );

		if ( $artist_post && ! empty( $artist_post->post_content ) ) {
			// Migrate content to new independent meta field
			update_post_meta( $link_page_id, '_link_page_bio_text', $artist_post->post_content );
			$stats['updated']++;
		} else {
			// Artist has no bio, just ensure meta key exists as empty string
			// This effectively "migrates" the empty state so it doesn't fallback in the future
			update_post_meta( $link_page_id, '_link_page_bio_text', '' );
			$stats['updated']++;
		}
	}

	return $stats;
}
