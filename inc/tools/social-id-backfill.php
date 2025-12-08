<?php
/**
 * Social ID Backfill (Temporary Migration Tool)
 *
 * One-time admin tool to backfill stable social IDs using the REST API.
 * Uses extrachill-api ID helpers to assign `{link_page_id}-social-{counter}` IDs
 * for artist profiles on artist.extrachill.com (blog ID 4).
 *
 * @package ExtraChillAdminTools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'ec-social-id-backfill',
			EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/social-id-backfill.js',
			array(),
			filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/social-id-backfill.js' ),
			true
		);

		wp_localize_script(
			'ec-social-id-backfill',
			'ecSocialIdBackfill',
			array(
				'restUrl' => rest_url( 'extrachill/v1/admin/social-id-backfill' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'blogId'  => get_current_blog_id(),
			)
		);
	}
);

add_filter(
	'extrachill_admin_tools',
	function ( $tools ) {
		if ( 4 !== (int) get_current_blog_id() ) {
			return $tools;
		}

		$tools[] = array(
			'id'          => 'social-id-backfill',
			'title'       => 'Social ID Backfill',
			'description' => 'One-time migration to assign stable social IDs. Run after a DB backup. Artist site only.',
			'callback'    => 'ec_social_id_backfill_page',
		);

		return $tools;
	},
	12
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'extrachill/v1/admin',
			'/social-id-backfill',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => 'ec_social_id_backfill_handler',
				'args'                => array(
					'dry_run' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}
);

/**
 * Render Social ID Backfill admin tab.
 *
 * @return void
 */
function ec_social_id_backfill_page() {
	if ( 4 !== (int) get_current_blog_id() ) {
		echo '<p>' . esc_html__( 'This tool is only available on artist.extrachill.com (blog ID 4).', 'extrachill-admin-tools' ) . '</p>';
		return;
	}

	echo '<div id="ec-social-id-backfill" class="ec-tool-wrap">';
	echo '<p><strong>' . esc_html__( 'Run after a DB backup.', 'extrachill-admin-tools' ) . '</strong> ' . esc_html__( 'Dry run shows counts only; live run writes IDs.', 'extrachill-admin-tools' ) . '</p>';
	echo '<form id="ec-social-id-backfill-form">';
	echo '<label><input type="checkbox" name="dry_run" value="1" checked> ' . esc_html__( 'Dry run (no writes)', 'extrachill-admin-tools' ) . '</label><br><br>';
	echo '<button type="submit" class="button button-primary">' . esc_html__( 'Scan &amp; Backfill', 'extrachill-admin-tools' ) . '</button> ';
	echo '<span class="spinner" style="float:none"></span>';
	echo '</form>';
	echo '<div id="ec-social-id-backfill-output" style="margin-top:1em;"></div>';
	echo '</div>';
}

/**
 * Handle Social ID Backfill REST request.
 *
 * @param WP_REST_Request $request Request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function ec_social_id_backfill_handler( WP_REST_Request $request ) {
	if ( 4 !== (int) get_current_blog_id() ) {
		return new WP_Error( 'wrong_site', __( 'This migration must run on artist.extrachill.com (blog ID 4).', 'extrachill-admin-tools' ), array( 'status' => 400 ) );
	}

	if ( ! function_exists( 'extrachill_api_get_next_id' ) || ! function_exists( 'extrachill_api_sync_counter_from_id' ) ) {
		return new WP_Error( 'missing_dependency', __( 'extrachill-api ID helpers are required.', 'extrachill-admin-tools' ), array( 'status' => 500 ) );
	}

	$dry_run = (bool) $request->get_param( 'dry_run' );

	$artists = get_posts(
		array(
			'post_type'      => 'artist_profile',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => 'any',
		)
	);

	$summary = array(
		'scanned_artists' => 0,
		'updated_artists' => 0,
		'skipped_empty'   => 0,
		'errors'          => array(),
		'samples'         => array(),
	);

	foreach ( $artists as $artist_id ) {
		++$summary['scanned_artists'];

		$link_page_id = ec_get_link_page_for_artist( $artist_id );
		if ( ! $link_page_id ) {
			++$summary['skipped_empty'];
			continue;
		}

		$socials_raw = get_post_meta( $artist_id, '_artist_profile_social_links', true );
		$socials_raw = maybe_unserialize( $socials_raw );

		if ( ! is_array( $socials_raw ) || empty( $socials_raw ) ) {
			++$summary['skipped_empty'];
			continue;
		}

		$updated_socials = array();
		$modified        = false;

		foreach ( $socials_raw as $social ) {
			if ( ! is_array( $social ) || empty( $social['type'] ) || empty( $social['url'] ) ) {
				continue;
			}

			$social_id = isset( $social['id'] ) ? sanitize_text_field( $social['id'] ) : '';

			if ( extrachill_api_needs_id_assignment( $social_id ) ) {
				$social_id = extrachill_api_get_next_id( $link_page_id, 'social' );
				$modified  = true;
			} else {
				extrachill_api_sync_counter_from_id( $link_page_id, 'social', $social_id );
			}

			$updated_socials[] = array(
				'id'   => $social_id,
				'type' => sanitize_text_field( $social['type'] ),
				'url'  => esc_url_raw( $social['url'] ),
			);
		}

		if ( empty( $updated_socials ) ) {
			++$summary['skipped_empty'];
			continue;
		}

		if ( $modified && ! $dry_run ) {
			update_post_meta( $artist_id, '_artist_profile_social_links', $updated_socials );
		}

		if ( $modified ) {
			++$summary['updated_artists'];
			if ( count( $summary['samples'] ) < 5 ) {
				$summary['samples'][] = array(
					'artist_id'    => $artist_id,
					'link_page_id' => $link_page_id,
					'social_ids'   => wp_list_pluck( $updated_socials, 'id' ),
				);
			}
		}
	}

	return rest_ensure_response(
		array(
			'dry_run' => $dry_run,
			'summary' => $summary,
		)
	);
}
