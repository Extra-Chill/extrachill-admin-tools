<?php
/**
 * Festival Wire Migration Tool
 *
 * Migrates festival_wire posts from Blog 1 (extrachill.com) to Blog 11 (wire.extrachill.com).
 * Self-contained AJAX handlers - no external API dependencies.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'EC_FWM_SOURCE_BLOG', 1 );
define( 'EC_FWM_TARGET_BLOG', 11 );
define( 'EC_FWM_BATCH_SIZE', 50 );

add_filter( 'extrachill_admin_tools', function ( $tools ) {
    $tools[] = array(
        'id'          => 'festival-wire-migration',
        'title'       => 'Festival Wire Migration',
        'description' => 'Migrate festival_wire posts from extrachill.com to wire.extrachill.com.',
        'callback'    => 'ec_fwm_admin_page',
    );
    return $tools;
}, 40 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'tools_page_extrachill-admin-tools' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'ec-festival-wire-migration',
        EXTRACHILL_ADMIN_TOOLS_PLUGIN_URL . 'assets/js/festival-wire-migration.js',
        array(),
        filemtime( EXTRACHILL_ADMIN_TOOLS_PLUGIN_DIR . 'assets/js/festival-wire-migration.js' ),
        true
    );
    wp_localize_script( 'ec-festival-wire-migration', 'ecFwm', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'ec_fwm_nonce' ),
    ) );
} );

add_action( 'wp_ajax_ec_fwm_preflight', 'ec_fwm_ajax_preflight' );
add_action( 'wp_ajax_ec_fwm_migrate_batch', 'ec_fwm_ajax_migrate_batch' );
add_action( 'wp_ajax_ec_fwm_reset', 'ec_fwm_ajax_reset' );

function ec_fwm_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    ?>
    <div id="ec-fwm" style="max-width:900px;">
        <p><strong>Source:</strong> extrachill.com (Blog 1) → <strong>Target:</strong> wire.extrachill.com (Blog 11)</p>

        <div style="margin:1rem 0;">
            <button type="button" class="button" id="ec-fwm-preflight">Check Status</button>
            <button type="button" class="button button-primary" id="ec-fwm-migrate">Run Migration</button>
            <button type="button" class="button" id="ec-fwm-reset" style="margin-left:1rem;border-color:#dc3232;color:#dc3232;">Reset (Delete All Target Posts)</button>
        </div>

        <div id="ec-fwm-progress" style="display:none;margin:1rem 0;padding:1rem;background:#f0f0f1;border-left:4px solid #2271b1;">
            <div id="ec-fwm-progress-text">Starting migration...</div>
            <div style="margin-top:0.5rem;background:#fff;border:1px solid #ccc;height:20px;">
                <div id="ec-fwm-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;"></div>
            </div>
        </div>

        <div id="ec-fwm-output" style="display:none;"></div>
    </div>
    <?php
}

function ec_fwm_verify_request() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
    if ( ! check_ajax_referer( 'ec_fwm_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }
}

function ec_fwm_ajax_preflight() {
    ec_fwm_verify_request();

    $source_count = 0;
    $target_count = 0;

    try {
        switch_to_blog( EC_FWM_SOURCE_BLOG );
        $source_count = (int) wp_count_posts( 'festival_wire' )->publish;
    } finally {
        restore_current_blog();
    }

    try {
        switch_to_blog( EC_FWM_TARGET_BLOG );
        $target_count = (int) wp_count_posts( 'festival_wire' )->publish;
    } finally {
        restore_current_blog();
    }

    wp_send_json_success( array(
        'source_count' => $source_count,
        'target_count' => $target_count,
        'remaining'    => max( 0, $source_count - $target_count ),
    ) );
}

function ec_fwm_ajax_migrate_batch() {
    ec_fwm_verify_request();

    $last_id = isset( $_POST['last_id'] ) ? absint( $_POST['last_id'] ) : 0;

    $source_posts = array();
    try {
        switch_to_blog( EC_FWM_SOURCE_BLOG );
        global $wpdb;
        $source_posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND ID > %d ORDER BY ID ASC LIMIT %d",
            'festival_wire',
            'publish',
            $last_id,
            EC_FWM_BATCH_SIZE
        ) );
    } finally {
        restore_current_blog();
    }

    if ( empty( $source_posts ) ) {
        wp_send_json_success( array(
            'done'     => true,
            'migrated' => 0,
            'skipped'  => 0,
            'last_id'  => $last_id,
        ) );
    }

    $migrated = 0;
    $skipped  = 0;
    $new_last_id = $last_id;

    foreach ( $source_posts as $source_post ) {
        $new_last_id = (int) $source_post->ID;
        $result = ec_fwm_migrate_single_post( $source_post );
        if ( $result === 'migrated' ) {
            $migrated++;
        } else {
            $skipped++;
        }
    }

    wp_send_json_success( array(
        'done'     => false,
        'migrated' => $migrated,
        'skipped'  => $skipped,
        'last_id'  => $new_last_id,
    ) );
}

function ec_fwm_migrate_single_post( $source_post ) {
    $source_id = (int) $source_post->ID;

    // Get source data while in source blog context
    $source_thumbnail_id = 0;
    $source_meta = array();
    $source_terms = array();

    try {
        switch_to_blog( EC_FWM_SOURCE_BLOG );
        $source_thumbnail_id = (int) get_post_thumbnail_id( $source_id );
        $source_meta = get_post_meta( $source_id );
        $source_terms = array(
            'festival' => wp_get_object_terms( $source_id, 'festival', array( 'fields' => 'slugs' ) ),
            'location' => wp_get_object_terms( $source_id, 'location', array( 'fields' => 'slugs' ) ),
        );
    } finally {
        restore_current_blog();
    }

    // Check if post with same title already exists on target
    $exists = false;
    try {
        switch_to_blog( EC_FWM_TARGET_BLOG );
        global $wpdb;
        $exists = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_title = %s LIMIT 1",
            'festival_wire',
            'publish',
            $source_post->post_title
        ) );
    } finally {
        restore_current_blog();
    }

    if ( $exists ) {
        return 'skipped';
    }

    // Create post on target blog
    try {
        switch_to_blog( EC_FWM_TARGET_BLOG );

        $new_post_id = wp_insert_post( array(
            'post_type'        => 'festival_wire',
            'post_status'      => $source_post->post_status,
            'post_title'       => $source_post->post_title,
            'post_name'        => $source_post->post_name,
            'post_content'     => $source_post->post_content,
            'post_excerpt'     => $source_post->post_excerpt,
            'post_author'      => $source_post->post_author,
            'post_date'        => $source_post->post_date,
            'post_date_gmt'    => $source_post->post_date_gmt,
            'post_modified'    => $source_post->post_modified,
            'post_modified_gmt'=> $source_post->post_modified_gmt,
        ), true );

        if ( is_wp_error( $new_post_id ) ) {
            return 'skipped';
        }

        // Copy meta (skip internal WP meta)
        $skip_meta = array( '_edit_lock', '_edit_last', '_thumbnail_id' );
        foreach ( $source_meta as $key => $values ) {
            if ( in_array( $key, $skip_meta, true ) ) {
                continue;
            }
            foreach ( $values as $value ) {
                update_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }

        // Copy taxonomies
        foreach ( $source_terms as $taxonomy => $slugs ) {
            if ( empty( $slugs ) || is_wp_error( $slugs ) ) {
                continue;
            }
            // Ensure terms exist on target
            foreach ( $slugs as $slug ) {
                if ( ! term_exists( $slug, $taxonomy ) ) {
                    wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
                }
            }
            wp_set_object_terms( $new_post_id, $slugs, $taxonomy, false );
        }

        // Copy featured image
        if ( $source_thumbnail_id ) {
            $new_thumb_id = ec_fwm_copy_attachment( $source_thumbnail_id, $new_post_id );
            if ( $new_thumb_id ) {
                set_post_thumbnail( $new_post_id, $new_thumb_id );
            }
        }
    } finally {
        restore_current_blog();
    }

    return 'migrated';
}

function ec_fwm_copy_attachment( $source_attachment_id, $target_post_id ) {
    $attachment_data = null;
    $file_path = '';

    try {
        switch_to_blog( EC_FWM_SOURCE_BLOG );
        $attachment_data = get_post( $source_attachment_id );
        if ( ! $attachment_data || 'attachment' !== $attachment_data->post_type ) {
            return 0;
        }
        $file_path = get_attached_file( $source_attachment_id );
    } finally {
        restore_current_blog();
    }

    if ( ! $file_path || ! file_exists( $file_path ) ) {
        return 0;
    }

    $new_attachment_id = 0;

    try {
        switch_to_blog( EC_FWM_TARGET_BLOG );

        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return 0;
        }

        wp_mkdir_p( $uploads['path'] );
        $filename = wp_basename( $file_path );
        $new_filename = wp_unique_filename( $uploads['path'], $filename );
        $new_path = trailingslashit( $uploads['path'] ) . $new_filename;

        if ( ! copy( $file_path, $new_path ) ) {
            return 0;
        }

        $filetype = wp_check_filetype( $new_filename, null );

        $new_attachment_id = wp_insert_attachment( array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => $attachment_data->post_title,
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $new_path, $target_post_id );

        if ( ! is_wp_error( $new_attachment_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata( $new_attachment_id, $new_path );
            wp_update_attachment_metadata( $new_attachment_id, $attach_data );
        }
    } finally {
        restore_current_blog();
    }

    return $new_attachment_id;
}

function ec_fwm_ajax_reset() {
    ec_fwm_verify_request();

    $deleted_posts = 0;
    $deleted_attachments = 0;

    try {
        switch_to_blog( EC_FWM_TARGET_BLOG );

        $post_ids = get_posts( array(
            'post_type'      => 'festival_wire',
            'post_status'    => 'any',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ) );

        foreach ( $post_ids as $post_id ) {
            // Delete child attachments
            $attachments = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'any',
                'post_parent'    => $post_id,
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'no_found_rows'  => true,
            ) );

            foreach ( $attachments as $att_id ) {
                wp_delete_attachment( $att_id, true );
                $deleted_attachments++;
            }

            wp_delete_post( $post_id, true );
            $deleted_posts++;
        }
    } finally {
        restore_current_blog();
    }

    wp_send_json_success( array(
        'deleted_posts'       => $deleted_posts,
        'deleted_attachments' => $deleted_attachments,
    ) );
}
