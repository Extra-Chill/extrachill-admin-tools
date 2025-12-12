<?php
/**
 * Fixes broken image URLs in multisite by adding /sites/{blog_id}/ path and -1 suffix for -scaled images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'extrachill_admin_tools',
	function ( $tools ) {
		$tools[] = array(
			'id'          => 'scaled-image-url-fix',
			'title'       => 'Fix Scaled Image URLs',
			'description' => 'Fix broken -scaled image URLs in multisite by adding /sites/{blog_id}/ path and -1 suffix when available.',
			'callback'    => 'ec_scaled_image_url_fix_page',
		);
		return $tools;
	},
	10
);

function ec_scaled_image_url_fix_page() {
	$results = array();
	$mode    = '';

	if ( isset( $_POST['scan_broken_images'] ) && check_admin_referer( 'scaled_image_url_fix' ) ) {
		$results = ec_scan_broken_image_urls();
		$mode    = 'scan';
	} elseif ( isset( $_POST['fix_broken_images'] ) && check_admin_referer( 'scaled_image_url_fix' ) ) {
		$results = ec_fix_broken_image_urls();
		$mode    = 'fix';
	}

	echo '<form method="post">';
	wp_nonce_field( 'scaled_image_url_fix' );
	echo '<p>';
	echo '<input type="submit" name="scan_broken_images" class="button" value="Scan for Broken Images" style="margin-right:1em;"> ';
	echo '<input type="submit" name="fix_broken_images" class="button" value="Fix Broken Images" onclick="return confirm(\'This will update post content. Continue?\');">';
	echo '</p>';
	echo '</form>';

	if ( ! empty( $results ) ) {
		if ( $mode === 'fix' ) {
			echo '<div class="notice notice-success"><p>';
			echo '<strong>Fixed ' . intval( $results['posts_updated'] ) . ' posts with ' . intval( $results['images_fixed'] ) . ' broken images.</strong>';
			echo '</p></div>';
		}

		if ( ! empty( $results['items'] ) ) {
			echo '<table class="widefat fixed striped" style="margin-top:1em;">';
			echo '<thead><tr><th>Post Title</th><th>Broken URL</th><th>' . ( $mode === 'scan' ? 'Will Replace With' : 'Replaced With' ) . '</th></tr></thead>';
			echo '<tbody>';
			foreach ( $results['items'] as $item ) {
				echo '<tr>';
				echo '<td><a href="' . esc_url( get_edit_post_link( $item['post_id'] ) ) . '">' . esc_html( $item['post_title'] ) . '</a></td>';
				echo '<td style="font-size:11px;word-break:break-all;">' . esc_html( $item['broken_url'] ) . '</td>';
				echo '<td style="font-size:11px;word-break:break-all;">' . esc_html( $item['scaled_url'] ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		} elseif ( $mode === 'scan' ) {
			echo '<div class="notice notice-info"><p><strong>No broken images found!</strong> All image URLs in post content are working.</p></div>';
		}
	}
}

function ec_scan_broken_image_urls() {
	$blog_id = get_current_blog_id();
	$items   = array();

	// Scan regular posts, and forum topics/replies if bbPress is active
	$post_types = array( 'post' );
	if ( function_exists( 'bbp_get_topic_post_type' ) && function_exists( 'bbp_get_reply_post_type' ) ) {
		$post_types[] = bbp_get_topic_post_type();
		$post_types[] = bbp_get_reply_post_type();
	}

	$posts = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		$post    = get_post( $post_id );
		$content = $post->post_content;

		// Find all image URLs containing -scaled (but not already -scaled-1, -scaled-2, etc.)
		preg_match_all( '/(https?:\/\/[^\s"\']+\/wp-content\/uploads\/[^\s"\']+-scaled\.(jpg|jpeg|png|gif|webp))/i', $content, $matches );

		if ( empty( $matches[1] ) ) {
			continue;
		}

		foreach ( $matches[1] as $url ) {
			// Skip if already has -scaled-1, -scaled-2, etc.
			if ( preg_match( '/-scaled-\d+\./i', $url ) ) {
				continue;
			}

			// Skip if already has /sites/{blog_id}/
			if ( strpos( $url, '/sites/' . $blog_id . '/' ) !== false ) {
				continue;
			}

			// Create corrected URL
			$fixed_url = ec_convert_to_scaled_dash_one_url( $url );
			if ( $fixed_url === $url ) {
				continue;
			}

			// Verify corrected URL exists via HTTP HEAD
			if ( ! ec_url_exists( $fixed_url ) ) {
				continue;
			}

			// Found a fixable broken image
			$items[] = array(
				'post_id'    => $post_id,
				'post_title' => $post->post_title,
				'broken_url' => $url,
				'scaled_url' => $fixed_url,
			);
		}
	}

	return array( 'items' => $items );
}

function ec_fix_broken_image_urls() {
	$scan_results  = ec_scan_broken_image_urls();
	$items         = $scan_results['items'];
	$posts_updated = 0;
	$images_fixed  = 0;

	if ( empty( $items ) ) {
		return array(
			'posts_updated' => 0,
			'images_fixed'  => 0,
			'items'         => array(),
		);
	}

	// Group by post_id
	$posts_to_fix = array();
	foreach ( $items as $item ) {
		if ( ! isset( $posts_to_fix[ $item['post_id'] ] ) ) {
			$posts_to_fix[ $item['post_id'] ] = array();
		}
		$posts_to_fix[ $item['post_id'] ][] = $item;
	}

	foreach ( $posts_to_fix as $post_id => $post_items ) {
		$post             = get_post( $post_id );
		$content          = $post->post_content;
		$original_content = $content;

		foreach ( $post_items as $item ) {
			$content = str_replace( $item['broken_url'], $item['scaled_url'], $content );
			++$images_fixed;
		}

		if ( $content !== $original_content ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			);
			++$posts_updated;
		}
	}

	return array(
		'posts_updated' => $posts_updated,
		'images_fixed'  => $images_fixed,
		'items'         => $items,
	);
}

/**
 * Checks if a URL exists by making an HTTP HEAD request
 */
function ec_url_exists( $url ) {
	$response = wp_remote_head( $url, array( 'timeout' => 5 ) );
	return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
}

/**
 * Converts -scaled image URL to multisite path + -scaled-1 variant
 * (e.g., image-scaled.jpeg -> /sites/{blog_id}/image-scaled-1.jpeg)
 */
function ec_convert_to_scaled_dash_one_url( $url ) {
	$blog_id = get_current_blog_id();

	// Add /sites/{blog_id}/ if missing
	if ( strpos( $url, '/sites/' . $blog_id . '/' ) === false ) {
		$url = preg_replace( '/(\/wp-content\/uploads\/)/', '$1sites/' . $blog_id . '/', $url );
	}

	// Convert -scaled to -scaled-1
	if ( preg_match( '/^(.+-scaled)(\.[a-z]{3,4})$/i', $url, $matches ) ) {
		return $matches[1] . '-1' . $matches[2];
	}
	return $url;
}
